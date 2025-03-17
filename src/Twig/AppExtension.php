<?php

// src/Twig/AppExtension.php

namespace App\Twig;

use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension
{
    protected $translator;

    /**
     * ingest services
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * setup twig filters
     */
    public function getFilters(): array
    {
        return [
            // site specific
            new TwigFilter('extract_genres', 'App\\Controller\\KeywordController::extractGenres'),
            new TwigFilter('extract_topics', 'App\\Controller\\KeywordController::extractTopics'),
        ];
    }
}
