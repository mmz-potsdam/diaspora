<?php

// src/Controller/PlaceController.php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Doctrine\ORM\EntityManagerInterface;

/**
 *
 */
class PlaceController extends \TeiEditionBundle\Controller\PlaceController
{
    /**
     * Override map to force mentioned places for the moment
     */
    #[Route(path: '/map', name: 'place-map')]
    #[Route(path: '/map/place', name: 'place-map-mentioned')]
    #[Route(path: '/map/landmark', name: 'place-map-landmark')]
    public function mapAction(
        Request $request,
        EntityManagerInterface $entityManager,
        TranslatorInterface $translator
    ) {
        [$markers, $bounds] = $this->buildMap(
            $entityManager,
            $request->getLocale(),
            'mentioned'
        );

        return $this->render('@TeiEdition/Place/map.html.twig', [
            'pageTitle' => $translator->trans('Map'),
            'bounds' => $bounds,
            'markers' => $markers,
        ]);
    }

    /**
     * Override map to force list of articles with mentioned places for the moment
     */
    #[Route(path: '/map/popup-content/{ids}', name: 'place-map-popup-content')]
    public function mapPopupContentAction(
        Request $request,
        EntityManagerInterface $entityManager,
        $ids
    ) {
        if (empty($ids)) {
            $articles = [];
        }
        else {
            $mode = str_replace('place-map-', '', $request->get('caller'));
            if ('landmark' != $mode) {
                // force mentioned places for the moment
                $mode = 'mentioned';
            }

            $ids = explode(',', $ids);
            $qb = $entityManager
                    ->getRepository(in_array($mode, [ 'mentioned', 'landmark' ])
                                             ? '\TeiEditionBundle\Entity\Article' : '\TeiEditionBundle\Entity\SourceArticle')
                    ->createQueryBuilder('A')
            ;

            $qb->select('A')
                    ->distinct()
                    ->andWhere('A.status IN (1) AND P.id IN (:ids)')
                    ->setParameter('ids', $ids)
            ;

            if ('mentioned' == $mode) {
                $qb
                ->innerJoin('A.placeReferences', 'AP')
                ->innerJoin('AP.place', 'P');
            }
            else if ('landmark' == $mode) {
                $qb
                ->innerJoin('A.landmarkReferences', 'AL')
                ->innerJoin('AL.landmark', 'P');

                $geo = $request->get('geo');
                if (!empty($geo)) {
                    $qb->andWhere('P.geo = :geo')
                        ->setParameter('geo', $geo)
                    ;
                }
            }
            else {
                $qb->innerJoin('A.contentLocation', 'P');

                $geo = $request->get('geo');
                if (!empty($geo)) {
                    $qb->andWhere('A.geo = :geo OR (A.geo IS NULL AND P.geo = :geo)')
                        ->setParameter('geo', $geo)
                    ;
                }
            }

            $locale = $request->getLocale();
            if (!empty($locale)) {
                $qb->andWhere('A.language = :lang')
                    ->setParameter('lang', \TeiEditionBundle\Utils\Iso639::code1to3($locale))
                ;
            }

            $qb->addOrderBy('A.dateCreated', 'ASC')
                ->addOrderBy('A.name', 'ASC');

            $articles = $qb
                    ->getQuery()
                    ->getResult();
            ;
        }

        return $this->render('@TeiEdition/Place/map-popup-content.html.twig', [
            'articles' => $articles,
        ]);
    }
}
