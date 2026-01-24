<?php

namespace Database\Seeders;

use App\Models\BlogPost;
use Illuminate\Database\Seeder;

class BlogPostSeeder extends Seeder
{
    public function run(): void
    {
        $posts = [
            [
                'title' => 'What Is GEO? A Complete Guide to Generative Engine Optimization',
                'slug' => 'what-is-geo-complete-guide',
                'excerpt' => 'Generative Engine Optimization (GEO) is the practice of optimizing content for AI search engines like ChatGPT, Perplexity, and Claude. Learn how GEO differs from traditional SEO and why it matters.',
                'content' => $this->getGeoGuideContent(),
                'tags' => ['GEO', 'AI Search', 'Content Strategy'],
                'status' => 'published',
                'published_at' => now()->subDays(7),
            ],
            [
                'title' => '10 Ways to Optimize Your Content for ChatGPT and Perplexity',
                'slug' => '10-ways-optimize-content-chatgpt-perplexity',
                'excerpt' => 'Practical strategies to make your content more visible and citable by AI search engines. From structured data to clear definitions, these techniques will boost your AI visibility.',
                'content' => $this->getOptimizationTipsContent(),
                'tags' => ['ChatGPT', 'Perplexity', 'Tips'],
                'status' => 'published',
                'published_at' => now()->subDays(5),
            ],
            [
                'title' => 'GEO vs SEO: Understanding the Key Differences',
                'slug' => 'geo-vs-seo-key-differences',
                'excerpt' => 'While SEO focuses on ranking in search results, GEO focuses on being cited by AI. Learn how these disciplines complement each other and why you need both.',
                'content' => $this->getGeoVsSeoContent(),
                'tags' => ['GEO', 'SEO', 'Comparison'],
                'status' => 'published',
                'published_at' => now()->subDays(3),
            ],
            [
                'title' => 'How AI Search Engines Choose Which Sources to Cite',
                'slug' => 'how-ai-search-engines-cite-sources',
                'excerpt' => 'Ever wondered why some websites get cited by ChatGPT while others don\'t? This deep dive explains the signals AI systems use to determine source credibility.',
                'content' => $this->getCitationContent(),
                'tags' => ['AI Citations', 'Trust Signals', 'Technical'],
                'status' => 'published',
                'published_at' => now()->subDays(1),
            ],
            [
                'title' => 'The Rise of AI Search: What It Means for Content Creators',
                'slug' => 'rise-of-ai-search-content-creators',
                'excerpt' => 'AI search is fundamentally changing how people find information. For content creators, this shift presents both challenges and opportunities.',
                'content' => $this->getRiseOfAiContent(),
                'tags' => ['AI Search', 'Content Strategy', 'Future'],
                'status' => 'published',
                'published_at' => now(),
            ],
        ];

        foreach ($posts as $post) {
            BlogPost::create($post);
        }
    }

    private function getGeoGuideContent(): string
    {
        return <<<'MARKDOWN'
## What Is Generative Engine Optimization?

**Generative Engine Optimization (GEO)** is the practice of optimizing digital content so it can be accurately understood, trusted, and cited by generative AI systems. Unlike traditional SEO which focuses on ranking in search engine results pages (SERPs), GEO focuses on making content "AI-ready" — structured and clear enough that AI systems can confidently use it as a source.

## Why GEO Matters Now

The way people search for information is changing rapidly. Instead of scanning through 10 blue links, users are increasingly asking AI assistants like ChatGPT, Perplexity, Claude, and Google's AI Overviews for direct answers.

This shift has profound implications:

- **Direct answers replace link lists**: AI synthesizes information from multiple sources into a single answer
- **Citations become currency**: Being cited by AI is the new "ranking #1"
- **Traditional SEO isn't enough**: High-ranking pages often aren't cited by AI

## The Core Principles of GEO

### 1. Clarity Over Keywords

AI systems prioritize content they can clearly understand. This means:

- Write clear, unambiguous definitions
- Use structured headings and lists
- Avoid jargon without explanation

### 2. Authority Signals

AI looks for signals that indicate expertise:

- Author credentials and bylines
- Citations to authoritative sources
- Consistent topical focus

### 3. Technical Accessibility

Your content must be technically accessible to AI crawlers:

- Proper HTML structure
- Schema.org markup
- Fast loading times
- No JavaScript-dependent content

## Getting Started with GEO

The first step is understanding how your current content performs with AI systems. A GEO Score provides a baseline measurement across key pillars:

1. **Content Quality**: Readability, structure, and clarity
2. **Technical Accessibility**: How easily AI can access your content
3. **Trust Signals**: Author attribution, citations, expertise indicators
4. **Topic Authority**: Depth and consistency of coverage

## Conclusion

GEO isn't replacing SEO — it's complementing it. As AI search continues to grow, optimizing for both traditional and AI search engines will be essential for maintaining visibility online.
MARKDOWN;
    }

