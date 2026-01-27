<?php

namespace App\Console\Commands;

use App\Models\LearningResource;
use Illuminate\Console\Command;

class ConvertResourcesToBlocks extends Command
{
    protected $signature = 'resources:convert-to-blocks {--slug= : Convert a specific resource by slug}';

    protected $description = 'Convert learning resources HTML content to styled JSON blocks';

    public function handle()
    {
        $slug = $this->option('slug');

        if ($slug) {
            $resource = LearningResource::where('slug', $slug)->first();
            if (! $resource) {
                $this->error("Resource with slug '{$slug}' not found.");

                return 1;
            }
            $this->convertResource($resource);
        } else {
            $resources = LearningResource::all();
            foreach ($resources as $resource) {
                $this->convertResource($resource);
            }
        }

        $this->info('Done converting resources to blocks!');

        return 0;
    }

    protected function convertResource(LearningResource $resource): void
    {
        $method = 'convert'.str_replace('-', '', ucwords($resource->slug, '-'));

        if (method_exists($this, $method)) {
            $this->info("Converting: {$resource->title}");
            $blocks = $this->$method();
            $resource->update([
                'content_type' => 'blocks',
                'content_blocks' => $blocks,
            ]);
            $this->info("  ✓ Converted to ".count($blocks).' blocks');
        } else {
            $this->warn("No converter found for: {$resource->slug}");
        }
    }

