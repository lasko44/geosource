<?php

namespace App\Services\RAG;

use Illuminate\Support\Arr;

/**
 * Extracts main content from HTML pages, removing boilerplate elements.
 *
 * Removes navigation, headers, footers, sidebars, ads, and other non-content
 * elements to improve embedding quality for RAG retrieval.
 */
class ContentExtractor
{
    /**
     * Elements to completely remove from the HTML.
     *
     * @var array<string>
     */
    private array $removeElements = [
        'script',
        'style',
        'noscript',
        'iframe',
        'svg',
        'canvas',
        'video',
        'audio',
        'form',
        'input',
        'button',
        'select',
        'textarea',
    ];

    /**
     * Semantic elements that typically contain navigation/boilerplate.
     *
     * @var array<string>
     */
    private array $boilerplateElements = [
        'nav',
        'header',
        'footer',
        'aside',
    ];

    /**
     * Class/ID patterns that indicate boilerplate content.
     *
     * @var array<string>
     */
    private array $boilerplatePatterns = [
        'nav',
        'navigation',
        'menu',
        'sidebar',
        'footer',
        'header',
        'breadcrumb',
        'cookie',
        'banner',
        'advertisement',
        'ad-',
        'ads-',
        'social',
        'share',
        'comment',
        'related',
        'recommended',
        'popup',
        'modal',
        'newsletter',
        'subscribe',
        'signup',
        'login',
        'search-form',
        'skip-link',
    ];

    /**
     * Extract main content from HTML.
     */
    public function extract(string $html): string
    {
        // Remove elements that should never be included
        $html = $this->removeUnwantedElements($html);

        // Try to find main content area
        $mainContent = $this->findMainContent($html);

        if ($mainContent !== null && strlen($mainContent) > 200) {
            $html = $mainContent;
        } else {
            // Fall back to removing boilerplate from full HTML
            $html = $this->removeBoilerplate($html);
        }

        // Clean up the extracted content
        return $this->cleanContent($html);
    }

    /**
     * Extract content with metadata about what was extracted.
     *
     * @return array{content: string, title: string|null, headings: array<string>, word_count: int}
     */
    public function extractWithMetadata(string $html): array
    {
        $title = $this->extractTitle($html);
        $headings = $this->extractHeadings($html);
        $content = $this->extract($html);

        return [
            'content' => $content,
            'title' => $title,
            'headings' => $headings,
            'word_count' => str_word_count($content),
        ];
    }

    /**
     * Remove script, style, and other non-content elements.
     */
    private function removeUnwantedElements(string $html): string
    {
        foreach ($this->removeElements as $element) {
            $html = preg_replace(
                '/<'.$element.'\b[^>]*>.*?<\/'.$element.'>/is',
                '',
                $html
            ) ?? $html;

            // Also remove self-closing versions
            $html = preg_replace(
                '/<'.$element.'\b[^>]*\/?>/i',
                '',
                $html
            ) ?? $html;
        }

        // Remove HTML comments
        $html = preg_replace('/<!--.*?-->/s', '', $html) ?? $html;

        return $html;
    }

    /**
     * Try to find the main content area using semantic HTML or common patterns.
     */
    private function findMainContent(string $html): ?string
    {
        // Try semantic <main> element first
        if (preg_match('/<main\b[^>]*>(.*?)<\/main>/is', $html, $match)) {
            return $match[1];
        }

        // Try <article> element
        if (preg_match('/<article\b[^>]*>(.*?)<\/article>/is', $html, $match)) {
            return $match[1];
        }

        // Try common content container patterns
        $contentPatterns = [
            '/<div[^>]*\b(?:id|class)\s*=\s*["\'][^"\']*\b(?:content|main|article|post|entry|page-content|main-content|article-content|post-content)[^"\']*["\'][^>]*>(.*?)<\/div>/is',
            '/<section[^>]*\b(?:id|class)\s*=\s*["\'][^"\']*\b(?:content|main|article)[^"\']*["\'][^>]*>(.*?)<\/section>/is',
        ];

        foreach ($contentPatterns as $pattern) {
            if (preg_match($pattern, $html, $match)) {
                return $match[1];
            }
        }

        return null;
    }

    /**
     * Remove boilerplate elements from HTML.
     */
    private function removeBoilerplate(string $html): string
    {
        // Remove semantic boilerplate elements
        foreach ($this->boilerplateElements as $element) {
            $html = preg_replace(
                '/<'.$element.'\b[^>]*>.*?<\/'.$element.'>/is',
                '',
                $html
            ) ?? $html;
        }

        // Remove elements with boilerplate class/id patterns
        foreach ($this->boilerplatePatterns as $pattern) {
            // Match elements with class or id containing the pattern
            $html = preg_replace(
                '/<[a-z]+[^>]*\b(?:class|id)\s*=\s*["\'][^"\']*\b'.preg_quote($pattern, '/').'\b[^"\']*["\'][^>]*>.*?<\/[a-z]+>/is',
                '',
                $html
            ) ?? $html;
        }

        return $html;
    }

    /**
     * Clean extracted content into plain text.
     */
    private function cleanContent(string $html): string
    {
        // Convert block elements to newlines for structure preservation
        $html = preg_replace('/<\/(p|div|li|tr|h[1-6]|blockquote|section|article)>/i', "\n", $html) ?? $html;
        $html = preg_replace('/<br\s*\/?>/i', "\n", $html) ?? $html;
        $html = preg_replace('/<\/?(ul|ol)>/i', "\n", $html) ?? $html;

        // Add spacing after headings
        $html = preg_replace('/<h([1-6])[^>]*>(.*?)<\/h\1>/is', "\n\n$2\n", $html) ?? $html;

        // Strip remaining tags
        $content = strip_tags($html);

        // Decode HTML entities
        $content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Normalize whitespace
        $content = preg_replace('/[ \t]+/', ' ', $content) ?? $content;
        $content = preg_replace('/\n[ \t]+/', "\n", $content) ?? $content;
        $content = preg_replace('/\n{3,}/', "\n\n", $content) ?? $content;

        return trim($content);
    }

    /**
     * Extract the page title.
     */
    private function extractTitle(string $html): ?string
    {
        // Try <title> tag first
        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $match)) {
            $title = trim(html_entity_decode(strip_tags($match[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if (! empty($title)) {
                return $title;
            }
        }

        // Fall back to <h1>
        if (preg_match('/<h1[^>]*>(.*?)<\/h1>/is', $html, $match)) {
            return trim(html_entity_decode(strip_tags($match[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }

        return null;
    }

    /**
     * Extract all headings from the HTML.
     *
     * @return array<string>
     */
    private function extractHeadings(string $html): array
    {
        $headings = [];

        if (preg_match_all('/<h([1-6])[^>]*>(.*?)<\/h\1>/is', $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $text = trim(html_entity_decode(strip_tags($match[2]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                if (! empty($text) && strlen($text) < 200) {
                    $headings[] = $text;
                }
            }
        }

        return array_slice($headings, 0, 20); // Limit to first 20 headings
    }

    /**
     * Set custom elements to remove.
     *
     * @param  array<string>  $elements
     */
    public function setRemoveElements(array $elements): self
    {
        $this->removeElements = $elements;

        return $this;
    }

    /**
     * Add additional boilerplate patterns to filter.
     *
     * @param  array<string>  $patterns
     */
    public function addBoilerplatePatterns(array $patterns): self
    {
        $this->boilerplatePatterns = array_merge($this->boilerplatePatterns, $patterns);

        return $this;
    }
}