    private function getOptimizationTipsContent(): string
    {
        return <<<'MARKDOWN'
## Practical Tips for AI Search Optimization

Getting your content cited by AI search engines requires a combination of content quality, technical optimization, and strategic structuring. Here are 10 actionable tips you can implement today.

## 1. Lead with Clear Definitions

When covering a topic, start with a clear, concise definition. AI systems often extract these for direct answers.

**Example:**
> "Content velocity refers to the speed at which an organization produces and publishes content across channels."

## 2. Use Question-Based Headings

Structure your content around the questions your audience actually asks. This aligns with how people query AI assistants.

**Instead of:** "Our Services"
**Use:** "What Services Does [Company] Offer?"

## 3. Include Structured Data

Implement Schema.org markup to help AI understand your content:

- Article schema for blog posts
- FAQ schema for question-answer content
- HowTo schema for tutorials
- Organization schema for company info

## 4. Create Comprehensive FAQ Sections

FAQ sections are goldmines for AI citation. Each question-answer pair is a potential source for AI responses.

## 5. Cite Authoritative Sources

Reference reputable sources to build credibility. AI systems trust content that demonstrates awareness of the broader conversation.

## 6. Maintain Topical Consistency

Focus your site on specific topics rather than covering everything. Topical authority matters for AI citation.

## 7. Update Content Regularly

Fresh content signals relevance. Update your key pages with new information, statistics, and insights.

## 8. Use Clear, Simple Language

Write at an appropriate reading level. Overly complex language can reduce AI comprehension and citation likelihood.

## 9. Include Author Information

Add author bios with credentials. AI systems factor in E-E-A-T (Experience, Expertise, Authoritativeness, Trustworthiness).

## 10. Optimize Technical Accessibility

Ensure your content is accessible without JavaScript rendering. Use server-side rendering when possible.

## Measuring Your Progress

Track your GEO optimization by:

- Monitoring AI citations of your content
- Running regular GEO Score assessments
- Analyzing which content gets cited vs. ignored

## Start Today

You don't need to implement everything at once. Start with the tips that require the least effort and build from there. Even small improvements in content clarity and structure can improve your AI visibility.
MARKDOWN;
    }

    private function getGeoVsSeoContent(): string
    {
        return <<<'MARKDOWN'
## GEO vs SEO: Two Sides of the Same Coin

Search engine optimization (SEO) and generative engine optimization (GEO) are often discussed as separate disciplines. In reality, they're complementary approaches to the same goal: getting your content found by people looking for answers.

## What Is SEO?

**Search Engine Optimization (SEO)** is the practice of optimizing content to rank higher in search engine results pages (SERPs). Key factors include:

- Keyword optimization
- Backlink building
- Page speed and Core Web Vitals
- Mobile responsiveness
- User experience signals

## What Is GEO?

**Generative Engine Optimization (GEO)** focuses on making content citable by AI systems. Key factors include:

- Content clarity and structure
- Authoritative sourcing
- Technical accessibility for AI crawlers
- Topical expertise signals
- Factual accuracy

## Key Differences

| Aspect | SEO | GEO |
|--------|-----|-----|
| Goal | Rank in SERPs | Get cited by AI |
| Primary signal | Backlinks | Content clarity |
| Output | List of links | Synthesized answer |
| User behavior | Click to visit | Get answer directly |
| Success metric | Click-through rate | Citation frequency |

## Where They Overlap

Despite their differences, SEO and GEO share common ground:

### Technical Foundation
Both require fast, accessible websites with proper HTML structure.

### Quality Content
Both reward well-written, authoritative content that serves user needs.

### E-E-A-T Signals
Both consider expertise, experience, authoritativeness, and trustworthiness.

## Why You Need Both

Consider this scenario:

1. A user searches "what is content velocity" on Google
2. They see an AI Overview citing your article
3. They click through to read more
4. They become a customer

In this flow, GEO got you the citation, but SEO ensured you appeared in the results. Neither alone would have captured this user.

## The Future of Search

As AI becomes more integrated into search experiences, the line between SEO and GEO will blur. Google's AI Overviews, Bing's Copilot, and standalone AI assistants are all converging on similar experiences.

The winners will be those who optimize for both human readers and AI systems — creating content that's discoverable, citable, and genuinely valuable.

## Action Steps

1. Audit your current SEO performance
2. Assess your GEO readiness with a GEO Score
3. Identify gaps in either area
4. Prioritize fixes that improve both simultaneously
5. Monitor performance across both dimensions
MARKDOWN;
    }

