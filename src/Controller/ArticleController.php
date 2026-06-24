<?php

// src/Controller/ArticleController.php

namespace App\Controller;

use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Doctrine\ORM\EntityManagerInterface;

/**
 *
 */
class ArticleController extends \TeiEditionBundle\Controller\SourceController
{
    protected function buildRelated(EntityManagerInterface $entityManager, $article)
    {
        $related = parent::buildRelated($entityManager, $article);

        $spatialCoverageReferences = $article->getSpatialCoverageReferences();
        if (count($spatialCoverageReferences) > 0) {
            $ids = array_map(
                function ($ref) {
                    return $ref->getEntity()->getId();
                },
                $spatialCoverageReferences->toArray()
            );

            $qb = $entityManager
                    ->createQueryBuilder();

            if (in_array('country', $article->getKeywords())) {
                // get all articles with spatialCoverage as geographical survey

                $qb->select([ 'A',
                    'A.creator HIDDEN articleSort',
                ])
                    ->distinct()
                    ->from('\TeiEditionBundle\Entity\Article', 'A')
                    ->innerJoin('A.spatialCoverageReferences', 'AP')
                    ->innerJoin('AP.place', 'P')
                    ->where('A.status = 1 AND A.id <> :id')
                    ->setParameter('id', $article->getId())
                    ->andWhere('A.language = :language')
                    ->setParameter('language', $article->getLanguage())
                    ->andWhere("A.articleSection IN ('background', 'interpretation')")
                    ->andWhere("P.id IN (:ids)")
                    ->setParameter('ids', $ids)
                    ->orderBy('articleSort, A.creator, A.name')
                ;

                $spatiallyRelated = $qb->getQuery()->getResult();
                if (count($spatiallyRelated) > 0) {
                    $related = array_merge($related, $spatiallyRelated);
                }
            }
            else {
                // get all geographical overviews
                $qb->select([ 'A',
                    'A.creator HIDDEN articleSort',
                ])
                    ->distinct()
                    ->from('\TeiEditionBundle\Entity\Article', 'A')
                    ->innerJoin('A.spatialCoverageReferences', 'AP')
                    ->innerJoin('AP.place', 'P')
                    ->where('A.status = 1 AND A.id <> :id')
                    ->setParameter('id', $article->getId())
                    ->andWhere('A.language = :language')
                    ->setParameter('language', $article->getLanguage())
                    ->andWhere("A.articleSection IN ('background', 'interpretation')")
                    ->andWhere("A.keywords LIKE '%country%'") // not optimal
                    ->andWhere("P.id IN (:ids)")
                    ->setParameter('ids', $ids)
                    ->orderBy('articleSort, A.creator, A.name')
                ;

                $spatiallyRelated = $qb->getQuery()->getResult();
                if (count($spatiallyRelated) > 0) {
                    $related = array_merge($related, $spatiallyRelated);
                }
            }
        }

        return $related;
    }
}
