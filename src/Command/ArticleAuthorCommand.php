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
class ArticleAuthorCommand extends BaseCommand
{
    protected function configure(): void
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

    protected function execute(InputInterface $input, OutputInterface $output): int
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

        if ($input->getOption('insert-missing') || $input->getOption('update')) {
            foreach ($persons as $author) {
                $person = null;

                $slug = $author->getSlug();
                $gnd = $author->getGnd();

                if (empty($slug) && empty($gnd)) {
                    $output->writeln(sprintf('<info>Skip author with empty slug and gnd in %s</info>', $fname));
                    continue;
                }

                if (!empty($slug)) {
                    $person = $this->findPersonBySlug($slug);
                }

                if (!empty($gnd)) {
                    $uri = 'https://d-nb.info/gnd/' . $gnd;
                    if (is_null($person)) {
                        $person = $this->findPersonByUri($uri);
                    }
                }

                if (!is_null($person) && !$input->getOption('update')) {
                    // person already exists and we are not updating, so we are done
                    continue;
                }

                // either insert or update
                $user = !empty($slug)
                    ? $this->findUserFromAdminBySlug($slug, $output)
                    : $this->findUserFromAdminByGnd($gnd, $output);

                if (is_null($user)) {
                    if (!empty($gnd) && !$input->getOption('update')) {
                        // try to create missing person from gnd
                        $this->insertMissingPerson($uri);
                        $person = $this->findPersonByUri($uri);

                        if (!is_null($person)) {
                            if (!empty($slug)) {
                                $person->setSlug($slug);
                                $this->em->persist($person);
                                $this->flushEm($this->em);
                            }
                        }
                    }

                    // can't insert or update
                    if (is_null($person) || $input->getOption('update')) {
                        $value = !empty($slug) ? $slug : $gnd;
                        $output->writeln(sprintf(
                            '<error>No user found for %s</error>',
                            trim($value)
                        ));
                        continue;
                    }
                }
                else {
                    // set from $user
                    if (is_null($person)) {
                        $person = new \TeiEditionBundle\Entity\Person();
                    }

                    if (!empty($slug)) {
                        $person->setSlug($slug);
                    }

                    foreach ([
                        'title' => 'honoricPrefix',
                        'firstname' => 'givenName',
                        'lastname' => 'familyName',
                        'position' => 'jobTitle',
                        'sex' => 'gender',
                        'url' => 'url',
                        'gnd' => 'gnd',
                    ] as $src => $target) {
                        if (!empty($user[$src])) {
                            if ('url' == $src && preg_match('/^keine/i', $user[$src])) {
                                $user[$src] = null;
                            }

                            $methodName = 'set' . ucfirst($target);
                            $person->$methodName($user[$src]);
                        }
                        else if ('url' == $target) {
                            // clear url
                            $methodName = 'set' . ucfirst($target);
                            $person->$methodName(null);
                        }
                    }

                    $description = [];
                    if (!empty($user['description_de'])) {
                        $description['de'] = $user['description_de'];
                    }

                    if (!empty($user['description'])) {
                        $description['en'] = $user['description'];
                    }

                    $person->setDescription($description);

                    // var_dump(json_encode($person));
                    $this->em->persist($person);
                    $this->flushEm($this->em);
                }
            }
        }

        return 0;
    }

    protected function findUserFromAdminBySlug($slug, $output)
    {
        $sql = "SELECT * FROM User WHERE slug = :slug AND status <> -100";

        $users = $this->dbconnAdmin->fetchAllAssociative($sql, [ 'slug' => $slug ]);
        if (empty($users)) {
            return;
        }

        if (count($users) > 1) {
            $output->writeln(sprintf(
                '<error>More than one user found for %s (IDs %s)</error>',
                trim($slug),
                join(', ', array_map(function ($user) { return $user['id']; }, $users))
            ));
        }

        return $users[0];
    }

    protected function findUserFromAdminByGnd($gnd, $output)
    {
        $sql = "SELECT * FROM User WHERE gnd = :gnd AND status <> -100";

        $users = $this->dbconnAdmin->fetchAllAssociative($sql, [ 'gnd' => $gnd ]);
        if (empty($users)) {
            return;
        }

        if (count($users) > 1) {
            $output->writeln(sprintf(
                '<error>More than one user found for %s (IDs %s)</error>',
                trim($slug),
                join(', ', array_map(function ($user) { return $user['id']; }, $users))
            ));
        }

        return $users[0];
    }

    protected function findPersonBySlug($slug)
    {
        return $this->em->getRepository('TeiEditionBundle\Entity\Person')->findOneBySlug($slug);
    }
}