    protected function convertWhatIsGeo(): array
    {
        return [
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'Definition', 'id' => 'definition'],
            ],
            [
                'type' => 'definition',
                'props' => [
                    'term' => 'Generative Engine Optimization (GEO)',
                    'definition' => 'is the practice of optimizing digital content so it can be accurately understood, trusted, and cited by generative AI systems such as ChatGPT, Google AI Overviews, Perplexity, Claude, and other large language models (LLMs).',
                ],
            ],
            [
                'type' => 'paragraph',
                'props' => [
                    'content' => 'Unlike traditional search engines, generative engines do not rank web pages by backlinks or keyword density. Instead, they <strong class="text-foreground">synthesize answers</strong> by selecting high-confidence sources based on clarity, structure, topical authority, and factual consistency.',
                ],
            ],
            [
                'type' => 'paragraph',
                'props' => [
                    'content' => '<span class="text-lg font-medium text-foreground">GEO focuses on making content AI-readable and citation-ready.</span>',
                ],
            ],
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'Why GEO Exists', 'id' => 'why-geo-exists'],
            ],
            [
                'type' => 'paragraph',
                'props' => [
                    'content' => 'Generative search engines operate differently than Google:',
                ],
            ],
            [
                'type' => 'table',
                'props' => [
                    'headers' => ['Aspect', 'Traditional SEO', 'Generative AI'],
                    'highlightColumn' => 2,
                    'rows' => [
                        ['Output', 'Ranks links', 'Synthesizes answers'],
                        ['Optimizes for', 'Keywords', 'Understanding'],
                        ['Measures', 'Clicks', 'Confidence'],
                        ['Uses', 'SERPs', 'Knowledge graphs'],
                    ],
                ],
            ],
            [
                'type' => 'warning-box',
                'props' => [
                    'title' => 'Key Insight',
                    'icon' => 'lightbulb',
                    'content' => 'Many high-ranking SEO pages are never cited by AI, while smaller sites with clearer structure often are. <strong class="text-foreground">GEO exists to solve this gap.</strong>',
                ],
            ],
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'Core Goals of GEO', 'id' => 'core-goals'],
            ],
            [
                'type' => 'step-list',
                'props' => [
                    'steps' => [
                        ['title' => 'Improve AI comprehension'],
                        ['title' => 'Increase likelihood of citation'],
                        ['title' => 'Reduce ambiguity in content'],
                        ['title' => 'Strengthen topical authority signals'],
                        ['title' => 'Structure information for machine consumption'],
                    ],
                ],
            ],
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'GEO in One Sentence', 'id' => 'summary'],
            ],
            [
                'type' => 'highlight-box',
                'props' => [
                    'content' => '<span class="text-xl font-medium">GEO helps AI systems understand what your site is about — and trust it enough to cite it.</span>',
                    'centered' => true,
                ],
            ],
        ];
    }

    protected function convertGeoVsSeo(): array
    {
        return [
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'Overview', 'id' => 'overview'],
            ],
            [
                'type' => 'paragraph',
                'props' => [
                    'content' => 'SEO and GEO share some foundational principles, but they optimize for fundamentally different systems. Understanding where they overlap — and where they diverge — is critical for modern digital strategy.',
                ],
            ],
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'Comparison Table', 'id' => 'comparison'],
            ],
            [
                'type' => 'table',
                'props' => [
                    'headers' => ['Aspect', 'SEO', 'GEO'],
                    'highlightColumn' => 2,
                    'rows' => [
                        ['Goal', 'Rank in search results', 'Be cited by AI systems'],
                        ['Target System', 'Google, Bing crawlers', 'ChatGPT, Claude, Perplexity'],
                        ['Success Metric', 'Clicks, impressions', 'Citations, accuracy'],
                        ['Primary Focus', 'Keywords, backlinks', 'Clarity, structure, authority'],
                        ['Content Format', 'Optimized for humans reading search results', 'Optimized for AI comprehension'],
                        ['Ranking Factors', 'PageRank, domain authority', 'Topical clarity, factual consistency'],
                        ['Output Type', 'Ranked list of links', 'Synthesized answer with sources'],
                    ],
                ],
            ],
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'Where SEO and GEO Overlap', 'id' => 'overlap'],
            ],
            [
                'type' => 'paragraph',
                'props' => [
                    'content' => 'Many SEO best practices also benefit GEO:',
                ],
            ],
            [
                'type' => 'list',
                'props' => [
                    'type' => 'check',
                    'items' => [
                        '<strong class="text-foreground">Clear heading hierarchy</strong> — helps both crawlers and LLMs parse content structure',
                        '<strong class="text-foreground">Structured data (JSON-LD)</strong> — provides explicit semantic context',
                        '<strong class="text-foreground">Quality content</strong> — authoritative, well-researched content performs better everywhere',
                        '<strong class="text-foreground">Fast page loads</strong> — ensures content is accessible to all systems',
                        '<strong class="text-foreground">Mobile-friendly design</strong> — modern requirement for all indexing',
                    ],
                ],
            ],
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'Where GEO Differs', 'id' => 'differences'],
            ],
            [
                'type' => 'paragraph',
                'props' => [
                    'content' => 'GEO requires additional optimizations that SEO doesn\'t emphasize:',
                ],
            ],
            [
                'type' => 'list',
                'props' => [
                    'type' => 'bullet',
                    'items' => [
                        '<strong class="text-foreground">Explicit definitions</strong> — "X is..." statements that AI can directly quote',
                        '<strong class="text-foreground">Declarative language</strong> — confident, citable statements without preamble',
                        '<strong class="text-foreground">Topical boundaries</strong> — clear scope so AI understands context limits',
                        '<strong class="text-foreground">Factual density</strong> — more facts per paragraph increases citation likelihood',
                        '<strong class="text-foreground">llms.txt file</strong> — explicit instructions for AI systems',
                    ],
                ],
            ],
            [
                'type' => 'warning-box',
                'props' => [
                    'title' => 'Key Insight',
                    'icon' => 'lightbulb',
                    'content' => 'A page can rank #1 on Google but never be cited by ChatGPT. Conversely, a page with no backlinks can be frequently cited if it has clear, authoritative definitions. <strong class="text-foreground">SEO and GEO are complementary, not competitive.</strong>',
                ],
            ],
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'Bottom Line', 'id' => 'bottom-line'],
            ],
            [
                'type' => 'highlight-box',
                'props' => [
                    'content' => '<span class="text-xl font-medium">SEO gets you traffic. GEO gets you cited. The best strategy optimizes for both.</span>',
                    'centered' => true,
                ],
            ],
        ];
    }

    protected function convertWhatIsAGeoScore(): array
    {
        return [
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'Definition', 'id' => 'definition'],
            ],
            [
                'type' => 'definition',
                'props' => [
                    'term' => 'GEO Score',
                    'definition' => 'is a quantitative measurement of how well a website or webpage is optimized for <a href="/resources/what-is-geo" class="text-primary hover:underline">Generative Engine Optimization (GEO)</a> — the practice of making content visible and citable by AI systems.',
                ],
            ],
            [
                'type' => 'paragraph',
                'props' => [
                    'content' => 'Unlike SEO scores, a GEO Score does not measure rankings or traffic. It measures <strong class="text-foreground">citation readiness</strong> — how likely AI systems like ChatGPT, Claude, and Perplexity are to understand, trust, and reference your content.',
                ],
            ],
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'How It Works', 'id' => 'how-it-works'],
            ],
            [
                'type' => 'paragraph',
                'props' => [
                    'content' => 'GeoSource.ai calculates your GEO Score by analyzing multiple factors:',
                ],
            ],
            [
                'type' => 'step-list',
                'props' => [
                    'steps' => [
                        ['title' => 'Clear Definitions', 'description' => 'Presence of explicit "X is..." definitions'],
                        ['title' => 'Structured Knowledge', 'description' => 'Heading hierarchy, lists, and organization'],
                        ['title' => 'Topic Authority', 'description' => 'Depth of coverage and expertise signals'],
                        ['title' => 'Machine-Readable Formatting', 'description' => 'Schema markup and semantic HTML'],
                        ['title' => 'High-Confidence Answerability', 'description' => 'Declarative, quotable statements'],
                    ],
                ],
            ],
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'Score Breakdown', 'id' => 'score-breakdown'],
            ],
            [
                'type' => 'table',
                'props' => [
                    'headers' => ['Grade', 'Score Range', 'Meaning'],
                    'highlightColumn' => -1,
                    'rows' => [
                        ['A+ to A-', '80-100%', 'Excellent - content is well-optimized for AI citation'],
                        ['B+ to B-', '65-79%', 'Good - minor improvements recommended'],
                        ['C+ to C-', '50-64%', 'Average - significant optimization needed'],
                        ['D+ to F', 'Below 50%', 'Needs work - unlikely to be cited by AI'],
                    ],
                ],
            ],
            [
                'type' => 'warning-box',
                'props' => [
                    'title' => 'Key Insight',
                    'icon' => 'lightbulb',
                    'content' => 'A high GEO Score doesn\'t guarantee citations, but it significantly increases your content\'s <strong class="text-foreground">likelihood of being understood and referenced</strong> by AI systems.',
                ],
            ],
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'Why It Matters', 'id' => 'why-it-matters'],
            ],
            [
                'type' => 'highlight-box',
                'props' => [
                    'content' => '<span class="text-xl font-medium">Your GEO Score reveals how AI sees your content — and what you can do to improve your visibility in AI-generated answers.</span>',
                    'centered' => true,
                ],
            ],
        ];
    }

    protected function convertHowAiSearchWorks(): array
    {
        return [
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'The Shift from Search to Synthesis', 'id' => 'shift'],
            ],
            [
                'type' => 'paragraph',
                'props' => [
                    'content' => 'Traditional search engines like Google work by <strong class="text-foreground">indexing web pages and ranking them</strong> based on relevance signals like keywords, backlinks, and user engagement. Users get a list of links to explore.',
                ],
            ],
            [
                'type' => 'paragraph',
                'props' => [
                    'content' => 'AI search engines work differently. They <strong class="text-foreground">synthesize answers</strong> by reading multiple sources, extracting relevant information, and generating a cohesive response. Users get a direct answer with optional source citations.',
                ],
            ],
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'How AI Systems Select Sources', 'id' => 'source-selection'],
            ],
            [
                'type' => 'paragraph',
                'props' => [
                    'content' => 'When answering a query, AI systems evaluate potential sources based on:',
                ],
            ],
            [
                'type' => 'step-list',
                'props' => [
                    'steps' => [
                        ['title' => 'Topical Relevance', 'description' => 'Does the content directly address the query?'],
                        ['title' => 'Definitional Clarity', 'description' => 'Are there clear, quotable explanations?'],
                        ['title' => 'Structural Quality', 'description' => 'Is the content well-organized and easy to parse?'],
                        ['title' => 'Authority Signals', 'description' => 'Does the source appear credible and expert?'],
                        ['title' => 'Factual Consistency', 'description' => 'Does the information align with other sources?'],
                    ],
                ],
            ],
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'Traditional vs AI Search', 'id' => 'comparison'],
            ],
            [
                'type' => 'table',
                'props' => [
                    'headers' => ['Aspect', 'Traditional Search', 'AI Search'],
                    'highlightColumn' => 2,
                    'rows' => [
                        ['Output', '10 blue links', 'Synthesized answer'],
                        ['User Action', 'Click and read multiple pages', 'Get direct answer'],
                        ['Source Display', 'Title and snippet', 'Inline citations (sometimes)'],
                        ['Ranking Basis', 'PageRank, CTR, backlinks', 'Clarity, authority, consistency'],
                        ['Content Format', 'Optimized for click-through', 'Optimized for extraction'],
                    ],
                ],
            ],
            [
                'type' => 'warning-box',
                'props' => [
                    'title' => 'Key Insight',
                    'icon' => 'lightbulb',
                    'content' => 'AI systems don\'t just rank your content — they <strong class="text-foreground">read and extract from it</strong>. Content must be written in a way that AI can understand, trust, and confidently cite.',
                ],
            ],
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'The Citation Challenge', 'id' => 'citation-challenge'],
            ],
            [
                'type' => 'paragraph',
                'props' => [
                    'content' => 'Being indexed isn\'t enough. Your content must be:',
                ],
            ],
            [
                'type' => 'list',
                'props' => [
                    'type' => 'check',
                    'items' => [
                        '<strong class="text-foreground">Parseable</strong> — structured so AI can extract key facts',
                        '<strong class="text-foreground">Quotable</strong> — contains declarative statements AI can cite verbatim',
                        '<strong class="text-foreground">Authoritative</strong> — signals expertise through depth and clarity',
                        '<strong class="text-foreground">Consistent</strong> — aligns with facts AI has learned elsewhere',
                    ],
                ],
            ],
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'Bottom Line', 'id' => 'bottom-line'],
            ],
            [
                'type' => 'highlight-box',
                'props' => [
                    'content' => '<span class="text-xl font-medium">AI search doesn\'t rank pages — it reads them, extracts answers, and decides which sources to cite. GEO optimizes your content for this new paradigm.</span>',
                    'centered' => true,
                ],
            ],
        ];
    }

    protected function convertWhyLlmsTxtMatters(): array
    {
        return [
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'What is llms.txt?', 'id' => 'what-is-llms-txt'],
            ],
            [
                'type' => 'definition',
                'props' => [
                    'term' => 'llms.txt',
                    'definition' => 'is a proposed standard file (similar to robots.txt) that provides explicit instructions to AI systems about how to interpret and cite a website\'s content.',
                ],
            ],
            [
                'type' => 'paragraph',
                'props' => [
                    'content' => 'While robots.txt tells crawlers <em>whether</em> to access content, llms.txt tells AI systems <em>how</em> to understand and reference it.',
                ],
            ],
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'Why It Matters', 'id' => 'why-it-matters'],
            ],
            [
                'type' => 'paragraph',
                'props' => [
                    'content' => 'AI systems make inferences about your content based on what they read. Without explicit guidance, they may:',
                ],
            ],
            [
                'type' => 'list',
                'props' => [
                    'type' => 'bullet',
                    'items' => [
                        'Misunderstand your site\'s primary purpose',
                        'Miss important context or qualifications',
                        'Cite outdated information',
                        'Attribute content incorrectly',
                    ],
                ],
            ],
            [
                'type' => 'paragraph',
                'props' => [
                    'content' => 'An llms.txt file gives you control over how AI systems interpret your brand and content.',
                ],
            ],
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'What to Include', 'id' => 'what-to-include'],
            ],
            [
                'type' => 'step-list',
                'props' => [
                    'steps' => [
                        ['title' => 'Site Description', 'description' => 'What your website/company does in one sentence'],
                        ['title' => 'Primary Topics', 'description' => 'The main subjects your content covers'],
                        ['title' => 'Expertise Areas', 'description' => 'Where you have authority and should be cited'],
                        ['title' => 'Content Freshness', 'description' => 'How often content is updated'],
                        ['title' => 'Citation Preferences', 'description' => 'How you prefer to be attributed'],
                    ],
                ],
            ],
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'Example Format', 'id' => 'example'],
            ],
            [
                'type' => 'code',
                'props' => [
                    'language' => 'text',
                    'content' => "# llms.txt for example.com\n\nname: Example Company\ndescription: Leading provider of cloud infrastructure solutions\n\nprimary-topics:\n  - Cloud computing\n  - DevOps practices\n  - Infrastructure automation\n\nexpertise:\n  - Kubernetes deployment\n  - CI/CD pipelines\n  - Cloud security\n\ncitation-name: Example Company\nlast-updated: 2026-01-15",
                ],
            ],
            [
                'type' => 'warning-box',
                'props' => [
                    'title' => 'Key Insight',
                    'icon' => 'lightbulb',
                    'content' => 'While llms.txt is not yet a universal standard, early adoption signals to AI systems that you\'re <strong class="text-foreground">intentionally optimizing for their comprehension</strong> — a positive authority signal.',
                ],
            ],
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'Bottom Line', 'id' => 'bottom-line'],
            ],
            [
                'type' => 'highlight-box',
                'props' => [
                    'content' => '<span class="text-xl font-medium">llms.txt is your opportunity to speak directly to AI systems and shape how they understand and cite your content.</span>',
                    'centered' => true,
                ],
            ],
        ];
    }

    protected function convertGeoScoreExplained(): array
    {
        return [
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'What Is a GEO Score?', 'id' => 'definition'],
            ],
            [
                'type' => 'definition',
                'props' => [
                    'term' => 'GEO Score',
                    'definition' => 'is a quantitative measurement of how well a website or webpage is optimized for <a href="/resources/what-is-geo" class="text-primary hover:underline">Generative Engine Optimization (GEO)</a> — the practice of making content visible and citable by AI systems.',
                ],
            ],
            [
                'type' => 'paragraph',
                'props' => [
                    'content' => 'Unlike SEO scores, a GEO Score does not measure rankings or traffic. It measures <strong class="text-foreground">citation readiness</strong> — how likely AI systems like ChatGPT, Claude, and Perplexity are to understand, trust, and reference your content.',
                ],
            ],
            [
                'type' => 'paragraph',
                'props' => [
                    'content' => 'The <a href="/" class="text-primary hover:underline">GeoSource.ai platform</a> calculates your GEO Score across up to 12 pillars, with the maximum score depending on your plan tier: <strong class="text-foreground">Free (100 pts)</strong>, <strong class="text-foreground">Pro (135 pts)</strong>, or <strong class="text-foreground">Agency (175 pts)</strong>.',
                ],
            ],
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'GEO Score Pillars', 'id' => 'pillars'],
            ],
            [
                'type' => 'paragraph',
                'props' => [
                    'content' => 'Your GEO Score is calculated based on up to 12 pillars, organized by plan tier. Each pillar measures a specific aspect of AI optimization.',
                ],
            ],
            [
                'type' => 'heading',
                'props' => ['level' => 3, 'content' => 'Free Tier (100 points max)', 'id' => 'free-tier'],
            ],
            [
                'type' => 'step-list',
                'props' => [
                    'steps' => [
                        ['title' => 'Clear Definitions (20 pts)', 'description' => 'Presence of explicit "X is..." definitions early in your content'],
                        ['title' => 'Structured Knowledge (20 pts)', 'description' => 'How well your content is organized with proper heading hierarchy and lists'],
                        ['title' => 'Topic Authority (25 pts)', 'description' => 'Depth of coverage and expertise indicators in your content'],
                        ['title' => 'Machine-Readable Formatting (15 pts)', 'description' => 'Technical optimization including schema markup and semantic HTML'],
                        ['title' => 'High-Confidence Answerability (20 pts)', 'description' => 'Declarative statements that AI can confidently quote and cite'],
                    ],
                ],
            ],
            [
                'type' => 'heading',
                'props' => ['level' => 3, 'content' => 'Pro Tier (+35 points)', 'id' => 'pro-tier'],
            ],
            [
                'type' => 'step-list',
                'props' => [
                    'steps' => [
                        ['title' => 'E-E-A-T Signals (15 pts)', 'description' => 'Experience, Expertise, Authoritativeness, and Trustworthiness indicators'],
                        ['title' => 'Citations & Sources (12 pts)', 'description' => 'External authoritative links and proper citation practices'],
                        ['title' => 'AI Crawler Access (8 pts)', 'description' => 'Technical accessibility for AI crawlers and systems'],
                    ],
                ],
            ],
            [
                'type' => 'heading',
                'props' => ['level' => 3, 'content' => 'Agency Tier (+40 points)', 'id' => 'agency-tier'],
            ],
            [
                'type' => 'step-list',
                'props' => [
                    'steps' => [
                        ['title' => 'Content Freshness (10 pts)', 'description' => 'Recency signals and regular content updates'],
                        ['title' => 'Readability (10 pts)', 'description' => 'Clear, accessible writing that AI can easily parse'],
                        ['title' => 'Question Coverage (10 pts)', 'description' => 'Direct answers to common questions users ask AI'],
                        ['title' => 'Multimedia Content (10 pts)', 'description' => 'Visual elements that enhance content understanding'],
                    ],
                ],
            ],
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'Grade Breakdown', 'id' => 'grades'],
            ],
            [
                'type' => 'paragraph',
                'props' => [
                    'content' => 'GEO Scores are converted to letter grades for easy interpretation:',
                ],
            ],
            [
                'type' => 'table',
                'props' => [
                    'headers' => ['Grade', 'Score Range', 'Description'],
                    'highlightColumn' => -1,
                    'rows' => [
                        ['A+ (90%+)', 'Exceptional', 'Content is fully optimized for AI citation'],
                        ['A (85-89%)', 'Excellent', 'Minor improvements possible'],
                        ['A- (80-84%)', 'Very Good', 'Well-structured with clear definitions'],
                        ['B+ (75-79%)', 'Good', 'Some optimization gaps to address'],
                        ['B (70-74%)', 'Above Average', 'Needs structural improvements'],
                        ['B- (65-69%)', 'Decent', 'Multiple areas need work'],
                        ['C+ (60-64%)', 'Average', 'Significant optimization needed'],
                        ['C (55-59%)', 'Below Average', 'Missing key GEO elements'],
                        ['C- (50-54%)', 'Poor', 'Unlikely to be cited by AI'],
                        ['D/F (<50%)', 'Failing', 'Major improvements needed'],
                    ],
                ],
            ],
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'Why GEO Score ≠ SEO Score', 'id' => 'geo-vs-seo'],
            ],
            [
                'type' => 'warning-box',
                'props' => [
                    'title' => 'Important Distinction',
                    'icon' => 'lightbulb',
                    'content' => 'A page can have excellent SEO (ranking #1 on Google) but a poor GEO Score (never cited by ChatGPT). These are fundamentally different metrics measuring different things.',
                ],
            ],
            [
                'type' => 'table',
                'props' => [
                    'headers' => ['Aspect', 'SEO Score', 'GEO Score'],
                    'highlightColumn' => 2,
                    'rows' => [
                        ['What it measures', 'Search engine rankings', 'AI comprehension readiness'],
                        ['Success metric', 'Traffic and clicks', 'Citations and references'],
                        ['Optimization focus', 'Keywords and backlinks', 'Clarity and structure'],
                        ['Target system', 'Google, Bing crawlers', 'LLMs like ChatGPT, Claude'],
                        ['Content goal', 'Rank on SERPs', 'Be cited in AI answers'],
                    ],
                ],
            ],
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'Bottom Line', 'id' => 'bottom-line'],
            ],
            [
                'type' => 'highlight-box',
                'props' => [
                    'content' => '<span class="text-xl font-medium">Your GEO Score reveals how AI sees your content — optimize for clarity, structure, and authority to maximize your citation potential.</span>',
                    'centered' => true,
                ],
            ],
        ];
    }

    protected function convertDefinitions(): array
    {
        return [
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'Core GEO Terms', 'id' => 'core-terms'],
            ],
            [
                'type' => 'definition',
                'props' => [
                    'term' => 'Generative Engine Optimization (GEO)',
                    'definition' => 'is the practice of optimizing digital content so it can be accurately understood, trusted, and cited by generative AI systems such as ChatGPT, Google AI Overviews, Perplexity, Claude, and other large language models (LLMs).',
                    'source' => 'GeoSource.ai',
                ],
            ],
            [
                'type' => 'definition',
                'props' => [
                    'term' => 'GEO Score',
                    'definition' => 'is a quantitative measurement of how well a website or webpage is optimized for generative AI understanding and citation. Higher scores indicate better AI comprehension readiness.',
                    'source' => 'GeoSource.ai',
                ],
            ],
            [
                'type' => 'definition',
                'props' => [
                    'term' => 'AI Citation',
                    'definition' => 'occurs when a generative AI system references or quotes content from a website in its response. Being cited indicates that AI found your content trustworthy and relevant.',
                    'source' => 'GeoSource.ai',
                ],
            ],
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'Technical Terms', 'id' => 'technical-terms'],
            ],
            [
                'type' => 'definition',
                'props' => [
                    'term' => 'llms.txt',
                    'definition' => 'is a proposed standard file (similar to robots.txt) that provides explicit instructions to AI systems about how to interpret and cite a website\'s content.',
                ],
            ],
            [
                'type' => 'definition',
                'props' => [
                    'term' => 'JSON-LD',
                    'definition' => 'is a JavaScript Object Notation for Linked Data format used to embed structured data in web pages. It helps AI systems understand the semantic meaning of content.',
                ],
            ],
            [
                'type' => 'definition',
                'props' => [
                    'term' => 'Schema Markup',
                    'definition' => 'is standardized vocabulary (from Schema.org) used to mark up web pages so that search engines and AI systems can better understand the content.',
                ],
            ],
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'Quality Signals', 'id' => 'quality-signals'],
            ],
            [
                'type' => 'definition',
                'props' => [
                    'term' => 'E-E-A-T',
                    'definition' => 'stands for Experience, Expertise, Authoritativeness, and Trustworthiness. These quality signals help AI systems evaluate content credibility.',
                    'source' => 'Google Quality Guidelines',
                ],
            ],
            [
                'type' => 'definition',
                'props' => [
                    'term' => 'Topical Authority',
                    'definition' => 'refers to the depth and breadth of a website\'s coverage on a specific topic. Higher topical authority increases the likelihood of AI citation.',
                ],
            ],
            [
                'type' => 'definition',
                'props' => [
                    'term' => 'High-Confidence Answerability',
                    'definition' => 'describes content written in declarative, quotable statements that AI systems can confidently cite without ambiguity.',
                ],
            ],
        ];
    }

    protected function convertGeoOptimizationChecklist(): array
    {
        return [
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'Content Structure', 'id' => 'content-structure'],
            ],
            [
                'type' => 'checklist',
                'props' => [
                    'items' => [
                        ['text' => 'Single H1 heading clearly stating the page topic', 'checked' => false],
                        ['text' => 'Multiple H2 subheadings organizing content logically', 'checked' => false],
                        ['text' => 'Clear "X is..." definition in the first paragraph', 'checked' => false],
                        ['text' => 'Bullet or numbered lists for key points', 'checked' => false],
                        ['text' => 'Short paragraphs (50-100 words each)', 'checked' => false],
                    ],
                ],
            ],
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'Technical Optimization', 'id' => 'technical'],
            ],
            [
                'type' => 'checklist',
                'props' => [
                    'items' => [
                        ['text' => 'JSON-LD structured data implemented', 'checked' => false],
                        ['text' => 'Semantic HTML elements (article, section, nav)', 'checked' => false],
                        ['text' => 'Alt text on all images', 'checked' => false],
                        ['text' => 'llms.txt file in root directory', 'checked' => false],
                        ['text' => 'robots.txt allows AI crawlers', 'checked' => false],
                    ],
                ],
            ],
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'Authority Signals', 'id' => 'authority'],
            ],
            [
                'type' => 'checklist',
                'props' => [
                    'items' => [
                        ['text' => 'Author name and bio displayed', 'checked' => false],
                        ['text' => 'Publish date visible', 'checked' => false],
                        ['text' => 'Last updated date shown', 'checked' => false],
                        ['text' => 'Links to authoritative sources', 'checked' => false],
                        ['text' => 'Contact information available', 'checked' => false],
                    ],
                ],
            ],
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'Content Quality', 'id' => 'quality'],
            ],
            [
                'type' => 'checklist',
                'props' => [
                    'items' => [
                        ['text' => '800+ words of substantive content', 'checked' => false],
                        ['text' => 'Declarative, quotable statements', 'checked' => false],
                        ['text' => 'FAQ section with common questions', 'checked' => false],
                        ['text' => '8th-9th grade reading level', 'checked' => false],
                        ['text' => 'Original insights and data', 'checked' => false],
                    ],
                ],
            ],
            [
                'type' => 'warning-box',
                'props' => [
                    'title' => 'Pro Tip',
                    'icon' => 'lightbulb',
                    'content' => 'Focus on the fundamentals first: clear definitions, proper structure, and authoritative content. Advanced optimizations build on this foundation.',
                ],
            ],
        ];
    }

    protected function convertAiSearchVisibilityGuide(): array
    {
        return [
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'Understanding AI Search', 'id' => 'understanding'],
            ],
            [
                'type' => 'paragraph',
                'props' => [
                    'content' => 'AI search visibility refers to how often and accurately your content appears in AI-generated responses. Unlike traditional search rankings, AI visibility depends on <strong class="text-foreground">comprehension, trust, and relevance</strong> rather than backlinks and keywords.',
                ],
            ],
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'Key Factors for AI Visibility', 'id' => 'factors'],
            ],
            [
                'type' => 'step-list',
                'props' => [
                    'steps' => [
                        ['title' => 'Content Clarity', 'description' => 'Write explicit definitions and declarative statements AI can quote'],
                        ['title' => 'Structural Organization', 'description' => 'Use headings, lists, and logical flow for easy parsing'],
                        ['title' => 'Topical Depth', 'description' => 'Cover topics comprehensively with supporting evidence'],
                        ['title' => 'Technical Accessibility', 'description' => 'Implement schema markup and allow AI crawler access'],
                        ['title' => 'Authority Indicators', 'description' => 'Display credentials, sources, and trust signals'],
                    ],
                ],
            ],
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'Common Visibility Blockers', 'id' => 'blockers'],
            ],
            [
                'type' => 'list',
                'props' => [
                    'type' => 'bullet',
                    'items' => [
                        'Ambiguous or hedging language ("might", "possibly", "could be")',
                        'Complex sentence structures AI struggles to parse',
                        'Missing definitions for key terms',
                        'Thin content without substantive information',
                        'Blocking AI crawlers via robots.txt',
                    ],
                ],
            ],
            [
                'type' => 'warning-box',
                'props' => [
                    'title' => 'Key Insight',
                    'icon' => 'lightbulb',
                    'content' => 'AI systems prefer content they can confidently understand and cite. <strong class="text-foreground">Clarity beats cleverness</strong> when optimizing for AI visibility.',
                ],
            ],
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'Measuring Your AI Visibility', 'id' => 'measuring'],
            ],
            [
                'type' => 'paragraph',
                'props' => [
                    'content' => 'Use tools like <a href="/" class="text-primary hover:underline">GeoSource.ai</a> to measure your GEO Score and identify specific areas for improvement in your AI search visibility.',
                ],
            ],
        ];
    }

    protected function convertHowLlmsCiteSources(): array
    {
        return [
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'The Citation Decision Process', 'id' => 'process'],
            ],
            [
                'type' => 'paragraph',
                'props' => [
                    'content' => 'When generating responses, LLMs don\'t simply search for keywords. They <strong class="text-foreground">evaluate sources based on confidence, clarity, and authority</strong> to decide what to cite.',
                ],
            ],
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'What Makes Content Citable', 'id' => 'citable'],
            ],
            [
                'type' => 'step-list',
                'props' => [
                    'steps' => [
                        ['title' => 'Explicit Definitions', 'description' => '"X is Y" statements that directly answer questions'],
                        ['title' => 'Declarative Authority', 'description' => 'Confident statements without excessive hedging'],
                        ['title' => 'Factual Consistency', 'description' => 'Information that aligns with other trusted sources'],
                        ['title' => 'Structural Clarity', 'description' => 'Well-organized content that\'s easy to extract from'],
                        ['title' => 'Topical Relevance', 'description' => 'Content that directly addresses the query topic'],
                    ],
                ],
            ],
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'Citation Patterns', 'id' => 'patterns'],
            ],
            [
                'type' => 'table',
                'props' => [
                    'headers' => ['Content Type', 'Citation Likelihood', 'Why'],
                    'highlightColumn' => -1,
                    'rows' => [
                        ['Clear definitions', 'High', 'Easy to quote verbatim'],
                        ['How-to guides', 'Medium-High', 'Actionable, structured steps'],
                        ['Opinion pieces', 'Low', 'Subjective, hard to verify'],
                        ['Dense academic text', 'Medium', 'Authoritative but hard to parse'],
                        ['FAQ pages', 'High', 'Direct question-answer format'],
                    ],
                ],
            ],
            [
                'type' => 'warning-box',
                'props' => [
                    'title' => 'Key Insight',
                    'icon' => 'lightbulb',
                    'content' => 'LLMs cite content they can <strong class="text-foreground">confidently extract and attribute</strong>. The easier you make it for AI to quote you accurately, the more likely you\'ll be cited.',
                ],
            ],
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'Improving Your Citation Rate', 'id' => 'improving'],
            ],
            [
                'type' => 'list',
                'props' => [
                    'type' => 'check',
                    'items' => [
                        'Start sections with clear, quotable definitions',
                        'Use declarative language ("X is" not "X might be")',
                        'Structure content with clear headings and lists',
                        'Include FAQ sections for common questions',
                        'Cite authoritative sources to build trust',
                    ],
                ],
            ],
        ];
    }

    protected function convertGeoContentFramework(): array
    {
        return [
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'The GEO Content Framework', 'id' => 'framework'],
            ],
            [
                'type' => 'paragraph',
                'props' => [
                    'content' => 'The GEO Content Framework provides a structured approach to creating content that AI systems can easily understand, trust, and cite.',
                ],
            ],
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'Framework Components', 'id' => 'components'],
            ],
            [
                'type' => 'step-list',
                'props' => [
                    'steps' => [
                        ['title' => 'Define', 'description' => 'Start with a clear, explicit definition of your topic'],
                        ['title' => 'Structure', 'description' => 'Organize with headings, lists, and logical flow'],
                        ['title' => 'Support', 'description' => 'Back claims with evidence, examples, and citations'],
                        ['title' => 'Clarify', 'description' => 'Remove ambiguity with declarative statements'],
                        ['title' => 'Optimize', 'description' => 'Add technical markup (schema, llms.txt)'],
                    ],
                ],
            ],
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'Content Structure Template', 'id' => 'template'],
            ],
            [
                'type' => 'list',
                'props' => [
                    'type' => 'numbered',
                    'items' => [
                        '<strong class="text-foreground">Opening Definition</strong> — "X is..." statement in paragraph 1',
                        '<strong class="text-foreground">Context</strong> — Why this topic matters',
                        '<strong class="text-foreground">Key Points</strong> — Main information in structured format',
                        '<strong class="text-foreground">Supporting Evidence</strong> — Data, examples, citations',
                        '<strong class="text-foreground">Summary</strong> — Restate key takeaways',
                        '<strong class="text-foreground">Related Topics</strong> — Internal links to related content',
                    ],
                ],
            ],
            [
                'type' => 'warning-box',
                'props' => [
                    'title' => 'Pro Tip',
                    'icon' => 'lightbulb',
                    'content' => 'Think of your content as a knowledge base entry, not a blog post. AI systems are looking for <strong class="text-foreground">authoritative, factual information</strong> they can confidently share.',
                ],
            ],
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'Bottom Line', 'id' => 'bottom-line'],
            ],
            [
                'type' => 'highlight-box',
                'props' => [
                    'content' => '<span class="text-xl font-medium">The GEO Content Framework helps you create content that\'s optimized for AI comprehension from the ground up.</span>',
                    'centered' => true,
                ],
            ],
        ];
    }

    protected function convertWhySsrMattersForGeo(): array
    {
        return [
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'What is Server-Side Rendering?', 'id' => 'what-is-ssr'],
            ],
            [
                'type' => 'definition',
                'props' => [
                    'term' => 'Server-Side Rendering (SSR)',
                    'definition' => 'is the process of rendering web pages on the server and sending fully-formed HTML to the browser, rather than relying on JavaScript to build the page on the client side.',
                ],
            ],
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'Why SSR Matters for GEO', 'id' => 'why-matters'],
            ],
            [
                'type' => 'paragraph',
                'props' => [
                    'content' => 'AI crawlers, like traditional search crawlers, need to access your content to index and cite it. <strong class="text-foreground">Client-side rendered content may not be visible</strong> to these systems.',
                ],
            ],
            [
                'type' => 'table',
                'props' => [
                    'headers' => ['Rendering Type', 'AI Crawler Visibility', 'GEO Impact'],
                    'highlightColumn' => 2,
                    'rows' => [
                        ['Server-Side (SSR)', 'Full visibility', 'Optimal for GEO'],
                        ['Static Site Generation (SSG)', 'Full visibility', 'Optimal for GEO'],
                        ['Client-Side (CSR)', 'Limited/None', 'Poor for GEO'],
                        ['Hybrid', 'Depends on implementation', 'Variable'],
                    ],
                ],
            ],
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'The Problem with Client-Side Rendering', 'id' => 'csr-problem'],
            ],
            [
                'type' => 'list',
                'props' => [
                    'type' => 'bullet',
                    'items' => [
                        'AI crawlers may not execute JavaScript',
                        'Content loads after initial page request',
                        'Dynamic content may be invisible to indexers',
                        'Important definitions and structured data may be missed',
                    ],
                ],
            ],
            [
                'type' => 'warning-box',
                'props' => [
                    'title' => 'Key Insight',
                    'icon' => 'lightbulb',
                    'content' => 'If AI systems can\'t see your content, they can\'t cite it. <strong class="text-foreground">SSR ensures your content is visible</strong> to all crawlers and AI systems.',
                ],
            ],
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'SSR Implementation Options', 'id' => 'options'],
            ],
            [
                'type' => 'list',
                'props' => [
                    'type' => 'check',
                    'items' => [
                        '<strong class="text-foreground">Next.js</strong> — React framework with built-in SSR',
                        '<strong class="text-foreground">Nuxt.js</strong> — Vue framework with SSR support',
                        '<strong class="text-foreground">Laravel + Inertia</strong> — PHP with Vue/React SSR',
                        '<strong class="text-foreground">Static Site Generators</strong> — Gatsby, Hugo, Jekyll',
                    ],
                ],
            ],
        ];
    }

    protected function convertEEATAndGeo(): array
    {
        return [
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'What is E-E-A-T?', 'id' => 'what-is-eeat'],
            ],
            [
                'type' => 'definition',
                'props' => [
                    'term' => 'E-E-A-T',
                    'definition' => 'stands for Experience, Expertise, Authoritativeness, and Trustworthiness. These quality signals help search engines and AI systems evaluate content credibility.',
                    'source' => 'Google Quality Guidelines',
                ],
            ],
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'E-E-A-T Components', 'id' => 'components'],
            ],
            [
                'type' => 'heading',
                'props' => ['level' => 3, 'content' => 'Experience', 'id' => 'experience'],
            ],
            [
                'type' => 'paragraph',
                'props' => [
                    'content' => 'First-hand experience with the topic demonstrates practical knowledge:',
                ],
            ],
            [
                'type' => 'list',
                'props' => [
                    'type' => 'bullet',
                    'items' => [
                        'Personal accounts and case studies',
                        'Real-world examples and applications',
                        'Practical insights from direct involvement',
                    ],
                ],
            ],
            [
                'type' => 'heading',
                'props' => ['level' => 3, 'content' => 'Expertise', 'id' => 'expertise'],
            ],
            [
                'type' => 'paragraph',
                'props' => [
                    'content' => 'Deep knowledge and skill in the subject area:',
                ],
            ],
            [
                'type' => 'list',
                'props' => [
                    'type' => 'bullet',
                    'items' => [
                        'Professional credentials and qualifications',
                        'Depth of technical knowledge',
                        'Industry recognition and publications',
                    ],
                ],
            ],
            [
                'type' => 'heading',
                'props' => ['level' => 3, 'content' => 'Authoritativeness', 'id' => 'authoritativeness'],
            ],
            [
                'type' => 'paragraph',
                'props' => [
                    'content' => 'Recognition as a trusted source in the field:',
                ],
            ],
            [
                'type' => 'list',
                'props' => [
                    'type' => 'bullet',
                    'items' => [
                        'Citations from other authoritative sources',
                        'Media mentions and expert features',
                        'Industry awards and recognition',
                    ],
                ],
            ],
            [
                'type' => 'heading',
                'props' => ['level' => 3, 'content' => 'Trustworthiness', 'id' => 'trustworthiness'],
            ],
            [
                'type' => 'paragraph',
                'props' => [
                    'content' => 'Signals that establish reliability and credibility:',
                ],
            ],
            [
                'type' => 'list',
                'props' => [
                    'type' => 'bullet',
                    'items' => [
                        'Accurate, fact-checked information',
                        'Transparent about sources and methods',
                        'Clear contact information and accountability',
                    ],
                ],
            ],
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'E-E-A-T Impact on GEO', 'id' => 'geo-impact'],
            ],
            [
                'type' => 'warning-box',
                'props' => [
                    'title' => 'Key Insight',
                    'icon' => 'lightbulb',
                    'content' => 'AI systems use E-E-A-T signals to determine which sources to trust and cite. <strong class="text-foreground">Strong E-E-A-T signals increase your citation likelihood.</strong>',
                ],
            ],
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'Implementing E-E-A-T', 'id' => 'implementation'],
            ],
            [
                'type' => 'checklist',
                'props' => [
                    'items' => [
                        ['text' => 'Add author bylines with credentials', 'checked' => false],
                        ['text' => 'Include author bio pages', 'checked' => false],
                        ['text' => 'Display publish and update dates', 'checked' => false],
                        ['text' => 'Link to authoritative sources', 'checked' => false],
                        ['text' => 'Show reviews and testimonials', 'checked' => false],
                        ['text' => 'Provide clear contact information', 'checked' => false],
                    ],
                ],
            ],
        ];
    }

    protected function convertAiCitationsAndGeo(): array
    {
        return [
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'What Are AI Citations?', 'id' => 'what-are'],
            ],
            [
                'type' => 'definition',
                'props' => [
                    'term' => 'AI Citation',
                    'definition' => 'occurs when a generative AI system references, quotes, or attributes information to a specific source in its response. This indicates that AI found your content trustworthy and relevant.',
                ],
            ],
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'Why Citations Matter', 'id' => 'why-matter'],
            ],
            [
                'type' => 'list',
                'props' => [
                    'type' => 'check',
                    'items' => [
                        '<strong class="text-foreground">Visibility</strong> — Your brand appears in AI responses',
                        '<strong class="text-foreground">Traffic</strong> — Users click through to your site',
                        '<strong class="text-foreground">Authority</strong> — Being cited builds credibility',
                        '<strong class="text-foreground">Trust</strong> — AI endorsement signals reliability',
                    ],
                ],
            ],
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'How to Get Cited', 'id' => 'how-to'],
            ],
            [
                'type' => 'step-list',
                'props' => [
                    'steps' => [
                        ['title' => 'Write Clear Definitions', 'description' => 'Start with "X is..." statements AI can quote'],
                        ['title' => 'Use Declarative Language', 'description' => 'Avoid hedging; be confident and direct'],
                        ['title' => 'Structure for Extraction', 'description' => 'Use headings and lists for easy parsing'],
                        ['title' => 'Build Authority', 'description' => 'Cite sources and show expertise'],
                        ['title' => 'Enable Crawling', 'description' => 'Allow AI bots in robots.txt'],
                    ],
                ],
            ],
            [
                'type' => 'warning-box',
                'props' => [
                    'title' => 'Key Insight',
                    'icon' => 'lightbulb',
                    'content' => 'AI systems cite content they can <strong class="text-foreground">confidently quote and attribute</strong>. Make your content easy to extract and cite accurately.',
                ],
            ],
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'Citation Best Practices', 'id' => 'best-practices'],
            ],
            [
                'type' => 'table',
                'props' => [
                    'headers' => ['Practice', 'Impact', 'Example'],
                    'highlightColumn' => -1,
                    'rows' => [
                        ['Clear definitions', 'High', '"GEO is the practice of..."'],
                        ['Structured content', 'High', 'H2 headings, bullet lists'],
                        ['Author attribution', 'Medium', 'Byline with credentials'],
                        ['Source citations', 'Medium', 'Links to authoritative sources'],
                        ['FAQ sections', 'High', 'Direct question-answer format'],
                    ],
                ],
            ],
        ];
    }

    protected function convertAiAccessibilityForGeo(): array
    {
        return [
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'What is AI Accessibility?', 'id' => 'what-is'],
            ],
            [
                'type' => 'definition',
                'props' => [
                    'term' => 'AI Accessibility',
                    'definition' => 'refers to the technical measures that allow AI crawlers and systems to access, read, and index your website content. Without proper accessibility, AI cannot cite your content.',
                ],
            ],
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'Key Accessibility Factors', 'id' => 'factors'],
            ],
            [
                'type' => 'step-list',
                'props' => [
                    'steps' => [
                        ['title' => 'robots.txt Configuration', 'description' => 'Allow AI crawlers like GPTBot and anthropic-ai'],
                        ['title' => 'No Blocking Directives', 'description' => 'Avoid noindex, nofollow on important content'],
                        ['title' => 'Server-Side Rendering', 'description' => 'Ensure content is visible without JavaScript'],
                        ['title' => 'Sitemap Availability', 'description' => 'Provide XML sitemap for discovery'],
                        ['title' => 'Fast Load Times', 'description' => 'Ensure quick access for crawlers'],
                    ],
                ],
            ],
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'robots.txt for AI Crawlers', 'id' => 'robots-txt'],
            ],
            [
                'type' => 'code',
                'props' => [
                    'language' => 'text',
                    'content' => "# Allow AI crawlers\nUser-agent: GPTBot\nAllow: /\n\nUser-agent: anthropic-ai\nAllow: /\n\nUser-agent: Claude-Web\nAllow: /\n\nUser-agent: PerplexityBot\nAllow: /\n\n# Sitemap\nSitemap: https://example.com/sitemap.xml",
                ],
            ],
            [
                'type' => 'warning-box',
                'props' => [
                    'title' => 'Key Insight',
                    'icon' => 'lightbulb',
                    'content' => 'Many websites accidentally block AI crawlers. <strong class="text-foreground">Check your robots.txt</strong> to ensure you\'re not preventing AI systems from accessing your content.',
                ],
            ],
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'Accessibility Checklist', 'id' => 'checklist'],
            ],
            [
                'type' => 'checklist',
                'props' => [
                    'items' => [
                        ['text' => 'robots.txt allows GPTBot, anthropic-ai, and other AI crawlers', 'checked' => false],
                        ['text' => 'No noindex/nosnippet on important pages', 'checked' => false],
                        ['text' => 'Content renders server-side (SSR/SSG)', 'checked' => false],
                        ['text' => 'XML sitemap is available and submitted', 'checked' => false],
                        ['text' => 'Page load time under 3 seconds', 'checked' => false],
                    ],
                ],
            ],
        ];
    }

    protected function convertContentFreshnessForGeo(): array
    {
        return [
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'Why Freshness Matters', 'id' => 'why-matters'],
            ],
            [
                'type' => 'paragraph',
                'props' => [
                    'content' => 'AI systems consider content recency when deciding what to cite. <strong class="text-foreground">Fresh, updated content signals relevance and accuracy</strong> for current queries.',
                ],
            ],
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'Freshness Signals', 'id' => 'signals'],
            ],
            [
                'type' => 'step-list',
                'props' => [
                    'steps' => [
                        ['title' => 'Visible Publish Date', 'description' => 'Show when content was originally published'],
                        ['title' => 'Last Updated Date', 'description' => 'Display when content was most recently modified'],
                        ['title' => 'Current Year References', 'description' => 'Include current year in relevant content'],
                        ['title' => 'Schema Markup', 'description' => 'Use datePublished and dateModified in JSON-LD'],
                        ['title' => 'Regular Updates', 'description' => 'Refresh content periodically with new information'],
                    ],
                ],
            ],
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'Implementation', 'id' => 'implementation'],
            ],
            [
                'type' => 'code',
                'props' => [
                    'language' => 'json',
                    'content' => "{\n  \"@type\": \"Article\",\n  \"datePublished\": \"2026-01-15\",\n  \"dateModified\": \"2026-01-20\",\n  \"author\": {\n    \"@type\": \"Person\",\n    \"name\": \"Jane Smith\"\n  }\n}",
                ],
            ],
            [
                'type' => 'warning-box',
                'props' => [
                    'title' => 'Key Insight',
                    'icon' => 'lightbulb',
                    'content' => 'Content with visible dates and regular updates is more likely to be cited for current queries. <strong class="text-foreground">Evergreen content still benefits from periodic refreshes.</strong>',
                ],
            ],
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'Freshness Checklist', 'id' => 'checklist'],
            ],
            [
                'type' => 'checklist',
                'props' => [
                    'items' => [
                        ['text' => 'Publish date visible on page', 'checked' => false],
                        ['text' => 'Last updated date displayed', 'checked' => false],
                        ['text' => 'datePublished in schema markup', 'checked' => false],
                        ['text' => 'dateModified in schema markup', 'checked' => false],
                        ['text' => 'Content reviewed and updated quarterly', 'checked' => false],
                    ],
                ],
            ],
        ];
    }

    protected function convertReadabilityAndGeo(): array
    {
        return [
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'Why Readability Matters for AI', 'id' => 'why-matters'],
            ],
            [
                'type' => 'paragraph',
                'props' => [
                    'content' => 'AI systems parse and extract information more accurately from clear, well-structured text. <strong class="text-foreground">Simple, accessible writing increases citation likelihood.</strong>',
                ],
            ],
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'Optimal Readability Targets', 'id' => 'targets'],
            ],
            [
                'type' => 'table',
                'props' => [
                    'headers' => ['Metric', 'Target', 'Why'],
                    'highlightColumn' => 1,
                    'rows' => [
                        ['Reading Level', '8th-9th grade', 'Accessible to AI and humans'],
                        ['Sentence Length', '15-20 words', 'Easy to parse and quote'],
                        ['Paragraph Length', '50-100 words', 'Digestible chunks'],
                        ['Vocabulary', 'Common words', 'Reduces ambiguity'],
                    ],
                ],
            ],
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'Readability Best Practices', 'id' => 'best-practices'],
            ],
            [
                'type' => 'list',
                'props' => [
                    'type' => 'check',
                    'items' => [
                        '<strong class="text-foreground">Use active voice</strong> — "AI cites clear content" not "Clear content is cited by AI"',
                        '<strong class="text-foreground">Avoid jargon</strong> — Define technical terms when first used',
                        '<strong class="text-foreground">One idea per sentence</strong> — Keep sentences focused',
                        '<strong class="text-foreground">Short paragraphs</strong> — Break up long blocks of text',
                        '<strong class="text-foreground">Use lists</strong> — Structure information for easy scanning',
                    ],
                ],
            ],
            [
                'type' => 'warning-box',
                'props' => [
                    'title' => 'Key Insight',
                    'icon' => 'lightbulb',
                    'content' => 'Complex writing doesn\'t signal expertise to AI. <strong class="text-foreground">Clear, simple writing is more likely to be cited</strong> than dense, academic prose.',
                ],
            ],
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'Tools for Measuring Readability', 'id' => 'tools'],
            ],
            [
                'type' => 'list',
                'props' => [
                    'type' => 'bullet',
                    'items' => [
                        'Hemingway Editor — highlights complex sentences',
                        'Flesch-Kincaid tests — measures reading grade level',
                        'GeoSource.ai — includes readability in GEO Score',
                    ],
                ],
            ],
        ];
    }

    protected function convertQuestionCoverageForGeo(): array
    {
        return [
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'What is Question Coverage?', 'id' => 'what-is'],
            ],
            [
                'type' => 'definition',
                'props' => [
                    'term' => 'Question Coverage',
                    'definition' => 'refers to how well your content anticipates and answers the questions users ask AI systems. Content with strong question coverage is more likely to be cited in AI responses.',
                ],
            ],
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'Why It Matters', 'id' => 'why-matters'],
            ],
            [
                'type' => 'paragraph',
                'props' => [
                    'content' => 'Users ask AI systems questions. If your content <strong class="text-foreground">directly answers those questions</strong>, AI is more likely to cite you.',
                ],
            ],
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'Implementing Question Coverage', 'id' => 'implementing'],
            ],
            [
                'type' => 'step-list',
                'props' => [
                    'steps' => [
                        ['title' => 'FAQ Sections', 'description' => 'Add dedicated Q&A sections to your pages'],
                        ['title' => 'Question Headings', 'description' => 'Use "What is...", "How do...", "Why does..." as H2s'],
                        ['title' => 'Direct Answers', 'description' => 'Answer questions in the first sentence after the heading'],
                        ['title' => 'FAQPage Schema', 'description' => 'Mark up Q&A content with structured data'],
                        ['title' => 'Cover Common Queries', 'description' => 'Research what questions users ask about your topic'],
                    ],
                ],
            ],
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'FAQ Schema Example', 'id' => 'schema-example'],
            ],
            [
                'type' => 'code',
                'props' => [
                    'language' => 'json',
                    'content' => "{\n  \"@type\": \"FAQPage\",\n  \"mainEntity\": [{\n    \"@type\": \"Question\",\n    \"name\": \"What is GEO?\",\n    \"acceptedAnswer\": {\n      \"@type\": \"Answer\",\n      \"text\": \"GEO is the practice of optimizing content for AI systems.\"\n    }\n  }]\n}",
                ],
            ],
            [
                'type' => 'warning-box',
                'props' => [
                    'title' => 'Key Insight',
                    'icon' => 'lightbulb',
                    'content' => 'Content formatted as questions and answers matches how users query AI systems. <strong class="text-foreground">This direct match increases citation likelihood.</strong>',
                ],
            ],
        ];
    }

    protected function convertMultimediaAndGeo(): array
    {
        return [
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'Multimedia in AI Context', 'id' => 'context'],
            ],
            [
                'type' => 'paragraph',
                'props' => [
                    'content' => 'While AI text systems primarily process text, multimedia elements can <strong class="text-foreground">enhance content understanding through associated metadata and context</strong>.',
                ],
            ],
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'How Multimedia Helps GEO', 'id' => 'how-helps'],
            ],
            [
                'type' => 'step-list',
                'props' => [
                    'steps' => [
                        ['title' => 'Alt Text', 'description' => 'Descriptive alt text provides context AI can read'],
                        ['title' => 'Figure Captions', 'description' => 'Captions explain what images and charts show'],
                        ['title' => 'Tables', 'description' => 'Structured data in tables is easily parsed by AI'],
                        ['title' => 'Video Transcripts', 'description' => 'Text versions of video content for AI access'],
                        ['title' => 'Infographic Text', 'description' => 'Key points from infographics in surrounding text'],
                    ],
                ],
            ],
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'Multimedia Best Practices', 'id' => 'best-practices'],
            ],
            [
                'type' => 'list',
                'props' => [
                    'type' => 'check',
                    'items' => [
                        '<strong class="text-foreground">Every image has descriptive alt text</strong>',
                        '<strong class="text-foreground">Data visualizations have text explanations</strong>',
                        '<strong class="text-foreground">Tables use proper HTML markup with headers</strong>',
                        '<strong class="text-foreground">Videos include transcripts or summaries</strong>',
                        '<strong class="text-foreground">Infographics have key points in body text</strong>',
                    ],
                ],
            ],
            [
                'type' => 'warning-box',
                'props' => [
                    'title' => 'Key Insight',
                    'icon' => 'lightbulb',
                    'content' => 'AI can\'t see images, but it can read alt text and captions. <strong class="text-foreground">Make your visual content text-accessible</strong> for maximum GEO benefit.',
                ],
            ],
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'Alt Text Example', 'id' => 'alt-example'],
            ],
            [
                'type' => 'code',
                'props' => [
                    'language' => 'html',
                    'content' => "<!-- Bad: Non-descriptive -->\n<img src=\"chart.png\" alt=\"chart\">\n\n<!-- Good: Descriptive -->\n<img src=\"chart.png\" alt=\"Bar chart showing GEO Score improvements: 45% to 82% over 3 months after optimization\">\n\n<!-- Better: With figure caption -->\n<figure>\n  <img src=\"chart.png\" alt=\"GEO Score improvement chart\">\n  <figcaption>GEO Score improved from 45% to 82% over 3 months following implementation of structured content and schema markup.</figcaption>\n</figure>",
                ],
            ],
        ];
    }

    protected function convertDesigningContentForAiSnippetExtraction(): array
    {
        return [
            // Definition
            [
                'type' => 'definition',
                'props' => [
                    'term' => 'AI Snippet Extraction',
                    'definition' => 'is the process by which generative AI engines identify concise, authoritative text blocks from web content to include directly in AI-generated responses. Content must be structured for extraction to be cited.',
                ],
            ],
            [
                'type' => 'paragraph',
                'props' => [
                    'content' => 'Whether it\'s ChatGPT, Perplexity, Gemini, or Claude, modern generative systems scan your content looking for <strong class="text-foreground">clean, trustworthy, machine-readable blocks</strong> they can confidently cite.',
                ],
            ],
            [
                'type' => 'warning-box',
                'props' => [
                    'title' => 'Key Insight',
                    'icon' => 'lightbulb',
                    'content' => 'If your content isn\'t structured for extraction, it doesn\'t matter how good it is — <strong class="text-foreground">AI won\'t use it</strong>.',
                ],
            ],

            // Why AI Snippet Extraction Matters
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'Why AI Snippet Extraction Matters', 'id' => 'why-it-matters'],
            ],
            [
                'type' => 'table',
                'props' => [
                    'headers' => ['Traditional SEO Optimizes For', 'GEO Optimizes For'],
                    'highlightColumn' => 1,
                    'rows' => [
                        ['Rankings', 'Answer clarity'],
                        ['Keywords', 'Semantic structure'],
                        ['Backlinks', 'Citation confidence'],
                    ],
                ],
            ],
            [
                'type' => 'paragraph',
                'props' => [
                    'content' => 'AI models don\'t scroll. They don\'t skim. They don\'t "discover" content like humans do. <strong class="text-foreground">They extract.</strong> Your job is to make extraction effortless.',
                ],
            ],

            // Headings
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'How to Use Headings for Machine Hierarchy', 'id' => 'headings'],
            ],
            [
                'type' => 'paragraph',
                'props' => [
                    'content' => 'Headings tell AI what the page is about and how information is organized. AI systems favor <strong class="text-foreground">descriptive, explicit headings</strong> that map cleanly to questions.',
                ],
            ],
            [
                'type' => 'step-list',
                'props' => [
                    'steps' => [
                        ['title' => 'One H1 per page', 'description' => 'Clearly states the main topic'],
                        ['title' => 'Logical H2 → H3 structure', 'description' => 'Creates scannable hierarchy'],
                        ['title' => 'Never skip heading levels', 'description' => 'Maintains semantic structure'],
                        ['title' => 'One idea per section', 'description' => 'Enables clean extraction'],
                    ],
                ],
            ],
            [
                'type' => 'table',
                'props' => [
                    'headers' => ['Example', 'Quality', 'Why'],
                    'highlightColumn' => -1,
                    'rows' => [
                        ['H2: What is AI snippet extraction?', '✅ Good', 'Descriptive, question-format'],
                        ['H2: Overview', '❌ Bad', 'Vague, non-specific'],
                    ],
                ],
            ],
            [
                'type' => 'info-box',
                'props' => [
                    'title' => 'Pro Tip: Use Question Headings',
                    'content' => 'Format headings as natural language questions (What is..., How does..., Why does...) to dramatically increase snippet eligibility. This mirrors how AI systems generate queries internally.',
                ],
            ],

            // Answer-First Content
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'How to Design Answer-First Content Blocks', 'id' => 'answer-first'],
            ],
            [
                'type' => 'paragraph',
                'props' => [
                    'content' => 'AI prefers content that delivers the answer immediately. Every important section should follow this pattern:',
                ],
            ],
            [
                'type' => 'highlight-box',
                'props' => [
                    'content' => '<strong class="text-lg">Answer → Explanation → Example</strong>',
                    'centered' => true,
                ],
            ],
            [
                'type' => 'paragraph',
                'props' => [
                    'content' => 'This format supports <strong class="text-foreground">high-confidence answerability</strong>, <strong class="text-foreground">reduced ambiguity</strong>, and <strong class="text-foreground">easier citation</strong> — one of the most important GEO pillars.',
                ],
            ],

            // Short Paragraphs
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'Why Short, Declarative Paragraphs Matter', 'id' => 'paragraphs'],
            ],
            [
                'type' => 'paragraph',
                'props' => [
                    'content' => 'AI models perform best with clear, concise text blocks:',
                ],
            ],
            [
                'type' => 'list',
                'props' => [
                    'type' => 'check',
                    'items' => [
                        '<strong class="text-foreground">2–4 sentence paragraphs</strong>',
                        '<strong class="text-foreground">50–100 words maximum</strong>',
                        '<strong class="text-foreground">Clear subject–verb structure</strong>',
                    ],
                ],
            ],
            [
                'type' => 'paragraph',
                'props' => [
                    'content' => 'Avoid long narrative blocks, storytelling before definitions, and vague introductions. <strong class="text-foreground">Write like you\'re answering a question — not writing an essay.</strong>',
                ],
            ],

            // Lists
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'Why Lists Are AI Gold', 'id' => 'lists'],
            ],
            [
                'type' => 'paragraph',
                'props' => [
                    'content' => 'Bullet and numbered lists are among the <strong class="text-foreground">most extractable formats</strong> for AI. Each item is a discrete idea that AI can safely lift.',
                ],
            ],
            [
                'type' => 'list',
                'props' => [
                    'type' => 'bullet',
                    'items' => [
                        'Steps and processes',
                        'Features and benefits',
                        'Comparisons',
                        'Key points and takeaways',
                    ],
                ],
            ],
            [
                'type' => 'warning-box',
                'props' => [
                    'title' => 'GEO Pillars Supported',
                    'icon' => 'lightbulb',
                    'content' => 'Lists support <strong class="text-foreground">Structured Information</strong>, <strong class="text-foreground">Machine Readability</strong>, and <strong class="text-foreground">Answer Formatting</strong> pillars.',
                ],
            ],

            // Tables
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'How to Use Tables for Structured Knowledge', 'id' => 'tables'],
            ],
            [
                'type' => 'paragraph',
                'props' => [
                    'content' => 'Tables provide explicit relationships between concepts. AI systems love tables because they <strong class="text-foreground">encode meaning clearly</strong>.',
                ],
            ],
            [
                'type' => 'table',
                'props' => [
                    'headers' => ['Format', 'Why AI Uses It'],
                    'highlightColumn' => -1,
                    'rows' => [
                        ['Headings', 'Defines topic hierarchy'],
                        ['Lists', 'Extractable points'],
                        ['Tables', 'Structured relationships'],
                        ['Definitions', 'Quotable statements'],
                    ],
                ],
            ],
            [
                'type' => 'paragraph',
                'props' => [
                    'content' => 'Use tables for comparisons, feature breakdowns, definitions, pros vs cons, and GEO vs SEO differences.',
                ],
            ],

            // Definitions
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'How to Write Explicit Definitions AI Can Quote', 'id' => 'definitions'],
            ],
            [
                'type' => 'paragraph',
                'props' => [
                    'content' => 'AI needs clear definitions to cite confidently. Definitions are one of the <strong class="text-foreground">most commonly extracted snippet types</strong>.',
                ],
            ],
            [
                'type' => 'step-list',
                'props' => [
                    'steps' => [
                        ['title' => 'Appears near the top', 'description' => 'Within the first few paragraphs'],
                        ['title' => 'Concise (1–2 sentences)', 'description' => 'No fluff or filler'],
                        ['title' => 'Avoids marketing language', 'description' => 'Factual, not promotional'],
                        ['title' => 'States exactly what something is', 'description' => '"X is..." format'],
                    ],
                ],
            ],
            [
                'type' => 'table',
                'props' => [
                    'headers' => ['Example', 'Quality'],
                    'highlightColumn' => -1,
                    'rows' => [
                        ['GEO is a revolutionary approach to modern visibility.', '❌ Bad — vague marketing'],
                        ['Generative Engine Optimization (GEO) is the practice of structuring content so AI search engines can accurately extract, understand, and cite it.', '✅ Good — clear, quotable'],
                    ],
                ],
            ],

            // Semantic HTML
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'Why Semantic HTML Matters for AI Crawlers', 'id' => 'semantic-html'],
            ],
            [
                'type' => 'paragraph',
                'props' => [
                    'content' => 'AI crawlers rely on semantic structure to understand content meaning.',
                ],
            ],
            [
                'type' => 'list',
                'props' => [
                    'type' => 'check',
                    'items' => [
                        'Use <code>&lt;article&gt;</code> and <code>&lt;section&gt;</code> elements',
                        'Use definition lists (<code>&lt;dl&gt;&lt;dt&gt;&lt;dd&gt;</code>)',
                        'Avoid div-only layouts for core content',
                    ],
                ],
            ],
            [
                'type' => 'paragraph',
                'props' => [
                    'content' => 'Semantic HTML reinforces meaning and supports <strong class="text-foreground">AI crawler comprehension</strong> and <strong class="text-foreground">reduced misinterpretation</strong>.',
                ],
            ],

            // Schema Markup
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'How to Add Schema Markup Strategically', 'id' => 'schema-markup'],
            ],
            [
                'type' => 'paragraph',
                'props' => [
                    'content' => 'Schema doesn\'t guarantee citation — but it reduces ambiguity. Think of schema as metadata that tells AI: <strong class="text-foreground">"Here\'s what this content actually represents."</strong>',
                ],
            ],
            [
                'type' => 'list',
                'props' => [
                    'type' => 'bullet',
                    'items' => [
                        '<strong class="text-foreground">Article</strong> — for blog posts and guides',
                        '<strong class="text-foreground">FAQPage</strong> — for question-answer content',
                        '<strong class="text-foreground">HowTo</strong> — for step-by-step instructions',
                        '<strong class="text-foreground">BreadcrumbList</strong> — for navigation context',
                    ],
                ],
            ],

            // FAQ Sections
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'Why FAQ Sections Are Extremely GEO-Friendly', 'id' => 'faq-sections'],
            ],
            [
                'type' => 'paragraph',
                'props' => [
                    'content' => 'FAQ sections directly improve <strong class="text-foreground">question coverage</strong>, <strong class="text-foreground">prompt matching</strong>, and <strong class="text-foreground">citation likelihood</strong>.',
                ],
            ],
            [
                'type' => 'list',
                'props' => [
                    'type' => 'check',
                    'items' => [
                        'Mirror AI question structures',
                        'Provide clean answer blocks',
                        'Work well with FAQPage schema',
                        'Cover long-tail prompts',
                    ],
                ],
            ],

            // Internal Linking
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'How Internal Linking Builds Topic Authority', 'id' => 'internal-linking'],
            ],
            [
                'type' => 'paragraph',
                'props' => [
                    'content' => 'AI doesn\'t evaluate pages in isolation. It evaluates <strong class="text-foreground">topical depth</strong>, <strong class="text-foreground">semantic connections</strong>, and <strong class="text-foreground">internal consistency</strong>.',
                ],
            ],
            [
                'type' => 'paragraph',
                'props' => [
                    'content' => 'Link related articles together using descriptive anchor text. A strong internal content cluster makes your site appear <strong class="text-foreground">knowledge-dense</strong> — a major GEO advantage.',
                ],
            ],

            // External Sources
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'Why Credible External Sources Boost Citations', 'id' => 'external-sources'],
            ],
            [
                'type' => 'paragraph',
                'props' => [
                    'content' => 'AI engines prioritize content that references research, documentation, and authoritative publications.',
                ],
            ],
            [
                'type' => 'paragraph',
                'props' => [
                    'content' => 'Add a clear <strong class="text-foreground">Sources</strong> or <strong class="text-foreground">References</strong> section when appropriate. Even when AI doesn\'t quote the source directly, it boosts your page\'s reliability score.',
                ],
            ],

            // Design for Extraction
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'The Mindset Shift: Design for Extraction, Not Ranking', 'id' => 'extraction-mindset'],
            ],
            [
                'type' => 'highlight-box',
                'props' => [
                    'content' => '<span class="text-xl font-medium">You are not writing to rank.<br>You are writing to be used.</span>',
                    'centered' => true,
                ],
            ],
            [
                'type' => 'paragraph',
                'props' => [
                    'content' => 'If AI can <strong class="text-foreground">find the answer instantly</strong>, <strong class="text-foreground">understand it clearly</strong>, and <strong class="text-foreground">trust it confidently</strong> — you become citable. That\'s how visibility works in AI search.',
                ],
            ],

            // Checklist
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'GEO-Optimized Content Design Checklist', 'id' => 'checklist'],
            ],
            [
                'type' => 'paragraph',
                'props' => [
                    'content' => 'Before publishing, verify your content meets these criteria:',
                ],
            ],
            [
                'type' => 'checklist',
                'props' => [
                    'items' => [
                        ['text' => 'Main question is clearly answered in the opening', 'checked' => false],
                        ['text' => 'Definitions are explicit ("X is..." format)', 'checked' => false],
                        ['text' => 'Headings are descriptive and question-format where appropriate', 'checked' => false],
                        ['text' => 'Lists and tables used for structured information', 'checked' => false],
                        ['text' => 'Paragraphs are short (50-100 words) and declarative', 'checked' => false],
                        ['text' => 'Each section can stand alone as a quotable block', 'checked' => false],
                        ['text' => 'AI could safely quote this text without misrepresenting it', 'checked' => false],
                    ],
                ],
            ],

            // Summary
            [
                'type' => 'heading',
                'props' => ['level' => 2, 'content' => 'Summary', 'id' => 'summary'],
            ],
            [
                'type' => 'paragraph',
                'props' => [
                    'content' => 'AI search rewards <strong class="text-foreground">clarity</strong>, <strong class="text-foreground">structure</strong>, and <strong class="text-foreground">intent</strong> — not clever writing. Designing content for snippet extraction isn\'t about gaming algorithms. It\'s about making your knowledge usable by machines.',
                ],
            ],
            [
                'type' => 'highlight-box',
                'props' => [
                    'content' => '<span class="text-xl font-medium">When your content is easy to extract, it becomes easy to cite.<br>In the age of AI search, citations are the new rankings.</span>',
                    'centered' => true,
                ],
            ],
        ];
    }
}
