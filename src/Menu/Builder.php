<?php

// src/Menu/Builder.php

// see http://symfony.com/doc/current/bundles/KnpMenuBundle/index.html

namespace App\Menu;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Novaway\Bundle\FeatureFlagBundle\Manager\FeatureManager;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class Builder
{
    private $factory;
    private $translator;
    private $requestStack;
    private $router;
    private $featureManager;

    /**
     * @param FactoryInterface $factory
     * @param TranslatorInterface $translator
     * @param RequestStack $requestStack
     * @param Router $router
     * @param FeatureManager|null $featureManager
     *
     * Add any other dependency you need
     */
    public function __construct(
        FactoryInterface $factory,
        TranslatorInterface $translator,
        RequestStack $requestStack,
        RouterInterface $router,
        ?FeatureManager $featureManager = null
    ) {
        $this->factory = $factory;
        $this->translator = $translator;
        $this->requestStack = $requestStack;
        $this->router = $router;
        $this->featureManager = $featureManager;
    }

    public function createTopMenu(array $options): ItemInterface
    {
        $menu = $this->factory->createItem('root');
        if (array_key_exists('position', $options) && 'footer' == $options['position']) {
            $menu->setChildrenAttributes([ 'id' => 'menu-top-footer', 'class' => 'small' ]);
        }
        else {
            $menu->setChildrenAttributes([ 'id' => 'menu-top', 'class' => 'list-inline' ]);
        }

        // add menu items
        if (!array_key_exists('part', $options) || 'left' == $options['part']) {
            if ((is_null($this->featureManager) || $this->featureManager->isEnabled('limited_navigation'))
                || (array_key_exists('position', $options) && 'footer' == $options['position'])) {
                // flat about
                $menu->addChild('about', [
                    'label' => $this->translator->trans('The Project'),
                    'route' => 'about',
                ])
                    ->setAttribute('class', 'list-inline-item');

                $menu->addChild('about-us', [
                    'label' => $this->translator->trans('About us'),
                    'route' => 'about-us',
                ])
                    ->setAttribute('class', 'list-inline-item');
            }
            else {
                // hierarchical about
                $menu->addChild('about', [
                    'label' => $this->translator->trans('About'),
                    'route' => 'about',
                    'attributes' => [
                        'id' => 'dropdownAboutMenuButton',
                        'class' => 'list-inline-item nav-item dropdown',
                    ],
                    'linkAttributes' => [
                        'class' => 'dropdown-toggle', // possibly prepend nav-link
                        'dropdown' => true,
                        'role' => 'button',
                        'data-bs-toggle' => 'dropdown',
                        'aria-expanded' => 'false',
                    ],
                    'childrenAttributes' => [
                        'class' => 'dropdown-menu dropdown-menu-center',
                        'aria-labelledby' => 'dropdownAboutMenuButton',
                    ],
                ]);

                $menu['about']
                    ->addChild('about-diaspora', [
                        'label' => $this->translator->trans('German-Jewish Diapora'),
                        'route' => 'about-diaspora',
                        'linkAttributes' => [
                            'class' => 'dropdown-item',
                        ],
                    ]);


                $menu['about']
                    ->addChild('about', [
                        'label' => $this->translator->trans('The Project'),
                        'route' => 'about',
                        'linkAttributes' => [
                            'class' => 'dropdown-item',
                        ],
                    ]);

                $menu['about']
                    ->addChild('about-website', [
                        'label' => $this->translator->trans('This Website'),
                        'route' => 'about-website',
                        'linkAttributes' => [
                            'class' => 'dropdown-item',
                        ],
                    ]);

                $menu['about']
                    ->addChild('about-editing', [
                        'label' => $this->translator->trans('Editorial Model and Guidelines'),
                        'route' => 'about-editing',
                        'linkAttributes' => [
                            'class' => 'dropdown-item',
                        ],
                    ]);

                $menu['about']
                    ->addChild('about-us', [
                        'label' => $this->translator->trans('About us'),
                        'route' => 'about-us',
                        'linkAttributes' => [
                            'class' => 'dropdown-item',
                        ],
                    ]);
            }

            /*
            $menu->addChild('terms', [
                    'label' => $this->translator->trans('Terms and Conditions'), 'route' => 'terms',
                ])
                ->setAttribute('class', 'list-inline-item');
            */

            $menu->addChild('contact', [
                'label' => $this->translator->trans('Contact'), 'route' => 'contact',
            ])
                ->setAttribute('class', 'list-inline-item');
        }

        return $menu;
    }