    private function getCitationContent(): string
    {
        return <<<'MARKDOWN'
## How AI Decides What to Cite

When you ask ChatGPT, Perplexity, or Claude a question, they don't just make up an answer. They reference information from their training data and, increasingly, from real-time web searches. But how do they decide which sources to cite?

## The Citation Process

### Step 1: Query Understanding

First, the AI parses your question to understand:
- The core topic
- The type of information needed (facts, opinions, how-tos)
- Any specific constraints or preferences

### Step 2: Source Retrieval

For AI systems with web access, this involves:
- Searching indexed content
- Retrieving relevant passages
- Ranking sources by relevance

### Step 3: Confidence Assessment

AI systems evaluate each potential source for:
- **Factual alignment**: Does the information match other sources?
- **Authority signals**: Is the source credible?
- **Clarity**: Is the information clearly stated?
- **Recency**: Is the information up-to-date?

### Step 4: Citation Decision

High-confidence sources get cited. Low-confidence sources may be used for context but not attributed.

## What Makes a Source "High Confidence"?

### Clear, Unambiguous Statements

AI prefers content that states facts clearly rather than hedging or using vague language.

**Lower confidence:** "Some experts believe that content velocity may impact SEO."
**Higher confidence:** "Content velocity — the frequency of content publication — directly impacts search visibility by keeping sites fresh and topically relevant."

### Authoritative Indicators

- Recognized domain authority
- Expert author attribution
- Citations to primary sources
- Consistent topical focus

### Technical Quality

- Proper HTML structure
- Schema.org markup
- Fast, accessible pages
- Clean, parseable content

### Corroboration

Information that appears consistently across multiple quality sources is more likely to be cited with confidence.

## Why Some High-Ranking Pages Don't Get Cited

Here's a surprising finding: many pages that rank #1 in Google never get cited by AI systems. Why?

1. **Content is too promotional**: AI filters out marketing language
2. **Information is buried**: Key facts are hidden in dense paragraphs
3. **Outdated data**: AI prefers recent information
4. **Technical barriers**: JavaScript rendering blocks AI access
5. **Lack of specificity**: Broad overview content doesn't answer specific questions

## Improving Your Citation Chances

### Structure for Extraction

Use formats that make information easy to extract:
- Definition boxes
- Bulleted lists
- Clear headings
- Table summaries

### Build Topical Authority

Become the go-to source for your topic by:
- Publishing comprehensive coverage
- Updating content regularly
- Demonstrating deep expertise

### Optimize for AI Access

Ensure your content is technically accessible:
- Use server-side rendering
- Implement proper schema markup
- Avoid content behind login walls

## The Bottom Line

AI citation isn't random — it's based on signals that indicate trustworthiness, clarity, and authority. By understanding these signals and optimizing for them, you can increase your chances of being cited when users ask questions in your domain.
MARKDOWN;
    }

    private function getRiseOfAiContent(): string
    {
        return <<<'MARKDOWN'
## The AI Search Revolution Is Here

The way people find information is undergoing its biggest transformation since Google replaced directories with search. AI-powered search isn't coming — it's already here, and it's changing everything.

## The Numbers Tell the Story

- ChatGPT has over 100 million weekly active users
- Perplexity processes millions of queries daily
- Google's AI Overviews appear in a growing percentage of searches
- Microsoft's Copilot is integrated across Office and Windows

These aren't early adopters anymore. This is mainstream behavior.

## What's Different About AI Search

### From Links to Answers

Traditional search gives you 10 blue links and hopes you find what you need. AI search gives you a direct answer, synthesized from multiple sources.

### From Keywords to Conversations

Users don't need to think in keywords anymore. They can ask natural questions and expect natural answers.

### From Browsing to Trusting

When AI provides an answer, users often accept it without clicking through to sources. This makes AI citation even more valuable than a #1 ranking.

## What This Means for Content Creators

### The Good News

- Quality content becomes more valuable
- Expertise is rewarded
- Clear, helpful content gets amplified

### The Challenge

- Traffic patterns are changing
- Visibility requires new strategies
- Traditional metrics may not apply

## Adapting Your Content Strategy

### Think "Citation-First"

Ask yourself: "Would an AI confidently cite this as a source?" If not, what's missing?

### Build Authority, Not Just Traffic

Focus on becoming the trusted source for your topics, not just driving page views.

### Create for Both Humans and AI

The best content serves both audiences. Clear, well-structured content that helps humans also helps AI.

### Monitor New Metrics

Start tracking:
- AI citations of your content
- GEO Score over time
- Brand mentions in AI responses

## The Opportunity

This transition is creating new opportunities for those who adapt quickly. The brands and creators who optimize for AI search now will have a significant advantage as AI becomes the primary way people find information.

## Getting Started

1. **Assess your current state**: Get a GEO Score to understand where you stand
2. **Identify quick wins**: Look for content that's almost AI-ready
3. **Build new habits**: Incorporate GEO thinking into your content process
4. **Stay informed**: AI search is evolving rapidly

## The Future Is Now

AI search isn't a future trend to prepare for — it's a present reality to adapt to. The organizations that recognize this and act now will be the ones users (and AI systems) turn to for answers.
MARKDOWN;
    }
}
