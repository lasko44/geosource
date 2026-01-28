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

        if ($slug === 'ai-search-is-stealing-your-traffic' || ! $slug) {
            $this->importAiSearchIsStealingYourTraffic();
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

    protected function importAiSearchIsStealingYourTraffic(): void
    {
        //comment
        $slug = 'ai-search-is-stealing-your-traffic';

        // Check if already exists
        if (BlogPost::where('slug', $slug)->exists()) {
            $this->warn("Blog post '{$slug}' already exists. Skipping.");

            return;
        }

        $content = <<<'MARKDOWN'
Let's say the quiet part out loud.

**AI search is already taking traffic away from websites.**

Not in the future. Not "eventually." Right now.

And most marketing teams are pretending it's not happening.

---

## The Click Is Disappearing

For years, the content playbook was simple:

**Write → Rank → Get Clicks**

That loop is breaking.

When users ask ChatGPT, Gemini, or Perplexity a question, they don't see ten results. They see one synthesized answer.

That answer often replaces the click entirely.

And when it doesn't? Only a few sources get cited.

**Everyone else gets nothing.**

---

## This Isn't a Google Update — It's a New Interface

SEO professionals keep treating AI search like another algorithm change.

It's not.

| Google Updates | AI Search |
|----------------|-----------|
| Tweak rankings | Change behavior |
| Adjust signals | Replace interface |
| Reward optimization | Reward clarity |

The interface itself has changed.

Users aren't searching — they're asking.

And the system responding doesn't care who ranks #1. **It cares who explains the topic best.**

---

## "But My Content Is Good"

That's what everyone says.

And they're probably right.

The problem isn't quality. **The problem is that AI systems don't reward effort — they reward clarity.**

If your page:

- Buries the answer
- Mixes multiple intents
- Rambles before explaining
- Lacks structure
- Assumes human context

...the model won't touch it.

Not because it's wrong — **because it's risky.**

AI engines avoid uncertainty. They cite what they can confidently extract.

---

## AI Doesn't Want Your Blog Post — It Wants Your Explanation

This is the mindset shift most marketers miss.

AI isn't browsing your article. It's scanning for:

- **Definitions** — What is X?
- **Relationships** — How does X relate to Y?
- **Explanations** — Why does X matter?
- **Structured ideas** — Steps, lists, comparisons
- **Reliable signals** — Authority, freshness, clarity

If your content doesn't present those cleanly, it becomes invisible.

You didn't lose traffic because your SEO failed. **You lost traffic because your content wasn't built for selection.**

---

## The Uncomfortable Truth

AI search is collapsing the funnel.

Top-of-funnel traffic used to belong to everyone who ranked. Now it belongs to whoever the AI trusts enough to quote.

That's a brutal filter.

And it's why early movers will dominate visibility while everyone else debates terminology.

---

## This Is Why GEO Exists

**Generative Engine Optimization (GEO)** isn't a buzzword. It's a response to a real problem:

> "Why isn't my content showing up in AI answers?"

GEO focuses on:

- How content is **structured**
- How ideas are **expressed**
- How entities are **defined**
- How answers are **extracted**
- How trust is **signaled**

In other words: whether AI can actually use your content.

---

## Most Teams Are Flying Blind

Here's the scary part.

Right now, most companies have:

- ❌ No AI visibility metrics
- ❌ No citation tracking
- ❌ No understanding of extractability
- ❌ No idea which pages are usable by AI

They're publishing content without knowing if it can even be read by the systems shaping discovery.

That's not strategy. **That's hope.**

---

## How GeoSource.ai Helps You Take Control

[GeoSource.ai](/) exists for one reason:

**To make AI visibility measurable.**

Instead of guessing, you can:

1. **Scan any page** — Enter a URL and analyze it
2. **See its GEO score** — 0-100 rating across 12 pillars
3. **Identify structural blind spots** — What's blocking AI understanding
4. **Fix what matters** — Prioritized recommendations
5. **Rescan and measure** — Track improvement over time

It turns AI optimization into something actionable — not theoretical.

No fluff. No vague advice. **Just clarity.**

---

## You Don't Need More Content

You need content AI can select.

Publishing more blog posts won't fix invisibility. **Improving how your best pages are understood will.**

One optimized page that AI consistently cites is worth more than ten that never appear.

---

## The Brands That Win Won't Be Louder — They'll Be Clearer

AI search rewards:

| Not This | This |
|----------|------|
| Cleverness | Clarity |
| Storytelling | Structure |
| Volume | Understanding |

The sooner your content reflects that, the faster you reclaim traffic you didn't even realize you were losing.

---

## The Bottom Line

If AI is the new front door to the internet, then **GEO is how you make sure your brand is standing in it.**

---

## Next Step

Run your highest-traffic page through [GeoSource.ai](/).

If AI can't understand it, neither can your future customers.

---

## Related Reading

- [How AI Search Engines Cite Sources](/blog/how-ai-search-engines-cite-sources) — Understand what makes content citable
- [Designing Content for AI Snippet Extraction](/blog/designing-content-for-ai-snippet-extraction) — Tactical guide to structure
- [GEO Optimization Checklist](/geo-optimization-checklist) — Step-by-step framework
MARKDOWN;

        $faq = [
            [
                'question' => 'Is AI search really taking traffic from websites?',
                'answer' => 'Yes. When users ask AI systems like ChatGPT, Perplexity, or Gemini a question, they often get a complete answer without clicking through to any website. Only sources that get cited receive traffic — everyone else gets nothing.',
            ],
            [
                'question' => 'Why isn\'t my content showing up in AI answers?',
                'answer' => 'AI systems avoid content that\'s risky to cite. If your page buries the answer, lacks structure, mixes multiple intents, or assumes human context, AI won\'t extract from it — not because it\'s wrong, but because it\'s unclear.',
            ],
            [
                'question' => 'What is Generative Engine Optimization (GEO)?',
                'answer' => 'GEO is the practice of structuring content so AI search engines can understand, trust, and cite it. It focuses on how content is structured, how ideas are expressed, how entities are defined, how answers are extracted, and how trust is signaled.',
            ],
            [
                'question' => 'How do I know if AI can use my content?',
                'answer' => 'Use a GEO score tool like GeoSource.ai to scan your pages. It analyzes content across 12 AI evaluation pillars and shows exactly what\'s blocking AI understanding, with prioritized recommendations to fix it.',
            ],
            [
                'question' => 'Do I need to create more content for AI visibility?',
                'answer' => 'No. You need content AI can select. One optimized page that AI consistently cites is worth more than ten pages that never appear in AI answers. Focus on improving your best existing content first.',
            ],
        ];

        $quickLinks = [
            ['title' => 'The Click Is Disappearing', 'anchor' => 'the-click-is-disappearing'],
            ['title' => 'This Isn\'t a Google Update', 'anchor' => 'this-isnt-a-google-update--its-a-new-interface'],
            ['title' => 'But My Content Is Good', 'anchor' => 'but-my-content-is-good'],
            ['title' => 'AI Wants Your Explanation', 'anchor' => 'ai-doesnt-want-your-blog-post--it-wants-your-explanation'],
            ['title' => 'The Uncomfortable Truth', 'anchor' => 'the-uncomfortable-truth'],
            ['title' => 'This Is Why GEO Exists', 'anchor' => 'this-is-why-geo-exists'],
            ['title' => 'Most Teams Are Flying Blind', 'anchor' => 'most-teams-are-flying-blind'],
            ['title' => 'How GeoSource.ai Helps', 'anchor' => 'how-geosourceai-helps-you-take-control'],
            ['title' => 'You Don\'t Need More Content', 'anchor' => 'you-dont-need-more-content'],
            ['title' => 'Clarity Over Cleverness', 'anchor' => 'the-brands-that-win-wont-be-louder--theyll-be-clearer'],
        ];

        $schemaJson = [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'BlogPosting',
                    'headline' => 'AI Search Is Stealing Your Traffic — Here\'s How to Get It Back',
                    'description' => 'AI search is already taking traffic from websites. Learn why your content isn\'t appearing in AI answers and how Generative Engine Optimization (GEO) helps you reclaim visibility.',
                    'url' => 'https://geosource.ai/blog/ai-search-is-stealing-your-traffic',
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
                    'image' => 'https://geosource.ai/images/blog/ai-search-is-stealing-your-traffic.png',
                    'mainEntityOfPage' => [
                        '@type' => 'WebPage',
                        '@id' => 'https://geosource.ai/blog/ai-search-is-stealing-your-traffic',
                    ],
                    'about' => [
                        '@type' => 'Thing',
                        'name' => 'AI Search Traffic Loss',
                        'description' => 'The phenomenon where websites lose traffic because AI search engines provide direct answers instead of linking to sources.',
                    ],
                ],
                [
                    '@type' => 'FAQPage',
                    'mainEntity' => [
                        [
                            '@type' => 'Question',
                            'name' => 'Is AI search really taking traffic from websites?',
                            'acceptedAnswer' => [
                                '@type' => 'Answer',
                                'text' => 'Yes. When users ask AI systems like ChatGPT, Perplexity, or Gemini a question, they often get a complete answer without clicking through to any website.',
                            ],
                        ],
                        [
                            '@type' => 'Question',
                            'name' => 'Why isn\'t my content showing up in AI answers?',
                            'acceptedAnswer' => [
                                '@type' => 'Answer',
                                'text' => 'AI systems avoid content that\'s risky to cite. If your page buries the answer, lacks structure, or assumes human context, AI won\'t extract from it.',
                            ],
                        ],
                        [
                            '@type' => 'Question',
                            'name' => 'What is Generative Engine Optimization (GEO)?',
                            'acceptedAnswer' => [
                                '@type' => 'Answer',
                                'text' => 'GEO is the practice of structuring content so AI search engines can understand, trust, and cite it in their responses.',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        BlogPost::create([
            'title' => 'AI Search Is Stealing Your Traffic — Here\'s How to Get It Back',
            'slug' => $slug,
            'excerpt' => 'AI search is already taking traffic from websites. Not in the future — right now. Learn why your content isn\'t appearing in AI answers and how GEO helps you reclaim visibility.',
            'content' => $content,
            'featured_image' => '/images/blog/ai-search-is-stealing-your-traffic.png',
            'meta_title' => 'AI Search Is Stealing Your Traffic — Here\'s How to Get It Back | GeoSource.ai',
            'meta_description' => 'AI search is taking website traffic. Learn why your content isn\'t in AI answers and how Generative Engine Optimization (GEO) helps you get cited by ChatGPT, Perplexity, and Gemini.',
            'schema_json' => $schemaJson,
            'tags' => ['AI Search', 'GEO', 'Traffic Loss', 'Content Strategy', 'AI Visibility'],
            'faq' => $faq,
            'quick_links' => $quickLinks,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->info("✓ Imported blog post: {$slug}");
    }
}
