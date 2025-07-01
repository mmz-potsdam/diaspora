<?php

namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use TeiEditionBundle\Entity\Article;

/**
 * @extends ServiceEntityRepository<Article>
 *
 * @method Article|null find($id, $lockMode = null, $lockVersion = null)
 * @method Article|null findOneBy(array $criteria, array $orderBy = null)
 * @method Article[]    findAll()
 * @method Article[]    findBy(array $criteria, ?array $orderBy = null, $limit = null, $offset = null)
 */
class ArticleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Article::class);
    }

    /**
     * Logic copied from the TeiEditionBundle\ControllerArticleController
     * TODO: move ArticleRepository to TeiEditionBundle and adjust ArticleController
     */
    public function findPublished($locale = null, $order = 'newest', $limit = null, $offset = null): array
    {
        $language = null;
        if (!empty($locale)) {
            $language = \TeiEditionBundle\Utils\Iso639::code1to3($locale);
        }

        $sort = 'creator'
            ? 'A.creator' : '-A.datePublished';

        $qb =  $this->getEntityManager()
                ->createQueryBuilder();

        $qb->select([ 'A',
            $sort . ' HIDDEN articleSort',
        ])
            ->from('\TeiEditionBundle\Entity\Article', 'A')
            ->where('A.status = 1')
            ->andWhere('A.language = :language')
            ->andWhere("A.articleSection IN ('background', 'interpretation')")
            ->andWhere('A.creator IS NOT NULL') // TODO: set for background
            ->orderBy('articleSort, A.creator, A.name')
        ;
        $query = $qb->getQuery();
        if (!empty($language)) {
            $query->setParameter('language', $language);
        }

        if (isset($limit) && $limit > 0) {
            $query->setMaxResults($limit);

            if (isset($offset) && $offset > 0) {
                $query->setFirstResult($offset);
            }
        }

        return $query->getResult();
    }
}
