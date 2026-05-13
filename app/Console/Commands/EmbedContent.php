<?php

namespace App\Console\Commands;

use App\Models\ContentEmbedding;
use App\Services\RAG\EmbeddingService;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;

/**
 * Embeds all public content pages (resources + programmatic SEO) into
 * the content_embeddings table for RAG-powered suggested content.
 */
class EmbedContent extends Command
{
    protected $signature = 'content:embed {--fresh : Clear existing embeddings before importing}';

    protected $description = 'Generate embeddings for all public content pages for suggested content';

    public function handle(EmbeddingService $embeddingService): int
    {
        if ($this->option('fresh')) {
            ContentEmbedding::query()->delete();
            $this->info('Cleared existing content embeddings.');
        }

        $pages = $this->gatherAllPages();

        $this->info("Embedding {$pages->count()} content pages...");

        $bar = $this->output->createProgressBar($pages->count());
        $bar->start();

        $embedded = 0;

        foreach ($pages as $page) {
            $slug = Arr::get($page, 'slug');
            $textToEmbed = Arr::get($page, 'title').' — '.Arr::get($page, 'excerpt');

            $record = ContentEmbedding::updateOrCreate(
                ['slug' => $slug],
                [
                    'type' => Arr::get($page, 'type'),
                    'title' => Arr::get($page, 'title'),
                    'url' => Arr::get($page, 'url'),
                    'excerpt' => Arr::get($page, 'excerpt'),
                    'metadata' => Arr::get($page, 'metadata', []),
                ]
            );

            try {
                $vector = $embeddingService->embed($textToEmbed, cache: true);
                $record->setEmbedding($vector);
                $embedded++;
            } catch (\Throwable $e) {
                $this->warn("  Failed to embed '{$slug}': {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Done. Embedded {$embedded}/{$pages->count()} pages.");

        return self::SUCCESS;
    }

    /**
     * Gather all public content pages into a unified collection.
     */
    private function gatherAllPages(): \Illuminate\Support\Collection
    {
        $pages = collect();

        // Learning resources (static pages)
        foreach ($this->getResourcePages() as $resource) {
            $pages->push($resource);
        }

        // Industry GEO pages
        foreach (config('programmatic-seo.industries', []) as $industry) {
            $pages->push([
                'slug' => 'geo-for-'.Arr::get($industry, 'slug'),
                'type' => 'industry',
                'title' => Arr::get($industry, 'title'),
                'url' => '/geo-for-'.Arr::get($industry, 'slug'),
                'excerpt' => Arr::get($industry, 'hero_description'),
                'metadata' => ['industry' => Arr::get($industry, 'slug')],
            ]);
        }

        // Platform optimization pages
        foreach (config('programmatic-seo.platforms', []) as $platform) {
            $pages->push([
                'slug' => 'optimize-for-'.Arr::get($platform, 'slug'),
                'type' => 'platform',
                'title' => Arr::get($platform, 'title'),
                'url' => '/optimize-for-'.Arr::get($platform, 'slug'),
                'excerpt' => Arr::get($platform, 'hero_description'),
                'metadata' => ['platform' => Arr::get($platform, 'slug')],
            ]);
        }

        // Comparison pages
        foreach (config('programmatic-seo.comparisons', []) as $comparison) {
            $pages->push([
                'slug' => 'compare-'.Arr::get($comparison, 'slug'),
                'type' => 'comparison',
                'title' => Arr::get($comparison, 'title'),
                'url' => '/compare/'.Arr::get($comparison, 'slug'),
                'excerpt' => Arr::get($comparison, 'when_to_use_a').' '.Arr::get($comparison, 'why_both'),
                'metadata' => [
                    'tool_a' => Arr::get($comparison, 'tool_a'),
                    'tool_b' => Arr::get($comparison, 'tool_b'),
                ],
            ]);
        }

        return $pages;
    }

    /**
     * Static resource pages with their descriptions for embedding.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getResourcePages(): array
    {
        return [
            ['slug' => 'resource-what-is-geo', 'type' => 'resource', 'title' => 'What Is Generative Engine Optimization (GEO)?', 'url' => '/resources/what-is-geo', 'excerpt' => 'Learn the definition, core principles, and goals of GEO — the practice of optimizing content for AI systems like ChatGPT, Perplexity, and Claude.'],
            ['slug' => 'resource-geo-vs-seo', 'type' => 'resource', 'title' => 'GEO vs SEO: What\'s the Difference?', 'url' => '/resources/geo-vs-seo', 'excerpt' => 'Understand the key differences between traditional SEO and Generative Engine Optimization. SEO ranks pages, GEO gets content cited by AI.'],
            ['slug' => 'resource-how-ai-search-works', 'type' => 'resource', 'title' => 'How AI Search Engines Actually Work', 'url' => '/resources/how-ai-search-works', 'excerpt' => 'Explore the mechanics of generative AI search including RAG, vector embeddings, and source selection used by ChatGPT and Perplexity.'],
            ['slug' => 'resource-how-llms-cite-sources', 'type' => 'resource', 'title' => 'How Large Language Models Choose Which Sources to Cite', 'url' => '/resources/how-llms-cite-sources', 'excerpt' => 'Discover the signals LLMs use to select high-confidence sources for citations including authority, structure, and definitions.'],
            ['slug' => 'resource-what-is-a-geo-score', 'type' => 'resource', 'title' => 'What Is a GEO Score?', 'url' => '/resources/what-is-a-geo-score', 'excerpt' => 'Learn how GEO Scores measure AI comprehension readiness across 12 pillars and what factors are evaluated by AI systems.'],
            ['slug' => 'resource-geo-content-framework', 'type' => 'resource', 'title' => 'The GeoSource.ai GEO Content Framework', 'url' => '/resources/geo-content-framework', 'excerpt' => 'A structured framework for creating content optimized for generative AI systems, covering definitions, structure, and authority.'],
            ['slug' => 'resource-why-llms-txt-matters', 'type' => 'resource', 'title' => 'Why llms.txt Matters for GEO', 'url' => '/resources/why-llms-txt-matters', 'excerpt' => 'Learn how llms.txt files help AI systems understand, discover, and cite your website content for better AI visibility.'],
            ['slug' => 'resource-why-ssr-matters-for-geo', 'type' => 'resource', 'title' => 'Why Server-Side Rendering (SSR) Matters for GEO', 'url' => '/resources/why-ssr-matters-for-geo', 'excerpt' => 'Understand why SSR is essential for AI visibility and how LLMs access your content through server-rendered HTML.'],
            ['slug' => 'resource-e-e-a-t-and-geo', 'type' => 'resource', 'title' => 'E-E-A-T and GEO: Building Trust for AI Visibility', 'url' => '/resources/e-e-a-t-and-geo', 'excerpt' => 'Learn how Experience, Expertise, Authoritativeness, and Trustworthiness influence AI citation decisions.'],
            ['slug' => 'resource-ai-citations-and-geo', 'type' => 'resource', 'title' => 'AI Citations and GEO: Getting Cited by LLMs', 'url' => '/resources/ai-citations-and-geo', 'excerpt' => 'Discover how to optimize your content structure to become a preferred citation source for AI systems.'],
            ['slug' => 'resource-ai-accessibility-for-geo', 'type' => 'resource', 'title' => 'AI Accessibility for GEO: Making Content Machine-Readable', 'url' => '/resources/ai-accessibility-for-geo', 'excerpt' => 'Ensure your content is technically accessible and easily consumable by AI crawlers and LLMs.'],
            ['slug' => 'resource-content-freshness-for-geo', 'type' => 'resource', 'title' => 'Content Freshness for GEO: Why Recency Matters', 'url' => '/resources/content-freshness-for-geo', 'excerpt' => 'Understand how content freshness and regular updates impact your visibility in AI-generated responses.'],
            ['slug' => 'resource-readability-and-geo', 'type' => 'resource', 'title' => 'Readability and GEO: Writing for AI Comprehension', 'url' => '/resources/readability-and-geo', 'excerpt' => 'Learn how clear, structured writing helps LLMs understand and accurately represent your content.'],
            ['slug' => 'resource-question-coverage-for-geo', 'type' => 'resource', 'title' => 'Question Coverage for GEO: Answering User Intent', 'url' => '/resources/question-coverage-for-geo', 'excerpt' => 'Optimize your content to directly answer the questions users ask AI search engines.'],
            ['slug' => 'resource-multimedia-and-geo', 'type' => 'resource', 'title' => 'Multimedia and GEO: Beyond Text Content', 'url' => '/resources/multimedia-and-geo', 'excerpt' => 'Learn how images, videos, and other media can enhance your GEO through proper optimization.'],
            ['slug' => 'resource-definitions', 'type' => 'resource', 'title' => 'GEO Definitions Glossary', 'url' => '/definitions', 'excerpt' => 'Official glossary of Generative Engine Optimization terminology. Definitions for GEO, AI search, citation, and content optimization terms.'],
            ['slug' => 'resource-geo-score-explained', 'type' => 'resource', 'title' => 'GEO Score Explained', 'url' => '/geo-score-explained', 'excerpt' => 'Deep dive into how GEO scoring works, what the 12 pillars measure, and how to improve your AI search readiness score.'],
            ['slug' => 'resource-geo-optimization-checklist', 'type' => 'resource', 'title' => 'GEO Optimization Checklist', 'url' => '/geo-optimization-checklist', 'excerpt' => 'Step-by-step checklist for optimizing your content for AI citation across all 12 GEO pillars.'],
            ['slug' => 'resource-ai-search-visibility-guide', 'type' => 'resource', 'title' => 'AI Search Visibility Guide', 'url' => '/ai-search-visibility-guide', 'excerpt' => 'Comprehensive guide to understanding and improving your visibility in AI-generated search answers.'],
        ];
    }
}
