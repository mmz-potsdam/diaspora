<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Render the data/tei/about-*.locale.xml
 */
class AboutController extends \TeiEditionBundle\Controller\RenderTeiController
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

        $crawler = new \Symfony\Component\DomCrawler\Crawler();
        $crawler->addHtmlContent($html);

        // headers for TOC
        $adjusted = false;
        $sectionHeaders = $crawler->filterXPath('//h2')->each(function ($node, $i) use (&$adjusted) {
            $id = $node->attr('id');
            if (empty($id)) {
                $adjusted = true;
                $id = 'section-' . $i;
                $node->getNode(0)->setAttribute('id', $id);
            }

            return [
                'id' => $id,
                'text' => $this->extractTextFromNode($node),
            ];
        });

        if ($adjusted) {
            $html = preg_replace('/<\/?body>/', '', $crawler->html());
        }

        if (count($sectionHeaders) > 0) {
            $html = $this->renderView('About/wrap-section-toc.html.twig', [
                'section_headers' => $sectionHeaders,
                'content' => $html,
            ]);
        }

        return $html;
    }

    /**
     * Render about-text from TEI to HTML
     * If $title is null, extract from TEI
     */
    protected function renderTitleContent(
        Request $request,
        $template,
        $title = null
    ) {
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

    #[Route(path: '/about', name: 'about')]
    #[Route(path: '/about/diaspora', name: 'about-diaspora')]
    #[Route(path: '/about/website', name: 'about-website')]
    #[Route(path: '/about/editing', name: 'about-editing')]
    // #[Route(path: '/terms', name: 'terms')]
    #[Route(path: '/contact', name: 'contact')]
    public function renderAbout(
        Request $request,
        TranslatorInterface $translator,
        $title = null
    ): Response {
        return $this->renderTitleContent($request, 'About/sitetext.html.twig', $title);
    }

    #[Route(path: '/about/team', name: 'about-us')]
    public function renderAboutUs(Request $request, $title = null): Response
    {
        return $this->renderTitleContent($request, 'About/sitetext.html.twig', $title);
    }

    /**
     * build Article-entities from Wordpress-API entries
     */
    protected function buildNewsArticles(&$posts, $client)
    {
        $articles = [];
        $categories = [];

        foreach ($posts as $post) {
            $article = new \TeiEditionBundle\Entity\Article();
            $article->setName($post['title']['rendered']);
            $article->setSlug($post['slug']);
            $article->setText($post['content']['rendered']);
            $article->setDatePublished(new \DateTime($post['date_gmt']));

            $keywords = [];
            if (!empty($post['categories'])) {
                foreach ($post['categories'] as $category) {
                    if (array_key_exists($category, $categories)) {
                        $keywords[] = $categories[$category];
                    }
                    else {
                        $categoryInfo = $client->categories()->get($category);
                        $keywords[] = $categories[$category] = $categoryInfo['name'];
                    }
                }

                $article->setKeywords(join(' / ', $keywords));
            }

            if (!empty($post['featured_media'])) {
                try {
                    $featuredMedia = $client->media()->get($post['featured_media']);
                    if (!empty($featuredMedia)) {
                        $size = array_key_exists('medium', $featuredMedia['media_details']['sizes'])
                            ? 'medium' : 'full';
                        $article->thumbnailUrl = $featuredMedia['media_details']['sizes'][$size]['source_url'];
                    }
                }
                catch (\Exception $e) {
                    ; // ignore
                }
            }

            $articles[] = $article;
        }

        return $articles;
    }

    /**
     * If app.wordpress.url is set, get news-entries
     * through Wordpress-API
     */
    #[Route(path: '/about/news', name: 'about-news')]
    public function newsAction(Request $request,
                               TranslatorInterface $translator)
    {
        try {
            /* the following can fail */
            $url = $this->getParameter('app.wordpress.url');

            if (!empty($url)) {
                try {
                    $client = new \Vnn\WpApiClient\WpClient(
                        new \Vnn\WpApiClient\Http\GuzzleAdapter(new \GuzzleHttp\Client()),
                            $url);
                    // $client->setCredentials(new \Vnn\WpApiClient\Auth\WpBasicAuth($this->getParameter('app.wordpress.user'), $this->getParameter('app.wordpress.password')));

                    $posts = $client->posts()->get(null, [
                        'per_page' => 15,
                        'lang' => $request->getLocale(),
                    ]);

                    if (!empty($posts)) {
                        return $this->render('@TeiEdition/About/news.html.twig', [
                            'articles' => $this->buildNewsArticles($posts, $client),
                        ]);
                    }
                }
                catch (\Exception $e) {
                    ;
                }
            }
        }
        catch (\InvalidArgumentException $e) {
            ; // ignore
        }

        // static fallback
        return $this->renderAbout($request, $translator, $translator->trans('News'));
    }
}
