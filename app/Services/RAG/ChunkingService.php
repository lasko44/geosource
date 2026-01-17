<?php

namespace App\Services\RAG;

/**
 * Service for intelligently chunking content for RAG.
 *
 * Supports multiple strategies:
 * - Semantic (by headings/sections)
 * - Fixed size with overlap
 * - Sentence-based
 * - Paragraph-based
 */
class ChunkingService
{
    private int $chunkSize;

    private int $chunkOverlap;

    private string $strategy;

    public function __construct()
    {
        $this->chunkSize = config('rag.chunking.size', 1000);
        $this->chunkOverlap = config('rag.chunking.overlap', 200);
        $this->strategy = config('rag.chunking.strategy', 'semantic');
    }

    /**
     * Chunk content using configured strategy.
     *
     * @return array<array{content: string, metadata: array}>
     */
    public function chunk(string $content, array $metadata = []): array
    {
        return match ($this->strategy) {
            'semantic' => $this->semanticChunk($content, $metadata),
            'fixed' => $this->fixedChunk($content, $metadata),
            'sentence' => $this->sentenceChunk($content, $metadata),
            'paragraph' => $this->paragraphChunk($content, $metadata),
            default => $this->semanticChunk($content, $metadata),
        };
    }

    /**
     * Chunk by semantic sections (headings).
     */
    public function semanticChunk(string $content, array $metadata = []): array
    {
        $chunks = [];
        $plainText = $this->stripHtml($content);

        // Split by headings while preserving them
        $sections = $this->splitByHeadings($content);

        foreach ($sections as $index => $section) {
            $sectionText = $this->stripHtml($section['content']);
            $sectionLength = strlen($sectionText);

            // If section is small enough, keep as single chunk
            if ($sectionLength <= $this->chunkSize) {
                if (strlen(trim($sectionText)) > 50) {
                    $chunks[] = [
                        'content' => trim($sectionText),
                        'metadata' => array_merge($metadata, [
                            'chunk_index' => count($chunks),
                            'section_heading' => $section['heading'] ?? null,
                            'section_level' => $section['level'] ?? null,
                            'chunk_type' => 'section',
                        ]),
                    ];
                }
            } else {
                // Split large sections into smaller chunks with overlap
                $subChunks = $this->splitWithOverlap($sectionText);
                foreach ($subChunks as $subIndex => $subChunk) {
                    $chunks[] = [
                        'content' => trim($subChunk),
                        'metadata' => array_merge($metadata, [
                            'chunk_index' => count($chunks),
                            'section_heading' => $section['heading'] ?? null,
                            'section_level' => $section['level'] ?? null,
                            'sub_chunk_index' => $subIndex,
                            'chunk_type' => 'section_part',
                        ]),
                    ];
                }
            }
        }

        // If no sections found, fall back to fixed chunking
        if (empty($chunks)) {
            return $this->fixedChunk($content, $metadata);
        }

        return $chunks;
    }

    /**
     * Chunk by fixed size with overlap.
     */
    public function fixedChunk(string $content, array $metadata = []): array
    {
        $plainText = $this->stripHtml($content);
        $subChunks = $this->splitWithOverlap($plainText);
        $chunks = [];

        foreach ($subChunks as $index => $chunk) {
            if (strlen(trim($chunk)) > 50) {
                $chunks[] = [
                    'content' => trim($chunk),
                    'metadata' => array_merge($metadata, [
                        'chunk_index' => $index,
                        'chunk_type' => 'fixed',
                    ]),
                ];
            }
        }

        return $chunks;
    }

    /**
     * Chunk by sentences, grouping to target size.
     */
    public function sentenceChunk(string $content, array $metadata = []): array
    {
        $plainText = $this->stripHtml($content);
        $sentences = $this->splitIntoSentences($plainText);
        $chunks = [];
        $currentChunk = '';
        $chunkIndex = 0;

        foreach ($sentences as $sentence) {
            $sentence = trim($sentence);
            if (empty($sentence)) {
                continue;
            }

            // If adding this sentence exceeds chunk size
            if (strlen($currentChunk) + strlen($sentence) > $this->chunkSize) {
                if (! empty($currentChunk)) {
                    $chunks[] = [
                        'content' => trim($currentChunk),
                        'metadata' => array_merge($metadata, [
                            'chunk_index' => $chunkIndex++,
                            'chunk_type' => 'sentence',
                        ]),
                    ];
                }
                $currentChunk = $sentence.' ';
            } else {
                $currentChunk .= $sentence.' ';
            }
        }

        // Add remaining content
        if (strlen(trim($currentChunk)) > 50) {
            $chunks[] = [
                'content' => trim($currentChunk),
                'metadata' => array_merge($metadata, [
                    'chunk_index' => $chunkIndex,
                    'chunk_type' => 'sentence',
                ]),
            ];
        }

        return $chunks;
    }

    /**
     * Chunk by paragraphs.
     */
    public function paragraphChunk(string $content, array $metadata = []): array
    {
        // Split by paragraph tags or double newlines
        $content = preg_replace('/<\/p>\s*<p[^>]*>/i', "\n\n", $content);
        $plainText = $this->stripHtml($content);
        $paragraphs = preg_split('/\n\s*\n/', $plainText, -1, PREG_SPLIT_NO_EMPTY);

        $chunks = [];
        $currentChunk = '';
        $chunkIndex = 0;

        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);
            if (strlen($paragraph) < 30) {
                continue;
            }

