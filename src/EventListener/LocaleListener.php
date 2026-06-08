<?php

/*
 * This file is derived of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventListener;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\Routing\RequestContextAwareInterface;

/**
 * Initializes the locale based on the current request.
 *
 * See https://raw.githubusercontent.com/symfony/symfony/refs/heads/7.4/src/Symfony/Component/HttpKernel/EventListener/LocaleListener.php
 */
class LocaleListener extends \Symfony\Component\HttpKernel\EventListener\LocaleListener
{
    public function __construct(
        private RequestStack $requestStack,
        private string $defaultLocale = 'en',
        private ?RequestContextAwareInterface $router = null,
        private bool $useAcceptLanguageHeader = false,
        private array $enabledLocales = [],
    ) {
        parent::__construct($requestStack, $defaultLocale, $router, $useAcceptLanguageHeader, $enabledLocales);
        $this->enabledLocales = $enabledLocales ? array_values(array_unique(array_merge([$defaultLocale], $enabledLocales))) : [];
    }

    public function setDefaultLocale(KernelEvent $event): void
    {
        $event->getRequest()->setDefaultLocale($this->defaultLocale);
        // the following call introduced in
        // https://github.com/symfony/symfony/commit/d70f4c2bd5bcd3a4461679d74165f2d369cdec1d#diff-538548f30cc4d81be08192f50877d6417b2fb33c01dea927789482e8af79c127
        // breaks host-based locale selection in JMSI18nRoutingBundle,
        // so we ignore it
        // $this->setRouterLocale($this->defaultLocale);
    }
}
