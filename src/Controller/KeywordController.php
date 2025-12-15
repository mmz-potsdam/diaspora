<?php

// src/Controller/KeywordController.php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Doctrine\ORM\EntityManagerInterface;

/**
 *
 */
class KeywordController extends \TeiEditionBundle\Controller\TopicController
{
    protected static $GENRES = [
        'country',
        'biography',
        'source',
    ];

    /* TODO: inject these topics */
    public static $TOPICS = [
        'Alltag',
        'Begegnungen',
        'Erbe und Erinnern',
        'Geschlecht und Generation',
        'Institutionen',
        'Identität und Religiosität',
        'Kultur- und Wissenstransfer',
        'Rückkehr',
        'Sprache',
        'Transnationale Netzwerke',
    ];

    public static function fetchActiveKeywords(
        EntityManagerInterface $entityManager,
        TranslatorInterface $translator,
        $locale,
        ?array $filter = null,
        $sortByKeyword = true
    ) {
        $language = null;
        if (!empty($locale)) {
            $language = \TeiEditionBundle\Utils\Iso639::code1to3($locale);
        }

        $qb = $entityManager
                ->createQueryBuilder();

        $qb->select([ 'A.keywords' ])
            ->distinct()
            ->from('\TeiEditionBundle\Entity\Article', 'A')
            ->where('A.status = 1')
            ->andWhere('A.language = :language')
            ->andWhere("A.articleSection IN ('interpretation')")
        ;

        $query = $qb->getQuery();
        if (!empty($language)) {
            $query->setParameter('language', $language);
        }

        $keywords = [];
        foreach ($query->getResult() as $row) {
            $keywords = array_unique(array_merge($keywords, $row['keywords']));
        }

        if (!is_null($filter)) {
            $keywords = array_values(array_intersect($keywords, $filter));
        }

        if ($sortByKeyword) {
            $coll = collator_create($locale);
            usort($keywords, function ($keywordA, $keywordB) use ($translator, $coll) {
                return collator_compare(
                    $coll,
                    /** @Ignore */
                    $translator->trans($keywordA, [], 'additional'),
                    /** @Ignore */
                    $translator->trans($keywordB, [], 'additional')
                );
            });
        }

        return $keywords;
    }

    public static function lookupLocalizedTopic($topic, $translator, $locale)
    {
        // we need to get from localized to non-localized term
        $localeTranslator = $translator->getLocale();
        if ($localeTranslator != $locale) {
            $translator->setLocale($locale);
        }

        foreach (self::$GENRES + self::$TOPICS as $label) {
            if (/** @Ignore */ $translator->trans($label, [], 'additional') == $topic) {
                $topic = $label;
                break;
            }
        }

        if ($localeTranslator != $locale) {
            $translator->setLocale($localeTranslator);
        }

        return $topic;
    }

    /**
     * return $keywords reduced to genre
     */
    public static function extractGenres($keywords)
    {
        return array_values(array_intersect($keywords, self::$GENRES));
    }

    /**
     * return $keywords reduced to topic
     */
    public static function extractTopics($keywords)
    {
        return array_values(array_intersect($keywords, self::$TOPICS));
    }

    protected function buildTopicsBySlug(TranslatorInterface $translator, $translateKeys = false)
    {
        $topics = [];
        foreach (self::$GENRES + self::$TOPICS as $label) {
            $labelTranslated = /** @Ignore */ $translator->trans($label, [], 'additional');
            $key = $this->slugify($translateKeys ? $labelTranslated : $label);
            $topics[$key] = $labelTranslated;
        }

        return $topics;
    }

    protected function fetchArticlesByKeyword(
        EntityManagerInterface $entityManager,
        TranslatorInterface $translator,
        $locale,
        ?array $filter = null,
        $sortByKeyword = true
    ) {
        $language = null;
        if (!empty($locale)) {
            $language = \TeiEditionBundle\Utils\Iso639::code1to3($locale);
        }

        $sort = 'A.creator'; // possibly: A.name

        $qb = $entityManager
                ->createQueryBuilder();

        $qb->select([ 'A',
            $sort . ' HIDDEN articleSort',
        ])
            ->from('\TeiEditionBundle\Entity\Article', 'A')
            ->where('A.status = 1')
            ->andWhere('A.language = :language')
            ->andWhere("A.articleSection IN ('interpretation')")
            ->orderBy('articleSort, A.creator, A.name')
        ;

        $query = $qb->getQuery();
        if (!empty($language)) {
            $query->setParameter('language', $language);
        }

        $articlesByKeyword = [];
        foreach ($query->getResult() as $article) {
            foreach ($article->getKeywords() as $keyword) {
                if (!array_key_exists($keyword, $articlesByKeyword)) {
                    $articlesByKeyword[$keyword] = [];
                }

                $articlesByKeyword[$keyword][] = $article;
            }
        }

        if (!is_null($filter)) {
            $articlesByKeyword = array_filter(
                $articlesByKeyword,
                function ($keyword) use ($filter) {
                    return in_array($keyword, $filter);
                },
                ARRAY_FILTER_USE_KEY
            );
        }

        if ($sortByKeyword) {
            $coll = collator_create($locale);
            uksort($articlesByKeyword, function ($keywordA, $keywordB) use ($translator, $coll) {
                return collator_compare(
                    $coll,
                    /** @Ignore */
                    $translator->trans($keywordA, [], 'additional'),
                    /** @Ignore */
                    $translator->trans($keywordB, [], 'additional')
                );
            });
        }
        else if (!is_null($filter)) {
            // sort by order of filter
            uksort($articlesByKeyword, function ($keywordA, $keywordB) use ($filter) {
                if ($keywordA == $keywordB) {
                    return 0;
                }

                $indexA = array_search($keywordA, $filter);
                $indexB = array_search($keywordB, $filter);

                if ($indexA === false && $indexB === false) {
                    return 0;
                }
                else if ($indexA === false) {
                    return 1;
                }
                else if ($indexB === false) {
                    return -1;
                }

                return $indexA - $indexB;
            });
        }

        return $articlesByKeyword;
    }

    /**
     */
    #[Route(path: '/genre', name: 'genre-index')]
    public function genreAction(
        Request $request,
        EntityManagerInterface $entityManager,
        TranslatorInterface $translator
    ): Response {
        $articlesByGenre = $this->fetchArticlesByKeyword($entityManager, $translator, $request->getLocale(), self::$GENRES, false);

        return $this->render('Keyword/genre-index.html.twig', [
            'pageTitle' => $translator->trans('Textual Genres'),
            'articlesByKeyword' => $articlesByGenre,
        ]);
    }

    /**
     */
    #[Route(path: '/topic', name: 'topic-index')]
    public function indexAction(
        Request $request,
        EntityManagerInterface $entityManager,
        TranslatorInterface $translator
    ): Response {
        $articlesByTopic = $this->fetchArticlesByKeyword($entityManager, $translator, $request->getLocale(), self::$TOPICS);

        return $this->render('Keyword/topic-index.html.twig', [
            'pageTitle' => $translator->trans('Topics'),
            'articlesByKeyword' => $articlesByTopic,
        ]);
    }
}
