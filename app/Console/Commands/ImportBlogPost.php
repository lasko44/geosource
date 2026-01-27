<?php

namespace App\Console\Commands;

use App\Models\BlogPost;
use Illuminate\Console\Command;

class ImportBlogPost extends Command
{
    protected $signature = 'blog:import {--slug= : Import a specific blog post by slug}';

    protected $description = 'Import blog posts to production';

    public function handle(): int
    {
        $slug = $this->option('slug');

        if ($slug === 'designing-content-for-ai-snippet-extraction' || ! $slug) {
            $this->importDesigningContentForAiSnippetExtraction();
        }

        return 0;
    }

    protected function importDesigningContentForAiSnippetExtraction(): void
    {
        $slug = 'designing-content-for-ai-snippet-extraction';

        // Check if already exists
        if (BlogPost::where('slug', $slug)->exists()) {
            $this->warn("Blog post '{$slug}' already exists. Skipping.");

            return;
        }

        $content = <<<'MARKDOWN'
## Why AI Snippet Extraction Matters

Traditional SEO optimizes for rankings, keywords, and backlinks. Generative Engine Optimization (GEO) optimizes for answer clarity, semantic structure, and citation confidence.

AI models don't scroll. They don't skim. They don't "discover" content like humans do. **They extract.**

Your job is to make extraction effortless.

| Traditional SEO Optimizes For | GEO Optimizes For |
|------------------------------|-------------------|
| Rankings | Answer clarity |
| Keywords | Semantic structure |
| Backlinks | Citation confidence |

## 1. Use Headings to Create Machine Hierarchy

Headings tell AI what the page is about and how information is organized.

**Best practices:**

- One H1 per page
- Logical H2 → H3 structure
- Never skip heading levels
- Each section should represent a single idea

AI systems favor descriptive, explicit headings that map cleanly to questions.

| Example | Quality | Why |
|---------|---------|-----|
| H2: What is AI snippet extraction? | ✅ Good | Descriptive, question-format |
| H2: Overview | ❌ Bad | Vague, non-specific |

This supports GEO pillars: **Content Clarity**, **Semantic Structure**, and **AI Comprehension**.

### Turn Headings Into Questions

Generative engines are question-driven. They often retrieve content by matching:

- "What is…"
- "How does…"
- "Why does…"

Formatting headings as natural language questions dramatically increases snippet eligibility.

**Example:** H2: How do AI engines extract snippets from webpages?

This mirrors the exact structure AI systems generate internally.

## 2. Design Answer-First Content Blocks

AI prefers content that delivers the answer immediately. Every important section should follow this pattern:

> **Answer → Explanation → Example**

**Example:**

AI snippet extraction is the process by which generative engines identify concise, authoritative text blocks to include directly in AI-generated responses.

Then expand with context and details.

This format supports:

- High-confidence answerability
- Reduced ambiguity
- Easier citation

This is one of the most important GEO pillars — and one most blogs fail entirely.

## 3. Use Short, Declarative Paragraphs

AI models perform best with:

- 2–4 sentence paragraphs
- 50–100 words max
- Clear subject–verb structure

**Avoid:**

- Long narrative blocks
- Storytelling before definitions
- Vague introductions

Write like you're answering a question — not writing an essay.

This improves: **Readability**, **Answer extraction accuracy**, and **Citation confidence**.

## 4. Lists Are AI Gold

Bullet and numbered lists are among the most extractable formats for AI.

**Why?** Because each item is a discrete idea.

AI can safely lift:

- Steps
- Features
- Benefits
- Comparisons

**Example:**

AI-friendly formatting includes:

- Clear headings
- Short paragraphs
- Bullet lists
- Tables
- Semantic HTML

Each line is independently usable — which makes it ideal for AI answers.

Supports GEO pillars: **Structured Information**, **Machine Readability**, and **Answer Formatting**.

## 5. Use Tables for Structured Knowledge

Tables provide explicit relationships between concepts. AI systems love tables because they encode meaning clearly.

**Use tables for:**

- Comparisons
- Feature breakdowns
- Definitions
- Pros vs cons
- GEO vs SEO differences

| Format | Why AI Uses It |
|--------|----------------|
| Headings | Defines topic hierarchy |
| Lists | Extractable points |
| Tables | Structured relationships |

Tables dramatically increase the chance your content is summarized accurately.

## 6. Write Explicit Definitions

AI needs clear definitions to cite confidently.

**A strong definition:**

- Appears near the top of the page
- Is concise (1–2 sentences)
- Avoids marketing language
- States exactly what something is

| Example | Quality |
|---------|---------|
| GEO is a revolutionary approach to modern visibility. | ❌ Bad — vague marketing |
| Generative Engine Optimization (GEO) is the practice of structuring content so AI search engines can accurately extract, understand, and cite it in generated answers. | ✅ Good — clear, quotable |

Definitions are one of the most commonly extracted snippet types.

## 7. Use Semantic HTML Where Possible

AI crawlers rely on semantic structure. Whenever possible:

- Use `<article>` and `<section>`
- Use definition lists (`<dl>`, `<dt>`, `<dd>`)
- Avoid div-only layouts for core content

Semantic HTML reinforces meaning.

This supports: **AI crawler comprehension**, **Structured retrieval**, and **Reduced misinterpretation**.

## 8. Add Schema Markup Strategically

Schema doesn't guarantee citation — but it reduces ambiguity.

**High-value schema for GEO:**

- Article
- FAQPage
- HowTo (when applicable)
- BreadcrumbList

Schema supports the GEO pillar: **Machine-Readable Formatting**.

Think of schema as metadata that tells AI: "Here's what this content actually represents."

## 9. Include FAQ Sections

FAQ sections are extremely GEO-friendly because they:

- Mirror AI question structures
- Provide clean answer blocks
- Work well with schema
- Cover long-tail prompts

**Example:**

**Q: Why do AI engines prefer structured content?**

A: Structured content reduces ambiguity and allows generative systems to extract answers with higher confidence.

This directly improves: **Question coverage**, **Prompt matching**, and **Citation likelihood**.

## 10. Link Internally to Build Topic Authority

AI doesn't evaluate pages in isolation. It evaluates:

- Topical depth
- Semantic connections
- Internal consistency

Link related articles together using descriptive anchor text.

This strengthens: **Topical authority**, **Contextual relevance**, and **Long-form trust signals**.

A strong internal content cluster makes your site appear knowledge-dense — a major GEO advantage.

## 11. Include Credible External Sources

AI engines prioritize content that references:

- Research
- Documentation
- Authoritative publications

Add a clear **Sources** or **References** section when appropriate.

This reinforces: **Trustworthiness**, **Factual grounding**, and **Citation confidence**.

Even when AI doesn't quote the source directly, it boosts your page's reliability score.

## 12. Design for Extraction, Not Ranking

The biggest mindset shift in GEO is this:

> **You are not writing to rank. You are writing to be used.**

If AI can:

- Find the answer instantly
- Understand it clearly
- Trust it confidently

You become citable. That's how visibility works in AI search.

## GEO-Optimized Content Design Checklist

Before publishing, ask:

- ✅ Is the main question clearly answered?
- ✅ Are definitions explicit?
- ✅ Are headings descriptive?
- ✅ Are lists and tables used where appropriate?
- ✅ Are paragraphs short and clear?
- ✅ Does each section stand alone?
- ✅ Would an AI safely quote this text?

If yes — you're GEO-ready.

## Final Thoughts

AI search rewards clarity, structure, and intent — not clever writing.

Designing content for snippet extraction isn't about gaming algorithms. It's about making your knowledge usable by machines.

**When your content is easy to extract, it becomes easy to cite.**

And in the age of AI search, citations are the new rankings.
MARKDOWN;

        $faq = [
            [
                'question' => 'What is AI snippet extraction?',
                'answer' => 'AI snippet extraction is the process by which generative AI engines identify concise, authoritative text blocks from web content to include directly in AI-generated responses. Content must be structured for extraction to be cited.',
            ],
            [
                'question' => 'How do I structure content for AI extraction?',
                'answer' => 'Structure content using descriptive headings, short paragraphs (50-100 words), bullet lists, tables, and explicit definitions. Follow the answer-first pattern: Answer → Explanation → Example. Use semantic HTML and schema markup.',
            ],
            [
                'question' => 'Why are lists good for AI extraction?',
                'answer' => 'Bullet and numbered lists are among the most extractable formats for AI because each item is a discrete idea. AI can safely lift individual points without taking them out of context.',
            ],
            [
                'question' => 'What makes a definition quotable by AI?',
                'answer' => 'A quotable definition appears near the top of the page, is concise (1-2 sentences), avoids marketing language, and states exactly what something is using the "X is..." format.',
            ],
        ];

        $quickLinks = [
            ['title' => 'Why AI Snippet Extraction Matters', 'anchor' => 'why-ai-snippet-extraction-matters'],
            ['title' => 'Use Headings to Create Machine Hierarchy', 'anchor' => '1-use-headings-to-create-machine-hierarchy'],
            ['title' => 'Design Answer-First Content Blocks', 'anchor' => '2-design-answer-first-content-blocks'],
            ['title' => 'Use Short, Declarative Paragraphs', 'anchor' => '3-use-short-declarative-paragraphs'],
            ['title' => 'Lists Are AI Gold', 'anchor' => '4-lists-are-ai-gold'],
            ['title' => 'Use Tables for Structured Knowledge', 'anchor' => '5-use-tables-for-structured-knowledge'],
            ['title' => 'Write Explicit Definitions', 'anchor' => '6-write-explicit-definitions'],
            ['title' => 'Use Semantic HTML', 'anchor' => '7-use-semantic-html-where-possible'],
            ['title' => 'Add Schema Markup', 'anchor' => '8-add-schema-markup-strategically'],
            ['title' => 'Include FAQ Sections', 'anchor' => '9-include-faq-sections'],
            ['title' => 'Link Internally', 'anchor' => '10-link-internally-to-build-topic-authority'],
            ['title' => 'Include Credible Sources', 'anchor' => '11-include-credible-external-sources'],
            ['title' => 'Design for Extraction', 'anchor' => '12-design-for-extraction-not-ranking'],
        ];

        $schemaJson = [
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            'headline' => 'Designing Content for AI Snippet Extraction: Headings, Lists, Tables & More',
            'description' => 'Learn how to structure headings, lists, tables, and definitions so AI search engines can extract, understand, and cite your content.',
            'url' => 'https://geosource.ai/blog/designing-content-for-ai-snippet-extraction',
            'datePublished' => '2026-01-27',
            'dateModified' => '2026-01-27',
            'author' => [
                '@type' => 'Organization',
                'name' => 'GeoSource.ai',
                'url' => 'https://geosource.ai',
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => 'GeoSource.ai',
                'url' => 'https://geosource.ai',
            ],
            'image' => 'https://geosource.ai/images/blog/designing-content-for-ai-snippet-extraction.png',
            'about' => [
                '@type' => 'DefinedTerm',
                'name' => 'AI Snippet Extraction',
                'description' => 'The process by which generative AI engines identify concise, authoritative text blocks from web content to include directly in AI-generated responses.',
            ],
        ];

        BlogPost::create([
            'title' => 'Designing Content for AI Snippet Extraction: Headings, Lists, Tables & More',
            'slug' => $slug,
            'excerpt' => 'AI search engines don\'t rank pages — they extract answers. Learn how to structure headings, lists, tables, and definitions so AI systems can confidently cite your content.',
            'content' => $content,
            'featured_image' => '/images/blog/designing-content-for-ai-snippet-extraction.png',
            'meta_title' => 'Designing Content for AI Snippet Extraction | GEO Guide',
            'meta_description' => 'Learn how to structure content for AI extraction. Covers headings, lists, tables, definitions, schema markup, and FAQ sections for maximum GEO visibility.',
            'schema_json' => $schemaJson,
            'tags' => ['GEO', 'Content Strategy', 'AI Search', 'Optimization'],
            'faq' => $faq,
            'quick_links' => $quickLinks,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->info("✓ Imported blog post: {$slug}");
    }
}
