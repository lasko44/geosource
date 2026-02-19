<?php

namespace App\Console\Commands;

use App\Models\BlogPost;
use Illuminate\Console\Command;

/**
 * Imports blog posts from code definitions into the database.
 */
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

        if ($slug === 'topical-authority-matters-more-than-backlinks-in-ai-search' || ! $slug) {
            $this->importTopicalAuthorityMattersMoreThanBacklinks();
        }

        if ($slug === 'why-some-content-becomes-ai-knowledge' || ! $slug) {
            $this->importWhySomeContentBecomesAiKnowledge();
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
            'excerpt' => 'AI search engines don\'t rank pages - they extract answers. Learn how to structure headings, lists, tables, and definitions so AI systems can confidently cite your content.',
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
        // comment
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
                    'headline' => 'AI Search Is Stealing Your Traffic - Here\'s How to Get It Back',
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
            'title' => 'AI Search Is Stealing Your Traffic - Here\'s How to Get It Back',
            'slug' => $slug,
            'excerpt' => 'AI search is already taking traffic from websites. Not in the future - right now. Learn why your content isn\'t appearing in AI answers and how GEO helps you reclaim visibility.',
            'content' => $content,
            'featured_image' => '/images/blog/ai-search-is-stealing-your-traffic.png',
            'meta_title' => 'AI Search Is Stealing Your Traffic - Here\'s How to Get It Back | GeoSource.ai',
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

    protected function importTopicalAuthorityMattersMoreThanBacklinks(): void
    {
        $slug = 'topical-authority-matters-more-than-backlinks-in-ai-search';

        if (BlogPost::where('slug', $slug)->exists()) {
            $this->warn("Blog post '{$slug}' already exists. Skipping.");

            return;
        }

        $content = <<<'MARKDOWN'
For more than 20 years, backlinks were treated like the ultimate ranking signal.

More links meant more authority.
More authority meant more traffic.

That belief is now actively misleading people.

**Because AI search doesn't rank pages — it chooses sources.**

When an AI engine like ChatGPT, Perplexity, or Google's AI Overviews generates an answer, it doesn't care how many websites link to you.

It cares whether you actually understand the topic.

This is why sites with massive backlink profiles are being ignored in AI answers — while smaller, tightly focused websites with far fewer links are being cited instead.

**Backlinks measure popularity.**

**AI search rewards understanding.**

And that changes everything.

---

## TL;DR

- In AI search, backlinks signal popularity — not comprehension.
- Generative engines prioritize topical authority: consistent, structured coverage of a subject across multiple pages.
- If your site doesn't demonstrate deep topic understanding, it won't be cited — regardless of how many links you have.

---

## Backlinks Were Built for Ranking Pages, Not Answering Questions

Traditional search engines needed a way to decide which page deserved to rank first.

Backlinks solved that problem.

They acted as votes — signals of trust and popularity across the web.

But AI search is not ranking ten blue links.

**It is generating a synthesized answer.**

That distinction matters.

Ranking pages requires popularity signals.
Answering questions requires understanding.

Backlinks were never designed to measure understanding.

---

## How AI Search Actually Chooses Sources

When an AI engine generates an answer, it doesn't look at a single page in isolation.

**It evaluates topic-level confidence.**

AI systems look for sources that demonstrate:

- Consistent coverage of a subject
- Repeated explanation of related concepts
- Aligned terminology and definitions
- Clear, structured presentation

This is why AI answers often reference sites that don't rank #1 in Google.

They aren't being chosen for authority in the traditional sense.

**They're being chosen for comprehension.**

This shift is part of a broader transformation in how information is discovered online, which is explored more deeply in [The Rise of AI Search: What It Means for Content Creators](/blog/rise-of-ai-search-content-creators).

---

## What Topical Authority Means in AI Search

Topical authority is not one great article.

**It's a pattern.**

AI systems build confidence when they see a website repeatedly explain the same subject from multiple angles.

That includes:

- Beginner explanations
- Technical breakdowns
- Comparisons
- Implications
- Related subtopics

This consistency tells the model:

> "This source understands the domain."

This is the foundation of Generative Engine Optimization (GEO) — where visibility comes from being understood, not merely ranked.

For a deeper explanation of this shift, see [What Is GEO? A Complete Guide to Generative Engine Optimization](/blog/what-is-geo-complete-guide).

---

## Why Backlinks Are Weaker Signals for AI

Backlinks still matter — but they matter differently.

A backlink says:

> "Someone referenced this page."

It does not say:

> "This site deeply understands the topic."

AI systems care far more about reducing uncertainty than measuring popularity.

From an AI perspective, a source with consistent explanations across many pages is safer than a highly linked page that barely covers the subject.

That's why backlink-heavy sites can lose visibility in AI answers, while smaller niche publishers gain it.

---

## AI Engines Think in Topics, Not Keywords

Traditional SEO was keyword-first.

**AI search is concept-first.**

Instead of ranking for phrases like:

> "AI search optimization"

AI systems map relationships between ideas such as:

- Generative answers
- Source citation
- Content structure
- Entity relevance
- Topical depth

Sites that repeatedly reinforce these concepts build stronger semantic identity.

This is why topical focus matters more than keyword coverage.

---

## Repetition Builds Authority — Not Redundancy

In classic SEO, repetition was often discouraged.

**In AI search, repetition builds confidence.**

When an AI model encounters the same concept explained clearly across multiple articles, it strengthens retrieval certainty.

This isn't keyword stuffing.

**It's semantic reinforcement.**

Explaining the same idea from different angles increases trust — not dilution.

---

## Why Single Viral Articles Don't Build AI Visibility

A single viral post can drive traffic.

It rarely builds authority.

**AI systems don't trust spikes.**

**They trust consistency.**

Topical authority emerges when a site publishes:

- Multiple related articles
- Interconnected explanations
- Aligned terminology
- Internal links reinforcing context

One article cannot establish that pattern.

Ten connected ones can.

---

## Topical Authority Compounds Over Time

Backlinks are largely static.

**Topical authority compounds.**

Every new related article:

- Strengthens previous pages
- Reinforces entity associations
- Increases citation likelihood
- Improves retrieval confidence

This is why AI visibility often appears suddenly.

The groundwork was happening long before the first citation.

---

## Why New Sites Can Compete Faster in AI Search

This shift explains something many creators are noticing:

**New, focused websites can appear in AI answers surprisingly fast.**

They may not outrank major domains in Google SERPs — but they can still be cited.

Why?

Because AI engines do not require years of link accumulation to evaluate understanding.

**They only require clarity.**

This is also why technical foundations matter, especially for AI crawlers — including how content is rendered and indexed, as explained in [Why Server-Side Rendering (SSR) Matters for GEO and AI Visibility](/blog/why-server-side-rendering-ssr-matters-for-geo-and-ai-visibility).

---

## The Role of Structure in Topical Authority

Structure plays a major role in how AI systems evaluate expertise.

AI prefers content that is:

- Logically segmented
- Clearly titled
- Declarative in headings
- Easy to extract

Formatting isn't cosmetic.

**It's interpretive.**

This is why headings, lists, summaries, and tables increase citation probability — a concept covered in [Designing Content for AI Snippet Extraction](/blog/designing-content-for-ai-snippet-extraction).

---

## What Actually Builds Topical Authority

Strong topical authority comes from:

- Narrow content focus
- Consistent terminology
- Internal linking between related posts
- Clear definitions and summaries
- Repeated coverage of core concepts

Together, these signals tell AI systems:

> "This site specializes here."

That specialization matters more than popularity.

---

## When Backlinks Still Matter

Backlinks still play a role.

They help with:

- Discovery
- Traditional rankings
- External trust validation

But they are no longer the primary signal for visibility inside AI-generated answers.

**Think of backlinks as permission.**

**Think of topical authority as qualification.**

AI will not cite you because you are popular.

It will cite you because you are reliable.

---

## Why This Changes Content Strategy Entirely

If topical authority matters more than backlinks, then content strategy must change.

That means:

- Fewer unrelated posts
- Deeper topical coverage
- Stronger internal linking
- Clearer conceptual positioning

Instead of asking:

> "What keywords should we target next?"

The better question becomes:

> "What topic do we want to own?"

That mindset shift defines GEO.

---

## Topical Authority Is the Core of GEO

Generative Engine Optimization is not about ranking pages.

**It's about becoming a trusted source within a topic.**

AI engines surface sources that:

- Demonstrate consistent understanding
- Reduce hallucination risk
- Explain concepts clearly
- Maintain alignment across content

This is why GEO success often appears before traffic growth.

Visibility comes first.

Clicks come later — if at all.

---

## Final Thought: Understanding Beats Popularity

The old web rewarded popularity.

**The new web rewards understanding.**

Backlinks measure who talks about you.
Topical authority measures how well you understand something.

**In AI search, understanding wins.**

If your goal is to appear in answers — not just rankings — then your strategy must shift away from chasing links and toward building deep, consistent topical coverage.

That's how sites become visible in AI search.

And that's how authority is earned in the generative era.
MARKDOWN;

        $faq = [
            [
                'question' => 'Do backlinks still matter for AI search?',
                'answer' => 'Backlinks still help with discovery, traditional rankings, and external trust validation. However, they are no longer the primary signal for visibility inside AI-generated answers. AI engines prioritize topical authority — consistent, structured coverage of a subject — over link popularity.',
            ],
            [
                'question' => 'What is topical authority in the context of AI search?',
                'answer' => 'Topical authority is a pattern of consistent coverage across multiple pages on the same subject. AI systems build confidence when they see a website repeatedly explain related concepts with aligned terminology, clear definitions, and structured presentation.',
            ],
            [
                'question' => 'Can new websites compete in AI search without backlinks?',
                'answer' => 'Yes. AI engines do not require years of link accumulation to evaluate understanding. New, focused websites can appear in AI answers by demonstrating clarity and deep topical coverage, even without strong backlink profiles.',
            ],
            [
                'question' => 'Why does topical authority compound over time?',
                'answer' => 'Every new related article strengthens previous pages, reinforces entity associations, increases citation likelihood, and improves retrieval confidence. Unlike backlinks which are largely static, topical authority grows with each piece of connected content.',
            ],
            [
                'question' => 'How does GEO differ from traditional SEO link building?',
                'answer' => 'Traditional SEO link building focuses on acquiring external links to signal popularity. GEO focuses on building deep, consistent topical coverage so AI engines can understand, trust, and cite your content. The shift is from popularity to comprehension.',
            ],
        ];

        $quickLinks = [
            ['title' => 'TL;DR', 'anchor' => 'tldr'],
            ['title' => 'Backlinks vs Questions', 'anchor' => 'backlinks-were-built-for-ranking-pages-not-answering-questions'],
            ['title' => 'How AI Chooses Sources', 'anchor' => 'how-ai-search-actually-chooses-sources'],
            ['title' => 'What Topical Authority Means', 'anchor' => 'what-topical-authority-means-in-ai-search'],
            ['title' => 'Why Backlinks Are Weaker', 'anchor' => 'why-backlinks-are-weaker-signals-for-ai'],
            ['title' => 'Topics Not Keywords', 'anchor' => 'ai-engines-think-in-topics-not-keywords'],
            ['title' => 'Repetition Builds Authority', 'anchor' => 'repetition-builds-authority--not-redundancy'],
            ['title' => 'Viral Articles Don\'t Build Visibility', 'anchor' => 'why-single-viral-articles-dont-build-ai-visibility'],
            ['title' => 'Authority Compounds', 'anchor' => 'topical-authority-compounds-over-time'],
            ['title' => 'New Sites Can Compete', 'anchor' => 'why-new-sites-can-compete-faster-in-ai-search'],
            ['title' => 'Role of Structure', 'anchor' => 'the-role-of-structure-in-topical-authority'],
            ['title' => 'What Builds Authority', 'anchor' => 'what-actually-builds-topical-authority'],
            ['title' => 'When Backlinks Still Matter', 'anchor' => 'when-backlinks-still-matter'],
            ['title' => 'Content Strategy Changes', 'anchor' => 'why-this-changes-content-strategy-entirely'],
            ['title' => 'Core of GEO', 'anchor' => 'topical-authority-is-the-core-of-geo'],
        ];

        $schemaJson = [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'BlogPosting',
                    'headline' => 'Topical Authority Matters More Than Backlinks in AI Search',
                    'description' => 'In AI search, backlinks signal popularity — not comprehension. Learn why topical authority is the real driver of AI visibility and how to build it.',
                    'url' => 'https://geosource.ai/blog/topical-authority-matters-more-than-backlinks-in-ai-search',
                    'datePublished' => '2026-01-28',
                    'dateModified' => '2026-01-28',
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
                    'image' => 'https://geosource.ai/images/blog/topical-authority-matters-more-than-backlinks-in-ai-search.png',
                    'mainEntityOfPage' => [
                        '@type' => 'WebPage',
                        '@id' => 'https://geosource.ai/blog/topical-authority-matters-more-than-backlinks-in-ai-search',
                    ],
                    'about' => [
                        '@type' => 'Thing',
                        'name' => 'Topical Authority in AI Search',
                        'description' => 'The concept that AI search engines prioritize consistent, structured coverage of a subject over backlink-based popularity signals when choosing sources to cite.',
                    ],
                ],
                [
                    '@type' => 'FAQPage',
                    'mainEntity' => [
                        [
                            '@type' => 'Question',
                            'name' => 'Do backlinks still matter for AI search?',
                            'acceptedAnswer' => [
                                '@type' => 'Answer',
                                'text' => 'Backlinks still help with discovery and traditional rankings, but they are no longer the primary signal for visibility inside AI-generated answers. AI engines prioritize topical authority over link popularity.',
                            ],
                        ],
                        [
                            '@type' => 'Question',
                            'name' => 'What is topical authority in AI search?',
                            'acceptedAnswer' => [
                                '@type' => 'Answer',
                                'text' => 'Topical authority is a pattern of consistent coverage across multiple pages on the same subject, with aligned terminology, clear definitions, and structured presentation.',
                            ],
                        ],
                        [
                            '@type' => 'Question',
                            'name' => 'Can new websites compete in AI search without backlinks?',
                            'acceptedAnswer' => [
                                '@type' => 'Answer',
                                'text' => 'Yes. AI engines do not require years of link accumulation. New, focused websites can appear in AI answers by demonstrating clarity and deep topical coverage.',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $author = \App\Models\User::where('email', 'matthew.laszkewicz@gmail.com')->first();

        BlogPost::create([
            'title' => 'Topical Authority Matters More Than Backlinks in AI Search',
            'slug' => $slug,
            'author_id' => $author?->id,
            'excerpt' => 'AI search doesn\'t rank pages — it chooses sources. Learn why topical authority has replaced backlinks as the primary driver of visibility in AI-generated answers.',
            'content' => $content,
            'featured_image' => '/images/blog/topical-authority-matters-more-than-backlinks-in-ai-search.svg',
            'meta_title' => 'Topical Authority Matters More Than Backlinks in AI Search | GeoSource.ai',
            'meta_description' => 'Backlinks measure popularity. AI search rewards understanding. Learn why topical authority is now more important than backlinks for AI visibility and citations.',
            'schema_json' => $schemaJson,
            'tags' => ['GEO', 'Topical Authority', 'Backlinks', 'AI Search', 'Content Strategy'],
            'faq' => $faq,
            'quick_links' => $quickLinks,
            'status' => 'published',
            'published_at' => '2026-02-15 10:00:00',
        ]);

        $this->info("✓ Imported blog post: {$slug}");
    }

    protected function importWhySomeContentBecomesAiKnowledge(): void
    {
        $slug = 'why-some-content-becomes-ai-knowledge';

        if (BlogPost::where('slug', $slug)->exists()) {
            $this->warn("Blog post '{$slug}' already exists. Skipping.");

            return;
        }

        $content = <<<'MARKDOWN'
Every day, billions of pages compete for attention.

But when someone asks ChatGPT, Perplexity, or Claude a question, only a handful of sources get cited.

**Most content is invisible to AI.**

Not because it's wrong. Not because it's poorly written. But because AI systems aren't designed to use everything — they're designed to select what they can trust.

Understanding that selection process is the difference between content that gets cited and content that gets ignored.

---

## How AI Decides What to Cite

AI search engines don't read content the way humans do.

They scan, extract, and evaluate. Every piece of content goes through a filter:

| AI Evaluation | What It Measures |
|---------------|------------------|
| Clarity | Can the answer be extracted quickly? |
| Structure | Is information organized logically? |
| Authority | Does this source demonstrate expertise? |
| Confidence | Can AI cite this without risk of error? |

Content that passes all four becomes citable.

**Content that fails any one often becomes invisible.**

This is why well-researched articles with buried answers get skipped, while simpler pages with clear structure get cited.

---

## The Selection Problem Most Content Creators Miss

Here's what most content strategies get wrong:

They optimize for **discovery** — rankings, keywords, clicks.

But AI search doesn't discover content the same way humans do.

**AI search selects content.**

Selection requires:

- **Extractability** — Can AI pull out a clean answer?
- **Definitiveness** — Does the content answer the question directly?
- **Trust signals** — Is this source consistently reliable?

If your page ranks #1 in Google but buries the answer in paragraph seven, AI will skip it and cite someone else.

This is explored further in [AI Search Is Stealing Your Traffic — Here's How to Get It Back](/blog/ai-search-is-stealing-your-traffic).

---

## Why Most Content Fails AI Selection

Most content fails because it was written for humans browsing, not machines extracting.

**Common failure patterns:**

1. **Buried answers** — The key information appears after long intros
2. **Mixed intent** — The page tries to answer too many questions
3. **Implicit context** — Assumes the reader already knows background
4. **Weak structure** — No clear headings, lists, or summaries
5. **Narrative-first** — Tells a story instead of stating facts

AI systems avoid these patterns because they introduce uncertainty.

**Uncertainty is the enemy of citation.**

When AI can't confidently extract an answer, it moves on to a source that makes extraction easier.

---

## What Makes Content Citable

Citable content shares common traits:

### 1. Answer-First Structure

The answer appears immediately after the question or heading.

> **Question:** What is Generative Engine Optimization?
>
> **Answer:** Generative Engine Optimization (GEO) is the practice of structuring content so AI systems can accurately extract, understand, and cite it in generated responses.

No preamble. No buildup. Just the answer.

### 2. Clear Definitions

AI relies heavily on definitions to establish entity relationships.

A strong definition:

- Uses the "X is Y" format
- Appears early on the page
- Avoids marketing language
- Is quotable without modification

### 3. Logical Hierarchy

Headings create a machine-readable outline.

| Good Hierarchy | Bad Hierarchy |
|----------------|---------------|
| H1: Main Topic | H1: Welcome! |
| H2: Subtopic A | H3: Random Thoughts |
| H3: Detail | H2: More Stuff |

AI uses headings to understand relationships. Broken hierarchy breaks understanding.

### 4. Structured Data

Lists, tables, and FAQs are AI gold.

Each item in a list is a discrete extractable fact. Tables encode relationships explicitly. FAQs mirror the exact question-answer pattern AI uses internally.

For tactical implementation, see [Designing Content for AI Snippet Extraction](/blog/designing-content-for-ai-snippet-extraction).

---

## The Trust Factor

Selection isn't just about structure. It's about trust.

AI systems evaluate trust signals including:

- **Topical consistency** — Does this site cover this subject deeply?
- **Internal linking** — Are concepts connected across pages?
- **Freshness** — Is the content current?
- **External references** — Does the content cite credible sources?

A single well-structured page won't build trust alone.

**Trust emerges from patterns.**

This is why [Topical Authority Matters More Than Backlinks in AI Search](/blog/topical-authority-matters-more-than-backlinks-in-ai-search) — consistency beats popularity in the generative era.

---

## Why This Matters Now

The shift to AI search is accelerating.

Google's AI Overviews, ChatGPT's search features, Perplexity's growth — these aren't experiments anymore. They're becoming the primary interface for information discovery.

Every month, more queries get answered without clicks.

**The window to establish AI visibility is closing.**

Sites that optimize now will become the default sources AI trusts. Sites that wait will compete for whatever citations remain.

---

## How to Know If Your Content Is Citable

Ask yourself:

- ✅ Can the main question be answered from the first paragraph?
- ✅ Are headings descriptive and question-aligned?
- ✅ Would an AI safely quote any sentence without context?
- ✅ Does the page cover one topic thoroughly, not many topics lightly?
- ✅ Are definitions explicit and quotable?

If the answer to any is no, your content may be structurally invisible.

---

## The Path Forward

Content becoming AI knowledge isn't random. It follows predictable patterns.

**What works:**

- Structure for extraction
- Definitions for clarity
- Consistency for trust
- Depth over breadth

**What doesn't:**

- Clever writing that buries information
- Broad coverage that lacks specificity
- Assumptions that readers have context
- Narrative-first approaches

The goal isn't more content. **It's more citable content.**

---

## Measure Before You Optimize

Most teams have no visibility into how AI evaluates their content.

[GeoSource.ai](/) provides exactly that:

1. **Scan any URL** — Analyze structure and extractability
2. **Get a GEO score** — 0-100 rating across 12 AI evaluation pillars
3. **See what's blocking citations** — Specific, actionable recommendations
4. **Track improvement** — Measure changes over time

You can't optimize what you can't measure.

---

## Final Thought

AI search is not a future trend. It's the present reality.

**The content that becomes AI knowledge isn't better written — it's better structured.**

Understanding how AI selects sources isn't optional anymore. It's the foundation of visibility in the generative era.

Your content can either become part of AI's knowledge or remain invisible.

The choice — and the structure — is yours.
MARKDOWN;

        $faq = [
            [
                'question' => 'Why doesn\'t my content appear in AI answers?',
                'answer' => 'AI systems select content based on extractability, clarity, structure, and trust signals. If your content buries answers, lacks clear headings, or mixes multiple topics, AI will skip it — not because it\'s wrong, but because it introduces uncertainty.',
            ],
            [
                'question' => 'What makes content citable by AI?',
                'answer' => 'Citable content has answer-first structure, clear definitions, logical heading hierarchy, and structured data like lists and tables. Each element reduces AI\'s uncertainty about what your content actually says.',
            ],
            [
                'question' => 'How does AI decide which sources to trust?',
                'answer' => 'AI evaluates trust through topical consistency (covering a subject deeply across pages), internal linking, content freshness, and external references. Trust emerges from patterns, not single pages.',
            ],
            [
                'question' => 'Can well-written content still be invisible to AI?',
                'answer' => 'Yes. Content written for human browsing often fails AI selection. Narrative-first approaches, buried answers, and implicit context make extraction risky. AI prioritizes structure over prose quality.',
            ],
            [
                'question' => 'How do I know if my content is AI-ready?',
                'answer' => 'Use a GEO score tool to analyze your pages across AI evaluation pillars. Key questions: Can the main answer be extracted from paragraph one? Are headings descriptive? Would AI safely quote any sentence? If not, your content may be structurally invisible.',
            ],
        ];

        $quickLinks = [
            ['title' => 'How AI Decides What to Cite', 'anchor' => 'how-ai-decides-what-to-cite'],
            ['title' => 'The Selection Problem', 'anchor' => 'the-selection-problem-most-content-creators-miss'],
            ['title' => 'Why Most Content Fails', 'anchor' => 'why-most-content-fails-ai-selection'],
            ['title' => 'What Makes Content Citable', 'anchor' => 'what-makes-content-citable'],
            ['title' => 'The Trust Factor', 'anchor' => 'the-trust-factor'],
            ['title' => 'Why This Matters Now', 'anchor' => 'why-this-matters-now'],
            ['title' => 'Is Your Content Citable?', 'anchor' => 'how-to-know-if-your-content-is-citable'],
            ['title' => 'The Path Forward', 'anchor' => 'the-path-forward'],
            ['title' => 'Measure Before You Optimize', 'anchor' => 'measure-before-you-optimize'],
        ];

        $schemaJson = [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'BlogPosting',
                    'headline' => 'Why Some Content Becomes AI Knowledge and Most Still Doesn\'t',
                    'description' => 'Understand how AI search engines select sources to cite. Learn why most content fails AI selection and what makes content citable in the generative era.',
                    'url' => 'https://geosource.ai/blog/why-some-content-becomes-ai-knowledge',
                    'datePublished' => '2026-02-18',
                    'dateModified' => '2026-02-18',
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
                    'image' => 'https://geosource.ai/images/blog/why-some-content-becomes-ai-knowledge.png',
                    'mainEntityOfPage' => [
                        '@type' => 'WebPage',
                        '@id' => 'https://geosource.ai/blog/why-some-content-becomes-ai-knowledge',
                    ],
                    'about' => [
                        '@type' => 'Thing',
                        'name' => 'AI Content Selection',
                        'description' => 'The process by which AI search engines evaluate and select content to cite in generated responses.',
                    ],
                ],
                [
                    '@type' => 'FAQPage',
                    'mainEntity' => [
                        [
                            '@type' => 'Question',
                            'name' => 'Why doesn\'t my content appear in AI answers?',
                            'acceptedAnswer' => [
                                '@type' => 'Answer',
                                'text' => 'AI systems select content based on extractability, clarity, structure, and trust signals. Content that buries answers or lacks clear structure introduces uncertainty and gets skipped.',
                            ],
                        ],
                        [
                            '@type' => 'Question',
                            'name' => 'What makes content citable by AI?',
                            'acceptedAnswer' => [
                                '@type' => 'Answer',
                                'text' => 'Citable content has answer-first structure, clear definitions, logical heading hierarchy, and structured data like lists and tables.',
                            ],
                        ],
                        [
                            '@type' => 'Question',
                            'name' => 'How does AI decide which sources to trust?',
                            'acceptedAnswer' => [
                                '@type' => 'Answer',
                                'text' => 'AI evaluates trust through topical consistency, internal linking, content freshness, and external references. Trust emerges from patterns across multiple pages.',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $author = \App\Models\User::where('email', 'matthew.laszkewicz@gmail.com')->first();

        BlogPost::create([
            'title' => 'Why Some Content Becomes AI Knowledge and Most Still Doesn\'t',
            'slug' => $slug,
            'author_id' => $author?->id,
            'excerpt' => 'AI search engines don\'t discover content — they select it. Learn how AI decides what to cite, why most content fails selection, and how to structure content for AI visibility.',
            'content' => $content,
            'featured_image' => '/images/blog/why-some-content-becomes-ai-knowledge.png',
            'meta_title' => 'Why Some Content Becomes AI Knowledge and Most Still Doesn\'t | GeoSource.ai',
            'meta_description' => 'Understand how AI search engines select sources to cite. Learn why most content fails AI selection and how to structure content for visibility in ChatGPT, Perplexity, and Claude.',
            'schema_json' => $schemaJson,
            'tags' => ['GEO', 'AI Search', 'Content Strategy', 'AI Citations', 'Visibility'],
            'faq' => $faq,
            'quick_links' => $quickLinks,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->info("✓ Imported blog post: {$slug}");
    }
}
