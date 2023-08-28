<?php
// src/TeiEditionBundle/Command/ArticleRefreshCommand.php

namespace App\Command;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

/**
 * Meta command that calls article:* commands in a single run.
 */
class ArticleRefreshCommand
extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName('article:refresh')
            ->setDescription('Meta command to article:adjust / article:header / article:content / articlce:entity / article:biblio')
            ->addArgument(
                'file',
                InputArgument::REQUIRED,
                'TEI file'
            )
            ->addOption(
                'publish',
                null,
                InputOption::VALUE_NONE,
                'If set, article:publish will be called as well'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $fnameInput = $input->getArgument('file');
        $quiet = $input->getOption('quiet');

        $fs = new Filesystem();

        if (!$fs->exists($fnameInput)) {
            $output->writeln(sprintf('<error>%s does not exist</error>', $fnameInput));

            return 1;
        }

        $basename = pathinfo($fnameInput, PATHINFO_FILENAME);
        if (!preg_match('/\.([a-z]+)$/', $basename, $matches)) {
            $output->writeln(sprintf('<error>%s is missing a language code</error>', $fnameInput));

            return 1;
        }

        $langIso2 = $matches[1];

        $ext = pathinfo($fnameInput, PATHINFO_EXTENSION);
        if ('xml' != $ext) {
            $output->writeln(sprintf('<error>Invalid file extension for %s (must be .xml)</error>',
                                     $fnameInput));

            return 1;
        }

        // TODO: switch to LanguageStrategy (since not every locale might have a translation)
        if (!in_array($langIso2, $this->getParameter('app.locales'))) {
            // alternate source languages don't have a article-entity that needs to be updated
            return 0;
        }

        $commands = [
            'article:author' => [],
            'article:header' => $input->getOption('publish')
                ? [ '--update', '--publish' ] : [ '--update' ],
            'article:content' => [ '--update' ],
            'article:entity' => [ '--insert-missing', '--set-references' ],
            'article:biblio' => [ '--set-references' ],
        ];

        $intermediateOutput = $quiet ? new NullOutput() : $output;
        foreach ($commands as $name => $calls) {
            // How to Call Other Commands, see https://symfony.com/doc/4.4/console/calling_commands.html

            $command = $this->getApplication()->find($name);

            foreach ($calls as $call) {
                $arguments = [
                    'command' => $name,
                    'file' => $fnameInput,
                    $call => null,
                ];

                $output->write(sprintf('<info>Running %s %s %s: </info>',
                                       $name, $call, $fnameInput));

                $returnCode = $command->run(new ArrayInput($arguments), $intermediateOutput);

                if (0 != $returnCode) {
                    $output->writeln('<error> [FAIL]</error>');

                    return 3;
                }

                $output->writeln('<info> [OK]</info>');
            }
        }

        return 0;
    }
}
