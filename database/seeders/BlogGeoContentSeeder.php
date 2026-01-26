<?php

namespace Database\Seeders;

use App\Models\BlogPost;
use Illuminate\Database\Seeder;

class BlogGeoContentSeeder extends Seeder
{
    /**
     * Add FAQ and Quick Links to all blog posts for GEO optimization.
     */
    public function run(): void
    {
        $geoContent = [
            'what-is-geo-complete-guide' => [
                'quick_links' => [
                    ['title' => 'What Is GEO?', 'anchor' => 'what-is-generative-engine-optimization'],
                    ['title' => 'Why GEO Matters Now', 'anchor' => 'why-geo-matters-now'],
                    ['title' => 'Core Principles of GEO', 'anchor' => 'the-core-principles-of-geo'],
                    ['title' => 'Getting Started', 'anchor' => 'getting-started-with-geo'],
                ],
                'faq' => [
                    [
                        'question' => 'What is Generative Engine Optimization (GEO)?',
                        'answer' => 'Generative Engine Optimization (GEO) is the practice of optimizing content to be discovered, understood, and cited by AI systems like ChatGPT, Claude, Perplexity, and Google AI Overviews. Unlike traditional SEO which focuses on search engine rankings, GEO focuses on making content readable and citable by large language models.',
                    ],
                    [
                        'question' => 'How is GEO different from SEO?',
                        'answer' => 'While SEO optimizes for search engine crawlers and ranking algorithms, GEO optimizes for AI language models that generate answers. GEO emphasizes clear definitions, structured content, authoritative sources, and machine-readable formatting that AI systems can easily parse and cite.',
                    ],
                    [
                        'question' => 'Do I need to choose between GEO and SEO?',
                        'answer' => 'No, GEO and SEO are complementary strategies. Good GEO practices like clear structure, authoritative content, and proper formatting also benefit traditional SEO. The best approach is to optimize for both humans, search engines, and AI systems.',
                    ],
                    [
                        'question' => 'How do I measure my GEO performance?',
                        'answer' => 'You can measure GEO performance using tools like GeoSource.ai which analyze your content across multiple pillars including clarity, structure, authority, and machine readability. Track whether AI systems cite your content in their responses.',
                    ],
                ],
            ],
            'geo-vs-seo-key-differences' => [
                'quick_links' => [
                    ['title' => 'What Is SEO?', 'anchor' => 'what-is-seo'],
                    ['title' => 'What Is GEO?', 'anchor' => 'what-is-geo'],
                    ['title' => 'Key Differences', 'anchor' => 'key-differences'],
                    ['title' => 'Why You Need Both', 'anchor' => 'why-you-need-both'],
                    ['title' => 'Action Steps', 'anchor' => 'action-steps'],
                ],
                'faq' => [
                    [
                        'question' => 'Should I focus on GEO or SEO first?',
                        'answer' => 'Start with SEO fundamentals as they provide the foundation for GEO. Once you have quality content that ranks well, enhance it with GEO techniques like clear definitions, structured data, and AI-friendly formatting.',
                    ],
                    [
                        'question' => 'Will AI search replace traditional search engines?',
                        'answer' => 'AI search is growing rapidly but will likely coexist with traditional search. Different users prefer different experiences. Optimizing for both ensures maximum visibility across all search modalities.',
                    ],
                    [
                        'question' => 'What content works best for GEO?',
                        'answer' => 'Informational and educational content performs best for GEO. Content that answers specific questions, provides clear definitions, and includes authoritative citations is most likely to be referenced by AI systems.',
                    ],
                ],
            ],
            'how-ai-search-engines-cite-sources' => [
                'quick_links' => [
                    ['title' => 'How AI Decides What to Cite', 'anchor' => 'how-ai-decides-what-to-cite'],
                    ['title' => 'The Citation Process', 'anchor' => 'the-citation-process'],
                    ['title' => 'High-Confidence Sources', 'anchor' => 'what-makes-a-source-high-confidence'],
                    ['title' => 'Improving Citation Chances', 'anchor' => 'improving-your-citation-chances'],
                ],
                'faq' => [
                    [
                        'question' => 'How do AI systems decide which sources to cite?',
                        'answer' => 'AI systems evaluate sources based on authority, relevance, clarity, and recency. Content from authoritative domains with clear, well-structured information that directly answers queries is more likely to be cited.',
                    ],
                    [
                        'question' => 'Can I track when AI cites my content?',
                        'answer' => 'Tracking AI citations is challenging as most AI systems do not provide analytics. Monitor your brand mentions, use GEO analysis tools, and test queries related to your content in various AI systems to assess visibility.',
                    ],
                    [
                        'question' => 'Does domain authority affect AI citations?',
                        'answer' => 'Yes, domain authority influences AI citations. AI systems tend to prefer content from established, authoritative sources. However, topical authority and content quality can help newer sites get cited for specific subjects.',
                    ],
                ],
            ],
            'rise-of-ai-search-content-creators' => [
                'quick_links' => [
                    ['title' => 'The AI Search Revolution', 'anchor' => 'the-ai-search-revolution-is-here'],
                    ['title' => 'The Numbers', 'anchor' => 'the-numbers-tell-the-story'],
                    ['title' => 'What\'s Different About AI Search', 'anchor' => 'whats-different-about-ai-search'],
                    ['title' => 'Adapting Your Strategy', 'anchor' => 'adapting-your-content-strategy'],
                    ['title' => 'Getting Started', 'anchor' => 'getting-started'],
                ],
                'faq' => [
                    [
                        'question' => 'Will AI search reduce traffic to my website?',
                        'answer' => 'AI search may reduce some click-through traffic for simple queries, but it can increase visibility and brand awareness. Quality content that gets cited by AI systems builds authority and can drive interested users who want deeper information.',
                    ],
                    [
                        'question' => 'How should content creators prepare for AI search?',
                        'answer' => 'Focus on creating comprehensive, authoritative content that provides unique value. Structure content clearly, include expert insights, and ensure your content answers questions thoroughly rather than superficially.',
                    ],
                    [
                        'question' => 'Is AI search better than traditional search?',
                        'answer' => 'AI search excels at synthesizing information and answering complex questions, while traditional search is better for navigational queries and discovering multiple sources. Both serve different user needs.',
                    ],
                ],
            ],
            '10-ways-optimize-content-chatgpt-perplexity' => [
                'quick_links' => [
                    ['title' => 'Clear Definitions', 'anchor' => '1-lead-with-clear-definitions'],
                    ['title' => 'Question-Based Headings', 'anchor' => '2-use-question-based-headings'],
                    ['title' => 'Structured Data', 'anchor' => '3-include-structured-data'],
                    ['title' => 'FAQ Sections', 'anchor' => '4-create-comprehensive-faq-sections'],
                    ['title' => 'Cite Sources', 'anchor' => '5-cite-authoritative-sources'],
                    ['title' => 'Measuring Progress', 'anchor' => 'measuring-your-progress'],
                ],
                'faq' => [
                    [
                        'question' => 'What is the most important GEO optimization?',
                        'answer' => 'Clear definitions and structured content are the most impactful optimizations. AI systems need to understand what your content is about and extract key information easily. Start with these fundamentals before advanced techniques.',
                    ],
                    [
                        'question' => 'How long does it take to see GEO results?',
                        'answer' => 'GEO results can vary. AI systems update their knowledge at different intervals. Some changes may be reflected quickly, while others take weeks or months. Focus on sustainable improvements rather than quick fixes.',
                    ],
                    [
                        'question' => 'Do I need technical skills for GEO?',
                        'answer' => 'Basic GEO can be done without technical skills by focusing on content quality and structure. Advanced optimizations like schema markup and llms.txt require some technical knowledge or developer assistance.',
                    ],
                ],
            ],
            'why-server-side-rendering-ssr-matters-for-geo-and-ai-visibility' => [
                'quick_links' => [
                    ['title' => 'What Is SSR?', 'anchor' => 'what-is-server-side-rendering-ssr'],
                    ['title' => 'How AI Crawlers Read Websites', 'anchor' => 'how-ai-and-llm-crawlers-read-websites'],
                    ['title' => 'Why SSR Matters for GEO', 'anchor' => 'why-ssr-matters-for-geo'],
                    ['title' => 'SSR vs CSR Comparison', 'anchor' => 'ssr-vs-client-side-rendering-csr'],
                    ['title' => 'Popular SSR Frameworks', 'anchor' => 'popular-frameworks-that-support-ssr'],
                ],
                'faq' => [
                    [
                        'question' => 'What is server-side rendering (SSR)?',
                        'answer' => 'Server-side rendering (SSR) is when a web server generates the complete HTML for a page before sending it to the browser. This ensures all content is immediately visible without requiring JavaScript to execute, making it accessible to AI crawlers.',
                    ],
                    [
                        'question' => 'Why can\'t AI crawlers read JavaScript-rendered content?',
                        'answer' => 'Most AI crawlers, including GPTBot and similar systems, do not execute JavaScript when indexing content. They rely on the initial HTML response, so content rendered by JavaScript may be invisible to them.',
                    ],
                    [
                        'question' => 'Do I need to rebuild my site for SSR?',
                        'answer' => 'Not necessarily. Many frameworks offer hybrid approaches. You can also use pre-rendering for critical pages or ensure important content is in the initial HTML. Evaluate your current setup and prioritize pages most important for AI visibility.',
                    ],
                    [
                        'question' => 'Which SSR framework should I use?',
                        'answer' => 'Popular choices include Next.js for React, Nuxt for Vue, SvelteKit for Svelte, and Astro for multi-framework support. Choose based on your team\'s expertise and project requirements.',
                    ],
                ],
            ],
        ];

        foreach ($geoContent as $slug => $content) {
            $post = BlogPost::where('slug', $slug)->first();

            if ($post) {
                $post->update([
                    'faq' => $content['faq'],
                    'quick_links' => $content['quick_links'],
                ]);

                $this->command->info("Updated: {$post->title}");
            } else {
                $this->command->warn("Not found: {$slug}");
            }
        }

        $this->command->info('GEO content added to all blog posts.');
    }
}
