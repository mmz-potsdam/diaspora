<?php

// src/Command/ArticleEnhanceCommand.php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Use textrazor-api to add entities linked to Wikidata to TEI.
 */
class ArticleEnhanceCommand extends BaseCommand
{
    protected function configure(): void
    {
        $this
            ->setName('article:enhance')
            ->setDescription('Enhance TEI')
            ->addArgument(
                'file',
                InputArgument::REQUIRED,
                'TEI file'
            )
            ->addOption(
                'add-entities',
                null,
                InputOption::VALUE_NONE,
                'If set, textrazor-api will be used to add entities',
            )
            ->addOption(
                'tidy',
                null,
                InputOption::VALUE_NONE,
                'If set, the output will be pretty printed'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $fname = $input->getArgument('file');

        $fs = new Filesystem();

        if (!$fs->exists($fname)) {
            $output->writeln(sprintf('<error>%s does not exist</error>', $fname));

            return  Command::FAILURE;
        }

        $xmlAsString = file_get_contents($fname);

        if ($input->getOption('add-entities')) {
            $textRazorOptions =  $this->getParameter('app.textrazor.options');
            if (empty($textRazorOptions['api-key'])) {
                $output->writeln('<error>TEXTRAZOR_API_KEY api key not set</error>');

                return Command::FAILURE;
            }

            $textRazorApiKey = $textRazorOptions['api-key'];


            $teiEnhancer = new \App\Utils\TeiEnhancer([
                'textRazorApiKey' => $textRazorApiKey,
                'ignore' => [
                    'Q183' => ['/^deutsch[esnr]*$/i', '/^german[s]*$/i'],
                    'Q7318' => ['/^deutsch[esnr]*$/i', '/^german[s]*$/i'],
                ],
            ]);

            $xmlAsString = $teiEnhancer->addEntities($xmlAsString);

            // strip added again from <bibl> tags
            $xslAsString = <<<EOT
                <xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:tei="http://www.tei-c.org/ns/1.0"
                exclude-result-prefixes="tei">
                <xsl:output method="xml" version="1.0" encoding="UTF-8" indent="yes"/>
                <xsl:strip-space elements="*"/>

                <!-- identity transform -->
                <xsl:template match="@*|node()">
                    <xsl:copy>
                        <xsl:apply-templates select="@*|node()"/>
                    </xsl:copy>
                </xsl:template>

                <!-- strip *Name tags -->
                <xsl:template match="tei:bibl//*[local-name() = 'orgName' or local-name() = 'persName' or local-name()='placeName']">
                    <xsl:apply-templates/>
                </xsl:template>

                </xsl:stylesheet>
            EOT;

            $xslt = new \XSLTProcessor();
            $xsltDoc = new \DomDocument();
            $xsltDoc->loadXML($xslAsString);
            $xslt->importStyleSheet($xsltDoc);
            $xmlDoc = new \DomDocument();
            $xmlDoc->loadXML($xmlAsString);
            $xmlReduced = $xslt->transformToXML($xmlDoc);

            if (false !== $xmlReduced) {
                $xmlAsString = $xmlReduced;
            }
        }

        $tmpname = $fs->tempnam(sys_get_temp_dir(), 'tei');
        file_put_contents($tmpname, $xmlAsString);
        $teiHelper = new \TeiEditionBundle\Utils\TeiHelper();
        $entities = $teiHelper->extractEntities($tmpname, true);
        unlink($tmpname);

        $wikidataService = new \LodService\LodService(new \LodService\Provider\WikidataProvider());

        foreach ($entities as $type => $entitiesOfType) {
            $uris = [];
            foreach ($entitiesOfType as $uri => $count) {
                if (preg_match('/^'
                            . preg_quote('http://www.wikidata.org/entity/', '/')
                            . '(Q\d+)$/', $uri, $matches))
                {
                    $uris[$uri] = false;
                }
            }

            if (empty($uris)) {
                continue;
            }

            foreach ($uris as $uri => $ignore) {
                // first lookup from existing entities
                $entity = null;
                switch ($type) {
                    case 'place':
                        $entity = $this->findPlaceByUri($uri);

                        if (!is_null($entity) && !empty($entity->getTgn())) {
                            $identifer = new \LodService\Identifier\TgnIdentifier($entity->getTgn());
                            $uris[$uri] = $identifer->toUri();
                        }

                        break;
                }

                if (is_null($entity) ) {
                    // lookup sameAs
                    $identifer = \LodService\Identifier\Factory::fromUri($uri);
                    $sameAs = $wikidataService->lookupSameAs($identifer);
                    foreach ($sameAs as $identifer) {
                        switch ($type) {
                            case 'person':
                            case 'organization':
                                    if ($identifer instanceof \LodService\Identifier\GndIdentifier) {
                                    $uris[$uri] = $identifer->toUri();
                                }
                                break;

                            case 'place':
                                if ($identifer instanceof \LodService\Identifier\TgnIdentifier) {
                                    $uris[$uri] = $identifer->toUri();
                                }
                                break;
                        }
                    }
                }
            }

            $replace = array_filter($uris);
            $xmlAsString = str_replace(array_keys($replace), array_values($replace), $xmlAsString);
        }

        if ($input->getOption('tidy')) {
            $res = $this->formatter->formatXML($xmlAsString);
            if (is_string($res)) {
                $xmlAsString = $res;
            }
        }

        $output->write($xmlAsString);

        return Command::SUCCESS;
    }
}
