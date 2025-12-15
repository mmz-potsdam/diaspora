<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Novaway\Bundle\FeatureFlagBundle\Manager\FeatureManager;
use App\Repository\ArticleRepository;

/**
 *
 */
class DefaultController extends \TeiEditionBundle\Controller\TopicController
{
    /* shared code with PlaceController */
    use \TeiEditionBundle\Controller\MapHelperTrait;

    #[Route(path: '/', name: 'home')]
    public function indexAction(
        Request $request,
        EntityManagerInterface $entityManager,
        TranslatorInterface $translator,
        ?ArticleRepository $repository = null,
        ?FeatureManager $featureManager = null
    ): Response {
        [$markers, $bounds] = $this->buildMap($entityManager, $request->getLocale(), 'mentioned');

        $news = [];

        try {
            /* the following can fail */
            $url = $this->getParameter('app.wp-rest.url');

            if (!empty($url)) {
                try {
                    $client = new \Vnn\WpApiClient\WpClient(
                        new \Vnn\WpApiClient\Http\GuzzleAdapter(new \GuzzleHttp\Client()),
                        $url
                    );
                    $client->setCredentials(new \Vnn\WpApiClient\Auth\WpBasicAuth($this->getParameter('app.wp-rest.user'), $this->getParameter('app.wp-rest.password')));
                    $posts = $client->posts()->get(null, [
                        'per_page' => 4,
                        'lang' => $request->getLocale(),
                    ]);

                    if (!empty($posts)) {
                        foreach ($posts as $post) {
                            $article = new \TeiEditionBundle\Entity\Article();
                            $article->setName($post['title']['rendered']);
                            $article->setSlug($post['slug']);
                            $article->setDatePublished(new \DateTime($post['date_gmt']));

                            $news[] = $article;
                        }
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

        $articles = $repository->findPublished($request->getLocale(), 'newest', 20);
        shuffle($articles);

        $pageMeta = [];
        // Set the site name
        // https://developers.google.com/search/docs/appearance/site-names
        if ('/' == $request->getRequestUri()) {
            // we can only set the site name on the root URI
            $pageMeta['jsonLd'] = [
                '@context' => 'https://schema.org',
                '@type' => 'WebSite',
                'name' => $translator->trans($this->getGlobal('siteName'), [], 'additional'),
                'url' => $this->generateUrl('home', [], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL),
            ];
        }

        return $this->render(
            'Default/home.html.twig',
            [
                'pageTitle' => $translator->trans('Welcome'),
                'pageMeta' => $pageMeta,
                // 'topics' => $this->buildTopicsDescriptions($translator, $request->getLocale()),
                'articles' => $articles,
                'markers' => $markers,
                'bounds' => $bounds,
                'news' => $news,
            ]
        );
    }
}
