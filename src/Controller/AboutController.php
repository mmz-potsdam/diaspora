<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Render the data/tei/about-*.locale.xml
 */
class AboutController
extends \TeiEditionBundle\Controller\RenderTeiController
{
    /**
     * Render about-text from TEI to HTML
     */
    protected function renderContent(Request $request, $fnameTei)
    {
        $params = [
            'lang' => \TeiEditionBundle\Utils\Iso639::code1To3($request->getLocale()),
        ];

        $html = $this->renderTei($fnameTei, 'dtabf_article-printview.xsl', [
            'params' => $params,
        ]);

        if (false === $html) {
            return '<div class="alert alert-warning">'
                 . 'Error: Invalid or missing file: ' . $fnameTei
                 . '</div>';
        }

        return $html;
    }

    /**
     * Render about-text from TEI to HTML
     * If $title is null, extract from TEI
     */
    protected function renderTitleContent(Request $request,
                                          $template,
                                          $title = null)
    {
        $route = $request->get('_route');
        $locale = $request->getLocale();
        $fnameTei = $route . '.' . $locale . '.xml';

        if (is_null($title)) {
            $teiHelper = new \TeiEditionBundle\Utils\TeiHelper();
            $meta = $teiHelper->analyzeHeader($this->locateTeiResource($fnameTei));
            if (!is_null($meta)) {
                $title = $meta->name;
            }
        }

        return $this->render($template, [
            'pageTitle' => $title,
            'title' => $title,
            'content' => $this->renderContent($request, $fnameTei),
        ]);
    }

    /**
     * @Route("/about", name="about")
     * xRoute("/terms", name="terms")
     * @Route("/contact", name="contact")
     */
    public function renderAbout(Request $request,
                                TranslatorInterface $translator,
                                $title = null)
    {
        return $this->renderTitleContent($request, 'About/sitetext.html.twig', $title);
    }

    /**
     * @Route("/about/team", name="about-us")
     * xRoute("/about/editors", name="about-editors")
     * xRoute("/about/board", name="about-board")
     * xRoute("/about/sponsors", name="about-sponsors")
     * xRoute("/about/cfp", name="about-cfp")
     */
    public function renderAboutUs(Request $request, $title = null)
    {
        return $this->renderTitleContent($request, 'About/sitetext.html.twig', $title);
    }
}