    public function createMainMenu(array $options): ItemInterface
    {
        $breadcrumbMode = isset($options['position']) && 'breadcrumb' == $options['position'];

        $menu = $this->factory->createItem('home', [ 'label' => $this->translator->trans('Home'), 'route' => 'home' ]);
        if (array_key_exists('position', $options) && 'footer' == $options['position']) {
            $menu->setChildrenAttributes([ 'id' => 'menu-main-footer', 'class' => 'small' ]);
        }
        else {
            $menu->setChildrenAttributes([ 'id' => 'menu-main', 'class' => 'list-inline' ]);
        }

        // add menu item
        /*
        $menu->addChild('topic-index', [
            'label' => $this->translator->trans('Topics'),
            'route' => 'topic-index',
        ])
            ->setAttribute('class', 'list-inline-item');

        $menu->addChild('date-chronology', [
            'label' => $this->translator->trans('Chronology'),
            'route' => 'date-chronology',
        ])
            ->setAttribute('class', 'list-inline-item');
        */

        $menu->addChild('place-map', [
            'label' => $this->translator->trans('Map'),
            'route' => 'place-map',
        ])
            ->setAttribute('class', 'list-inline-item');

        $menu->addChild('article-index', [
            'label' => $this->translator->trans('Articles'),
            'route' => 'article-index',
        ])
            ->setAttribute('class', 'list-inline-item');

        $menu->addChild('_lookup', [
            'label' => $this->translator->trans('Look-up'),
            'uri' => '#',
        ])
            ->setAttribute('class', 'list-inline-item')
            ->setAttribute('dropdown', true);

        $menu['_lookup']
            ->addChild('person-index', [
                'label' => $this->translator->trans('Persons'),
                'route' => 'person-index',
            ]);
        $menu['_lookup']
            ->addChild('place-index', [
                'label' => $this->translator->trans('Places'),
                'route' => 'place-index',
            ]);
        $menu['_lookup']
            ->addChild('organization-index', [
                'label' => $this->translator->trans('Organizations'),
                'route' => 'organization-index',
            ]);
        /*
        $menu['_lookup']
            ->addChild('event-index', [
                'label' => $this->translator->trans('Epochs and Events'),
                'route' => 'event-index',
            ]);
        */
        $menu['_lookup']
            ->addChild('bibliography-index', [
                'label' => $this->translator->trans('Bibliography'),
                'route' => 'bibliography-index',
            ]);

        /*
        // the following is currently not yet in use
        $menu['_lookup']
            ->addChild('glossary-index', [
                'label' => $this->translator->trans('Glossary'),
                'route' => 'glossary-index',
            ]);
        */

        if (array_key_exists('position', $options) && 'footer' == $options['position']) {
        }
        else {
            // $menu['topic-index']->setAttribute('id', 'menu-item-topic');
            // $menu['date-chronology']->setAttribute('id', 'menu-item-chronology');
            $menu['place-map']->setAttribute('id', 'menu-item-map');
            $menu['article-index']->setAttribute('id', 'menu-article-index');
            $menu['_lookup']->setAttribute('id', 'menu-item-lookup');

            // find the matching parent
            // TODO: maybe use a voter
            $uriCurrent = $this->requestStack->getCurrentRequest()->getRequestUri();

            // create the iterator
            $itemIterator = new \Knp\Menu\Iterator\RecursiveItemIterator($menu);

            // iterate recursively on the iterator
            $iterator = new \RecursiveIteratorIterator($itemIterator, \RecursiveIteratorIterator::SELF_FIRST);

            foreach ($iterator as $item) {
                $uri = $item->getUri();
                if (substr($uriCurrent, 0, strlen($uri)) === $uri) {
                    $item->setCurrent(true);
                    break;
                }
            }
        }

        return $menu;
    }

    public function createBreadcrumbMenu(array $options): ItemInterface
    {
        $menu = $this->createMainMenu($options + [ 'position' => 'breadcrumb' ]);

        // try to return the active item
        $currentRoute = $this->requestStack->getCurrentRequest()->get('_route');

        if (is_null($currentRoute) || 'home' == $currentRoute) {
            // $currentRoute is null on error pages, e.g. 404
            return $menu;
        }

        // first level
        $item = $menu[$currentRoute];
        if (isset($item)) {
            return $item;
        }

        // additional routes
        switch ($currentRoute) {
            case 'about':
            case 'terms':
            case 'contact':
                $toplevel = $this->createTopMenu([]);
                $item = $toplevel[$currentRoute];
                $item->setParent(null);
                $item = $menu->addChild($item);
                break;

            case 'article':
            case 'article-pdf':
                $item = $menu->addChild($currentRoute, [ 'label' => 'Article' ]);
                break;

            case 'source':
                $item = $menu->addChild($currentRoute, [ 'label' => 'Source' ]);
                break;

            case 'person-index':
            case 'place-index':
            case 'organization-index':
            case 'event-index':
            case 'bibliography-index':
            case 'glossary-index':
                $item = $menu['_lookup'][$currentRoute];
                break;

            case 'person':
            case 'person-by-gnd':
                $item = $menu['_lookup']['person-index'];
                $item = $item->addChild($currentRoute, [ 'label' => 'Detail', 'uri' => '#' ]);
                break;

            case 'place-map-mentioned':
            case 'place-map-landmark':
                $item = $menu->addChild($currentRoute, [ 'label' => 'Map' ]);
                break;

            case 'place':
            case 'place-by-tgn':
                $item = $menu['_lookup']['place-index'];
                $item = $item->addChild($currentRoute, [ 'label' => 'Detail', 'uri' => '#' ]);
                break;

            case 'organization':
            case 'organization-by-gnd':
                $item = $menu['_lookup']['organization-index'];
                $item = $item->addChild($currentRoute, [ 'label' => 'Detail', 'uri' => '#' ]);
                break;

            case 'event':
            case 'event-by-gnd':
                $item = $menu['_lookup']['event-index'];
                // $item = $item->addChild($currentRoute, [ 'label' => 'Detail', 'uri' => '#' ]);
                break;

            case 'article-index':
            case 'article-index-date':
                $item = $menu['_lookup']['article-index'];
                $item = $item->addChild($currentRoute, [ 'label' => 'Detail', 'uri' => '#' ]);
                break;

            case 'bibliography':
                $item = $menu['_lookup']['bibliography-index'];
                $item = $item->addChild($currentRoute, [ 'label' => 'Detail', 'uri' => '#' ]);
                break;

            case 'search-index':
                $item = $menu->addChild($currentRoute, [ 'label' => 'Search' ]);
                break;
        }

        if (isset($item)) {
            $item->setCurrent(true);

            return $item;
        }

        return $menu;
    }
}
