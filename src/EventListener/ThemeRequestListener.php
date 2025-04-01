<?php

// see https://github.com/Sylius/SyliusThemeBundle/blob/master/docs/your_first_theme.md

namespace App\EventListener;

use Novaway\Bundle\FeatureFlagBundle\Manager\FeatureManager;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Sylius\Bundle\ThemeBundle\Context\SettableThemeContext;
use Sylius\Bundle\ThemeBundle\Repository\ThemeRepositoryInterface;

class ThemeRequestListener
{
    /** @var ThemeRepositoryInterface */
    private $themeRepository;

    /** @var SettableThemeContext */
    private $themeContext;

    /** @var FeatureManager|null */
    private $featureManager;

    private $siteTheme = 'mmz-potsdam/diaspora-site';

    public function __construct(
        ThemeRepositoryInterface $themeRepository,
        SettableThemeContext $themeContext,
        ?FeatureManager $featureManager = null
    ) {
        $this->themeRepository = $themeRepository;
        $this->themeContext = $themeContext;
        $this->featureManager = $featureManager;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            // don't do anything if it's not the master request
            return;
        }

        if (is_null($this->featureManager) || $this->featureManager->isEnabled('limited_navigation')) {
            // go with the default theme
            return;
        }

        $theme = $this->themeRepository->findOneByName($this->siteTheme);
        if (!is_null($theme)) {
            $this->themeContext->setTheme($theme);
        }
    }
}
