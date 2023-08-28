<?php
// src/Command/ArticleAuthorCommand.php

namespace App\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

/**
 * Lookup author(s) and translator and insert/update corresponding Person.
 */
class ArticleAuthorCommand
extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName('article:author')
            ->setDescription('Extract Author(s) and Translator')
            ->addArgument(
                'file',
                InputArgument::REQUIRED,
                'TEI file'
            )
            ->addOption(
                'insert-missing',
                null,
                InputOption::VALUE_NONE,
                'If set, missing entries will be added to person'
            )
            ->addOption(
                'update',
                null,
                InputOption::VALUE_NONE,
                'If set, an existing person will be updated'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $fname = $input->getArgument('file');

        $fs = new Filesystem();

        if (!$fs->exists($fname)) {
            $output->writeln(sprintf('<error>%s does not exist</error>', $fname));

            return 1;
        }

        $teiHelper = new \TeiEditionBundle\Utils\TeiHelper();

        $article = $teiHelper->analyzeHeader($fname);

        if (false === $article) {
            $output->writeln(sprintf('<error>%s could not be loaded</error>', $fname));
            foreach ($teiHelper->getErrors() as $error) {
                $output->writeln(sprintf('<error>  %s</error>', trim($error->message)));
            }

            return 1;
        }

        if (empty($article->author) && empty($article->translator)) {
            $output->writeln(sprintf('<info>No author or translator found in %s</info>', $fname));

            return 0;
        }

        $persons = !empty($article->author) ? $article->author : [];
        if (!empty($article->translator)) {
            $persons[] = $article->translator;
        }

        $output->writeln($this->jsonPrettyPrint($persons));

        if ($input->getOption('insert-missing')) {
            foreach ($persons as $author) {
                $person = null;

                $slug = $author->getSlug();
                if (empty($slug)) {
                    // check if we have gnd
                    $gnd = $author->getGnd();
                    if (!empty($gnd)) {
                        $uri = 'https://d-nb.info/gnd/' . $gnd;
                        $this->insertMissingPerson($uri);
                        $person = $this->findPersonByUri($uri);
                    }

                    if (is_null($person)) {
                        $output->writeln(sprintf('<info>Skip author with empty slug and gnd in %s</info>', $fname));
                        continue;
                    }
                }
                else {
                    $person = $this->findPersonBySlug($slug);
                }

                // either insert or update
                if (is_null($person)) {
                    $value = !empty($slug) ? $slug : $gnd;
                    $output->writeln(sprintf('<error>No user found for %s</error>',
                                             trim($value)));
                    continue;
                }
            }
        }

        return 0;
    }

    protected function findPersonBySlug($slug)
    {
        return $this->em->getRepository('TeiEditionBundle\Entity\Person')->findOneBySlug($slug);
    }
}