            // If adding this paragraph exceeds chunk size
            if (strlen($currentChunk) + strlen($paragraph) > $this->chunkSize) {
                if (! empty($currentChunk)) {
                    $chunks[] = [
                        'content' => trim($currentChunk),
                        'metadata' => array_merge($metadata, [
                            'chunk_index' => $chunkIndex++,
                            'chunk_type' => 'paragraph',
                        ]),
                    ];
                }
                $currentChunk = $paragraph."\n\n";
            } else {
                $currentChunk .= $paragraph."\n\n";
            }
        }

        // Add remaining content
        if (strlen(trim($currentChunk)) > 50) {
            $chunks[] = [
                'content' => trim($currentChunk),
                'metadata' => array_merge($metadata, [
                    'chunk_index' => $chunkIndex,
                    'chunk_type' => 'paragraph',
                ]),
            ];
        }

        return $chunks;
    }

    /**
     * Create a summary chunk from content (for hierarchical retrieval).
     */
    public function createSummaryChunk(string $content, array $metadata = []): array
    {
        $plainText = $this->stripHtml($content);

        // Extract first paragraph and headings as summary
        $summary = '';

        // Get title/h1
        if (preg_match('/<h1[^>]*>(.*?)<\/h1>/is', $content, $match)) {
            $summary .= strip_tags($match[1])."\n\n";
        }

        // Get first paragraph
        $sentences = $this->splitIntoSentences($plainText);
        $firstParagraph = implode(' ', array_slice($sentences, 0, 3));
        $summary .= $firstParagraph;

        // Get all headings for overview
        preg_match_all('/<h[2-3][^>]*>(.*?)<\/h[2-3]>/is', $content, $matches);
        if (! empty($matches[1])) {
            $summary .= "\n\nSections: ".implode(', ', array_map('strip_tags', array_slice($matches[1], 0, 10)));
        }

        return [
            'content' => trim($summary),
            'metadata' => array_merge($metadata, [
                'chunk_type' => 'summary',
                'is_summary' => true,
            ]),
        ];
    }

    /**
     * Split content by headings into sections.
     */
    private function splitByHeadings(string $content): array
    {
        $sections = [];

        // Pattern to match headings
        $pattern = '/<(h[1-6])[^>]*>(.*?)<\/\1>/is';

        // Split content by headings
        $parts = preg_split($pattern, $content, -1, PREG_SPLIT_DELIM_CAPTURE);

        $currentSection = ['heading' => null, 'level' => null, 'content' => ''];

        for ($i = 0; $i < count($parts); $i++) {
            $part = $parts[$i];

            // Check if this is a heading tag (h1-h6)
            if (preg_match('/^h([1-6])$/', $part, $match)) {
                // Save previous section if it has content
                if (! empty(trim($currentSection['content']))) {
                    $sections[] = $currentSection;
                }

                // Start new section
                $headingText = strip_tags($parts[$i + 1] ?? '');
                $currentSection = [
                    'heading' => $headingText,
                    'level' => (int) $match[1],
                    'content' => $headingText."\n\n",
                ];
                $i++; // Skip the heading content part
            } else {
                $currentSection['content'] .= $part;
            }
        }

        // Add final section
        if (! empty(trim($currentSection['content']))) {
            $sections[] = $currentSection;
        }

        return $sections;
    }

    /**
     * Split text with overlap for context preservation.
     */
    private function splitWithOverlap(string $text): array
    {
        $chunks = [];
        $words = preg_split('/\s+/', $text);
        $wordsPerChunk = intval($this->chunkSize / 5); // Rough estimate of 5 chars per word
        $overlapWords = intval($this->chunkOverlap / 5);

        $position = 0;
        while ($position < count($words)) {
            $chunkWords = array_slice($words, $position, $wordsPerChunk);
            $chunk = implode(' ', $chunkWords);

            if (strlen($chunk) > 50) {
                $chunks[] = $chunk;
            }

            $position += $wordsPerChunk - $overlapWords;

            // Prevent infinite loop
            if ($position <= 0) {
                $position = count($words);
            }
        }

        return $chunks;
    }

    /**
     * Split text into sentences.
     */
    private function splitIntoSentences(string $text): array
    {
        // Split on sentence boundaries
        $sentences = preg_split(
            '/(?<=[.!?])\s+(?=[A-Z])/',
            $text,
            -1,
            PREG_SPLIT_NO_EMPTY
        );

        return array_filter($sentences, fn ($s) => strlen(trim($s)) > 10);
    }

    /**
     * Strip HTML tags and clean text.
     */
    private function stripHtml(string $content): string
    {
        // Remove script and style elements
        $content = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $content);
        $content = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $content);

        // Convert some tags to newlines for structure
        $content = preg_replace('/<\/(p|div|li|tr|h[1-6])>/i', "\n", $content);
        $content = preg_replace('/<br\s*\/?>/i', "\n", $content);

        // Strip remaining tags
        $content = strip_tags($content);

        // Decode entities
        $content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Normalize whitespace
        $content = preg_replace('/[ \t]+/', ' ', $content);
        $content = preg_replace('/\n\s*\n/', "\n\n", $content);

        return trim($content);
    }

    /**
     * Set chunk size.
     */
    public function setChunkSize(int $size): self
    {
        $this->chunkSize = $size;

        return $this;
    }

    /**
     * Set overlap size.
     */
    public function setOverlap(int $overlap): self
    {
        $this->chunkOverlap = $overlap;

        return $this;
    }

    /**
     * Set chunking strategy.
     */
    public function setStrategy(string $strategy): self
    {
        $this->strategy = $strategy;

        return $this;
    }
}
