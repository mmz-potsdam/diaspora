<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Controller for "Bring Your Own Story".
 */
class BringYourOwnStoryController extends AbstractController
{
    #[Route(path: '/bring-your-own-story', name: 'bring-your-own-story')]
    public function renderIntr(
        Request $request,
        TranslatorInterface $translator,
        $title = null
    ): Response {
        return $this->render(
            'BringYourOwnStory/intro.html.twig',
            [
                'pageTitle' => $translator->trans('Bring Your Own Story'),
            ]
        );
    }
}
