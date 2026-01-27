<?php

namespace App\Console\Commands;

use App\Models\Documentation;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ImportDocumentation extends Command
{
    protected $signature = 'docs:import {--fresh : Clear existing documentation before importing}';

    protected $description = 'Import documentation from the original HTML file into the database';

    public function handle(): int
    {
        // Get original file from git
        $originalHtml = shell_exec('git show HEAD~1:resources/views/nova/documentation.blade.php 2>/dev/null');

        if (! $originalHtml) {
            $this->error('Could not retrieve original documentation from git history.');
            $this->info('Please ensure the original file exists in git history.');

            return self::FAILURE;
        }

        if ($this->option('fresh')) {
            Documentation::truncate();
            $this->info('Cleared existing documentation.');
        }

        $sections = $this->parseSections($originalHtml);

        $this->info('Found ' . count($sections) . ' sections to import.');

        $bar = $this->output->createProgressBar(count($sections));
        $bar->start();

        foreach ($sections as $index => $section) {
            Documentation::updateOrCreate(
                ['slug' => $section['slug']],
                [
                    'title' => $section['title'],
                    'category' => $section['category'],
                    'category_slug' => Str::slug($section['category']),
                    'excerpt' => $section['excerpt'] ?? null,
                    'content' => $section['content'],
                    'sort_order' => $index,
                    'is_published' => true,
                ]
            );

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Documentation imported successfully!');

        return self::SUCCESS;
    }

    private function parseSections(string $html): array
    {
        $sections = [];

        // Define the section mapping from the original file
        $sectionMap = [
            'overview' => ['title' => 'Overview', 'category' => 'Getting Started'],
            'env-vars' => ['title' => 'Environment Variables', 'category' => 'Getting Started'],
            'geo-scoring' => ['title' => 'GEO Scoring System', 'category' => 'GEO Scoring System'],
            'scoring-pillars' => ['title' => 'Scoring Pillars', 'category' => 'GEO Scoring System'],
            'scorer-reference' => ['title' => 'Scorer Technical Reference', 'category' => 'GEO Scoring System'],
            'rag-embeddings' => ['title' => 'RAG & Embeddings', 'category' => 'GEO Scoring System'],
            'rag-reference' => ['title' => 'RAG Technical Reference', 'category' => 'GEO Scoring System'],
            'jobs' => ['title' => 'Jobs & Queue', 'category' => 'Jobs & Queue'],
            'services' => ['title' => 'Key Services', 'category' => 'Key Services'],
            'models' => ['title' => 'Database Models', 'category' => 'Database Models'],
            'billing' => ['title' => 'Billing & Subscriptions', 'category' => 'Billing & Subscriptions'],
            'citation-tracking' => ['title' => 'Citation Tracking', 'category' => 'Citation Tracking'],
            'citation-models' => ['title' => 'Citation Models', 'category' => 'Citation Tracking'],
            'citation-services' => ['title' => 'Citation Services', 'category' => 'Citation Tracking'],
            'citation-jobs' => ['title' => 'Citation Jobs', 'category' => 'Citation Tracking'],
            'debugging' => ['title' => 'Debugging Guide', 'category' => 'Debugging Guide'],
            'security' => ['title' => 'Security Precautions', 'category' => 'Security'],
            'blog-management' => ['title' => 'Blog Management', 'category' => 'Content Management'],
            'marketing-emails' => ['title' => 'Marketing Emails', 'category' => 'Content Management'],
        ];

        // Extract each section
        $ids = array_keys($sectionMap);

        for ($i = 0; $i < count($ids); $i++) {
            $currentId = $ids[$i];
            $nextId = $ids[$i + 1] ?? null;

            // Find content between sections
            $startPattern = 'id="' . $currentId . '"';
            $startPos = strpos($html, $startPattern);

            if ($startPos === false) {
                continue;
            }

            // Find the start of the h2 tag
            $h2StartPos = strrpos(substr($html, 0, $startPos), '<h2');
            if ($h2StartPos === false) {
                continue;
            }

            // Find the end of this section (start of next h2 or end of content)
            if ($nextId) {
                $nextPattern = 'id="' . $nextId . '"';
                $nextPos = strpos($html, $nextPattern, $startPos);
                if ($nextPos !== false) {
                    // Find the h2 tag that contains this id
                    $endPos = strrpos(substr($html, 0, $nextPos), '<h2');
                } else {
                    $endPos = strpos($html, '</div>', $startPos + 1000);
                }
            } else {
                // Last section - find closing tags
                $endPos = strpos($html, '</main>', $startPos);
                if ($endPos === false) {
                    $endPos = strlen($html);
                }
            }

            $sectionContent = substr($html, $h2StartPos, $endPos - $h2StartPos);

            // Clean up the content
            $content = $this->cleanContent($sectionContent, $sectionMap[$currentId]['title']);

            if (! empty(trim(strip_tags($content)))) {
                $sections[] = [
                    'slug' => $currentId,
                    'title' => $sectionMap[$currentId]['title'],
                    'category' => $sectionMap[$currentId]['category'],
                    'content' => $content,
                ];
            }
        }

        return $sections;
    }

    private function cleanContent(string $content, string $title): string
    {
        // Remove the h2 heading (we display title separately)
        $content = preg_replace('/<h2[^>]*>.*?<\/h2>/s', '', $content, 1);

        // Remove any leading/trailing whitespace
        $content = trim($content);

        // Fix class attributes that might break styling
        $content = preg_replace('/class="[^"]*text-gray-900[^"]*"/', '', $content);
        $content = preg_replace('/class="[^"]*border-b[^"]*"/', '', $content);

        return $content;
    }
}
