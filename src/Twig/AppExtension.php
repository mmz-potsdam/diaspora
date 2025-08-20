<?php

// src/Twig/AppExtension.php

namespace App\Twig;

use League\CommonMark\ConverterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension
{
    protected TranslatorInterface $translator;
    protected ConverterInterface $markdownConverter;

    /**
     * ingest services
     */
    public function __construct(TranslatorInterface $translator, ConverterInterface $markdownConverter)
    {
        $this->translator = $translator;
        $this->markdownConverter = $markdownConverter;
    }

    /**
     * setup twig filters
     */
    public function getFilters(): array
    {
        return [
            // minimal markdown for person bios
            new TwigFilter('markdown_to_html', [$this, 'convertMarkdownToHtml'], ['is_safe' => ['all']]),

            // site specific
            new TwigFilter('extract_genres', 'App\\Controller\\KeywordController::extractGenres'),
            new TwigFilter('extract_topics', 'App\\Controller\\KeywordController::extractTopics'),
        ];
    }

    public function convertMarkdownToHtml(?string $markdown): string
    {
        if (is_null($markdown)) {
            return '';
        }

        return $this->markdownConverter->convert($markdown);
    }
}
