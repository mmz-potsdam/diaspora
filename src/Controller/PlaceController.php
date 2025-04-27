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
}
