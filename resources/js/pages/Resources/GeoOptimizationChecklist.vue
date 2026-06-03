<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import {
    BookOpen,
    ArrowRight,
    ArrowLeft,
    CheckSquare,
    Quote,
    MessageSquare,
    Bot,
    Clock,
    Type,
    Calendar,
    AlertTriangle,
    XCircle,
    Circle,
} from 'lucide-vue-next';
import SkipNav from '@/components/resources/SkipNav.vue';
import ResourceHeader from '@/components/resources/ResourceHeader.vue';
import ResourceFooter from '@/components/resources/ResourceFooter.vue';
import ResourceBreadcrumb from '@/components/resources/ResourceBreadcrumb.vue';

const publishedDate = '2026-01-18';
const modifiedDate = '2026-06-03';
const formattedPublishedDate = new Date(publishedDate).toLocaleDateString('en-US', { month: 'long', year: 'numeric' });

const breadcrumbItems = [
    { label: 'Resources', href: '/resources' },
    { label: 'GEO Optimization Checklist' },
];

// Pillars in priority order based on what our citation research actually validated.
const pillars = [
    {
        id: 'answerability',
        title: 'Answerability',
        icon: MessageSquare,
        summary: 'Answer the question directly, near the top, in clear declarative sentences. This was the strongest predictor of AI citation in our research.',
        items: [
            { task: 'Put the direct answer in the first sentence of each major section', priority: 'High' },
            { task: 'Lead with the answer, not preamble like "In this article we\'ll explore..."', priority: 'High' },
            { task: 'Write in declarative sentences: "X is Y" rather than rhetorical questions', priority: 'High' },
            { task: 'Include self-contained quotable snippets that stand on their own without surrounding context', priority: 'High' },
            { task: 'Cut hedging language ("maybe," "perhaps," "might possibly") when you can state a fact', priority: 'Medium' },
            { task: 'Replace marketing copy with concrete claims that a reader can verify', priority: 'Medium' },
        ],
    },
    {
        id: 'citation-quality',
        title: 'Citation Quality',
        icon: Quote,
        summary: 'Pages that cite authoritative outside sources are more likely to be cited themselves by AI assistants. Treat citations as load-bearing, not decorative.',
        items: [
            { task: 'Link out to primary sources: .gov, .edu, peer-reviewed research, original studies', priority: 'High' },
            { task: 'Use inline attribution ("according to the Pew Research Center...") next to the claim it supports', priority: 'High' },
            { task: 'Cite the original source, not a blog summarizing the original source', priority: 'High' },
            { task: 'Include a References or Sources section on long-form pages', priority: 'Medium' },
            { task: 'Quote statistics with the source and year next to the number', priority: 'Medium' },
            { task: 'Audit existing pages and replace dead or downgraded links', priority: 'Low' },
        ],
    },
    {
        id: 'definitions',
        title: 'Definitions',
        icon: BookOpen,
        summary: 'AI assistants pull definitional sentences disproportionately often. Give them clean, explicit "X is Y" statements to lift.',
        link: '/definitions',
        items: [
            { task: 'Open each topic page with an explicit definition in the first paragraph', priority: 'High' },
            { task: 'Use definitional patterns crawlers recognize: "X is...", "X refers to...", "X is defined as..."', priority: 'High' },
            { task: 'Include the topic\'s canonical name (not just a pronoun) inside the definition sentence', priority: 'High' },
            { task: 'Define each technical term the first time it appears, inline or via glossary link', priority: 'Medium' },
            { task: 'Avoid circular definitions ("AI search is search powered by AI")', priority: 'Medium' },
            { task: 'Mark definitional sentences with semantic HTML (<dfn>) where it fits naturally', priority: 'Low' },
        ],
    },
    {
        id: 'readability',
        title: 'Readability',
        icon: Type,
        summary: 'Plain, well-structured prose is easier for both readers and LLMs to parse and quote. Tight sentences, clear sectioning, no walls of text.',
        items: [
            { task: 'Keep sentences short — aim for around 15 to 20 words on average', priority: 'High' },
            { task: 'Use one H1, then H2s for major sections, then H3s under those. Don\'t skip levels', priority: 'High' },
            { task: 'Break content into short paragraphs (roughly 50 to 100 words)', priority: 'Medium' },
            { task: 'Use bullet lists and numbered lists for anything that\'s actually a list', priority: 'Medium' },
            { task: 'Add tables for comparisons, specs, or structured data', priority: 'Medium' },
            { task: 'Cut compound-complex sentences. Two short sentences beats one long one', priority: 'Low' },
        ],
    },
    {
        id: 'ai-accessibility',
        title: 'AI Accessibility',
        icon: Bot,
        summary: 'If crawlers and LLMs can\'t reach or parse the page, nothing else matters. Treat this as the floor, not the ceiling.',
        items: [
            { task: 'Render content server-side. If the main text only exists after JS executes, most AI crawlers will miss it', priority: 'High' },
            { task: 'Allow major AI crawlers in robots.txt (GPTBot, ClaudeBot, PerplexityBot, OAI-SearchBot)', priority: 'High' },
            { task: 'Remove noindex, nosnippet, and noarchive directives from pages you want cited', priority: 'High' },
            { task: 'Add an llms.txt file at the site root pointing to your most quotable canonical content', priority: 'Medium' },
            { task: 'Use Article and FAQPage schema (JSON-LD) where appropriate', priority: 'Medium' },
            { task: 'Reference your sitemap in robots.txt', priority: 'Medium' },
            { task: 'Audit Cloudflare, WAF, and bot-mitigation rules — aggressive blocking quietly excludes AI crawlers', priority: 'Medium' },
        ],
    },
    {
        id: 'freshness',
        title: 'Content Freshness',
        icon: Clock,
        summary: 'A mild but real positive signal. Visible recency cues help AI assistants prefer your version of an answer over a competitor\'s older one.',
        items: [
            { task: 'Show a visible "Last updated" date on evergreen pages', priority: 'High' },
            { task: 'Keep datePublished and dateModified accurate in your schema', priority: 'High' },
            { task: 'Re-check statistics and claims annually — replace anything stale rather than letting it rot', priority: 'Medium' },
            { task: 'Use current-year references where the fact actually changes year to year', priority: 'Medium' },
            { task: 'Add a brief changelog line on substantive updates ("Updated June 2026: refreshed data")', priority: 'Low' },
        ],
    },
];

const dontBother = [
    'Building "topical authority" by linking related articles into clusters. We didn\'t see this drive citation.',
    'Adding E-E-A-T badges, "expert reviewed by" labels, or author credential boxes for the sake of AI. Visible E-E-A-T signaling didn\'t predict whether AI assistants cited the page.',
    'Padding pages with images, infographics, or video for "AI visibility." Heavy multimedia was associated with worse recommendation survival, not better.',
    'Trying to answer every related question on a single page. Question Coverage was flat in our data — depth on the actual question beats breadth across adjacent ones.',
    'Chasing word-count thresholds. Length without answers doesn\'t buy you anything.',
];

const allItems = pillars.flatMap((p) => p.items);

const jsonLd = {
    '@context': 'https://schema.org',
    '@type': 'HowTo',
    name: 'GEO Optimization Checklist',
    description: 'A research-backed checklist for optimizing your website for citation by AI assistants, organized around the signals our studies validated as drivers.',
    url: 'https://geosource.ai/geo-optimization-checklist',
    step: allItems.map((item, index) => ({
        '@type': 'HowToStep',
        position: index + 1,
        name: item.task,
        itemListElement: {
            '@type': 'HowToDirection',
            text: item.task,
        },
    })),
};

const articleJsonLd = {
    '@context': 'https://schema.org',
    '@type': 'Article',
    headline: 'GEO Optimization Checklist - What Actually Drives AI Citation',
    description: 'A research-backed checklist for optimizing your website for citation by AI assistants. Organized around the signals our studies validated as drivers.',
    url: 'https://geosource.ai/geo-optimization-checklist',
    datePublished: publishedDate,
    dateModified: modifiedDate,
    author: {
        '@type': 'Organization',
        name: 'GeoSource.ai',
        url: 'https://geosource.ai',
    },
    publisher: {
        '@type': 'Organization',
        name: 'GeoSource.ai',
        url: 'https://geosource.ai',
    },
};

const faqJsonLd = {
    '@context': 'https://schema.org',
    '@type': 'FAQPage',
    mainEntity: [
        {
            '@type': 'Question',
            name: 'What actually drives AI citation?',
            acceptedAnswer: {
                '@type': 'Answer',
                text: 'Across our four-study research line, the signals that consistently predicted AI citation were: Answerability (direct, declarative answers near the top of the page), Citation Quality (linking out to authoritative sources), Definitions (explicit "X is Y" statements), Readability (plain, well-structured prose), AI Accessibility (server-rendered content that crawlers can actually reach), and Content Freshness (visible recency cues).',
            },
        },
        {
            '@type': 'Question',
            name: 'Does E-E-A-T improve AI citation?',
            acceptedAnswer: {
                '@type': 'Answer',
                text: 'In our research, visible E-E-A-T signals — author bios, credential badges, "expert reviewed by" labels — did not predict whether AI assistants cited the page. Build trust because it matters to readers, not because it will lift AI citation.',
            },
        },
        {
            '@type': 'Question',
            name: 'Should I add more multimedia to improve AI visibility?',
            acceptedAnswer: {
                '@type': 'Answer',
                text: 'No. In our ecommerce study, heavy multimedia was associated with worse recommendation survival, not better. Add images when they help the reader. Don\'t add them as an AI optimization tactic.',
            },
        },
        {
            '@type': 'Question',
            name: 'Where should I start?',
            acceptedAnswer: {
                '@type': 'Answer',
                text: 'Start with Answerability: put the direct answer in the first sentence of each major section, write in declarative form, and include self-contained quotable snippets. Then layer in Citation Quality (cite authoritative outside sources inline) and Definitions (open each topic page with an explicit "X is Y" statement).',
            },
        },
    ],
};
</script>

<template>
    <Head title="GEO Optimization Checklist - What Actually Drives AI Citation | GeoSource.ai">
        <meta name="description" content="A research-backed checklist for getting cited by AI assistants. Organized around the signals our four-study research line validated as drivers — answerability, citation quality, definitions, readability, AI accessibility, and freshness." />
        <meta property="og:title" content="GEO Optimization Checklist - What Actually Drives AI Citation" />
        <meta property="og:description" content="A research-backed checklist organized around the signals we validated as drivers of AI citation." />
        <meta property="og:type" content="article" />
        <meta property="og:url" content="https://geosource.ai/geo-optimization-checklist" />
        <meta property="og:site_name" content="GeoSource.ai" />
        <meta property="article:published_time" :content="publishedDate" />
        <meta property="article:modified_time" :content="modifiedDate" />
        <meta property="article:author" content="GeoSource.ai" />
        <meta property="article:section" content="Checklist" />
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:site" content="@geosource_ai" />
        <meta name="twitter:title" content="GEO Optimization Checklist - What Actually Drives AI Citation" />
        <meta name="twitter:description" content="A research-backed checklist organized around the signals we validated as drivers of AI citation." />
        <meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1" />
        <link rel="canonical" href="https://geosource.ai/geo-optimization-checklist" />
        <component :is="'script'" type="application/ld+json">{{ JSON.stringify(jsonLd) }}</component>
        <component :is="'script'" type="application/ld+json">{{ JSON.stringify(articleJsonLd) }}</component>
        <component :is="'script'" type="application/ld+json">{{ JSON.stringify(faqJsonLd) }}</component>
    </Head>

    <div class="min-h-screen bg-background text-foreground">
        <!-- Skip Navigation -->
        <SkipNav />

        <!-- Navigation -->
        <ResourceHeader />

        <main id="main-content" role="main">
            <!-- Breadcrumb -->
            <ResourceBreadcrumb :items="breadcrumbItems" />

            <!-- Article -->
            <article class="py-12">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <!-- Header -->
                    <header class="mb-10">
                        <Badge variant="secondary" class="mb-4">
                            <CheckSquare class="mr-1 h-3 w-3" aria-hidden="true" />
                            Practical Guide
                        </Badge>
                        <h1 id="page-title" class="text-3xl font-bold tracking-tight sm:text-4xl lg:text-5xl">
                            GEO Optimization Checklist
                        </h1>
                        <p class="mt-4 text-lg text-muted-foreground">
                            What actually drives AI citation — and what doesn't. Organized around the signals our four-study research line validated as drivers.
                        </p>
                        <div class="mt-4 flex items-center gap-2 text-sm text-muted-foreground">
                            <Calendar class="h-4 w-4" aria-hidden="true" />
                            <time :datetime="publishedDate">{{ formattedPublishedDate }}</time>
                            <span aria-hidden="true">·</span>
                            <span>Updated June 2026</span>
                        </div>
                    </header>

                    <!-- What changed -->
                    <section class="mb-10" aria-labelledby="what-changed-heading">
                        <Card class="border-amber-500/40 bg-amber-500/5">
                            <CardContent class="pt-6">
                                <div class="flex items-start gap-3">
                                    <AlertTriangle class="h-5 w-5 text-amber-600 dark:text-amber-500 shrink-0 mt-1" aria-hidden="true" />
                                    <div>
                                        <h2 id="what-changed-heading" class="text-lg font-semibold mb-2">What changed in this checklist</h2>
                                        <p class="text-muted-foreground">
                                            Previous versions of this guide told you to build topical authority, add E-E-A-T signals like credential badges and expert-reviewed labels, and load pages with multimedia. Our research did not validate any of those as drivers of AI citation. This version is rebuilt around the signals that did predict citation. See the supporting work on the <Link href="/research" class="text-primary hover:underline font-medium">research page</Link> and the cross-study summary on <Link href="/blog/what-predicts-ai-citations" class="text-primary hover:underline font-medium">what predicts AI citations</Link>.
                                        </p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </section>

                    <!-- How to use this -->
                    <section class="mb-10" aria-labelledby="how-to-use-heading">
                        <h2 id="how-to-use-heading" class="sr-only">How to use this checklist</h2>
                        <Card class="border-primary/50 bg-primary/5">
                            <CardContent class="pt-6 space-y-3">
                                <p>
                                    This is a single unified checklist. There's no Free/Pro/Agency split anymore — these signals are the signals, regardless of which plan you're on.
                                </p>
                                <p class="text-muted-foreground">
                                    Pillars are ordered by the strength of evidence in our research. Work top-down. Within each pillar, items are tagged <strong>High</strong>, <strong>Medium</strong>, or <strong>Low</strong> — priority reflects what mattered in the data, not what's quickest to ship. If you only do the High items, you've done the work that counts.
                                </p>
                            </CardContent>
                        </Card>
                    </section>

                    <!-- Priority Legend -->
                    <section class="mb-10" aria-labelledby="priority-legend-heading">
                        <h2 id="priority-legend-heading" class="sr-only">Priority Legend</h2>
                        <div class="flex flex-wrap items-center gap-4 text-sm">
                            <span class="font-medium">Priority:</span>
                            <span class="flex items-center gap-1.5">
                                <Badge variant="destructive" class="text-xs">High</Badge>
                                <span class="text-muted-foreground">Strong evidence this drives citation</span>
                            </span>
                            <span class="flex items-center gap-1.5">
                                <Badge variant="default" class="text-xs">Medium</Badge>
                                <span class="text-muted-foreground">Supports the High items</span>
                            </span>
                            <span class="flex items-center gap-1.5">
                                <Badge variant="secondary" class="text-xs">Low</Badge>
                                <span class="text-muted-foreground">Polish, do last</span>
                            </span>
                        </div>
                    </section>

                    <!-- Pillars -->
                    <section class="mb-12" aria-labelledby="pillars-heading">
                        <h2 id="pillars-heading" class="sr-only">Checklist by pillar</h2>
                        <div class="space-y-6">
                            <Card
                                v-for="(pillar, index) in pillars"
                                :key="pillar.id"
                                :aria-labelledby="`pillar-${pillar.id}`"
                            >
                                <CardHeader>
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="flex items-start gap-3">
                                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary/10">
                                                <component :is="pillar.icon" class="h-5 w-5 text-primary" aria-hidden="true" />
                                            </div>
                                            <div>
                                                <CardTitle :id="`pillar-${pillar.id}`" class="text-xl">
                                                    {{ index + 1 }}. {{ pillar.title }}
                                                </CardTitle>
                                                <CardDescription class="mt-1.5">{{ pillar.summary }}</CardDescription>
                                            </div>
                                        </div>
                                        <Link
                                            v-if="pillar.link"
                                            :href="pillar.link"
                                            class="text-primary hover:underline text-sm hidden sm:block shrink-0"
                                        >
                                            Learn more <span aria-hidden="true">→</span>
                                        </Link>
                                    </div>
                                </CardHeader>
                                <CardContent>
                                    <ul class="space-y-2.5" :aria-label="`${pillar.title} checklist items`">
                                        <li
                                            v-for="item in pillar.items"
                                            :key="item.task"
                                            class="flex items-start gap-3 p-3 rounded-lg border bg-card hover:bg-muted/50 transition-colors"
                                        >
                                            <Circle class="h-5 w-5 text-muted-foreground shrink-0 mt-0.5" aria-hidden="true" />
                                            <span class="flex-1">{{ item.task }}</span>
                                            <Badge
                                                :variant="item.priority === 'High' ? 'destructive' : item.priority === 'Medium' ? 'default' : 'secondary'"
                                                class="shrink-0 text-xs"
                                            >
                                                {{ item.priority }}
                                            </Badge>
                                        </li>
                                    </ul>
                                </CardContent>
                            </Card>
                        </div>
                    </section>

                    <Separator class="my-12" />

                    <!-- Don't bother -->
                    <section class="mb-12" aria-labelledby="dont-bother-heading">
                        <div class="mb-6">
                            <h2 id="dont-bother-heading" class="text-2xl font-bold">Don't bother</h2>
                            <p class="text-muted-foreground mt-2">
                                Tactics you'll be told to do that didn't move the needle in our research. Spend your time on the pillars above instead.
                            </p>
                        </div>
                        <Card class="border-destructive/30 bg-destructive/5">
                            <CardContent class="pt-6">
                                <ul class="space-y-3">
                                    <li
                                        v-for="item in dontBother"
                                        :key="item"
                                        class="flex items-start gap-3"
                                    >
                                        <XCircle class="h-5 w-5 text-destructive shrink-0 mt-0.5" aria-hidden="true" />
                                        <span>{{ item }}</span>
                                    </li>
                                </ul>
                            </CardContent>
                        </Card>
                    </section>

                    <Separator class="my-12" />

                    <!-- Related -->
                    <section class="mb-12" aria-labelledby="related-resources-heading">
                        <Card class="border-primary/50">
                            <CardContent class="pt-6">
                                <h2 id="related-resources-heading" class="text-lg font-bold mb-4">Read the research behind this</h2>
                                <p class="text-muted-foreground mb-4">
                                    This checklist is downstream of our four-study research line. The <Link href="/research" class="text-primary hover:underline font-medium">research page</Link> covers methodology and findings. <Link href="/blog/what-predicts-ai-citations" class="text-primary hover:underline font-medium">What predicts AI citations</Link> summarizes the cross-study takeaways in one place. The <Link href="/definitions" class="text-primary hover:underline font-medium">definitions reference</Link> covers the terminology used throughout.
                                </p>
                                <nav aria-label="Related resources">
                                    <ul class="flex flex-wrap gap-2" role="list">
                                        <li>
                                            <Link href="/research">
                                                <Button variant="outline" size="sm">Research</Button>
                                            </Link>
                                        </li>
                                        <li>
                                            <Link href="/blog/what-predicts-ai-citations">
                                                <Button variant="outline" size="sm">What predicts AI citations</Button>
                                            </Link>
                                        </li>
                                        <li>
                                            <Link href="/resources/what-is-geo">
                                                <Button variant="outline" size="sm">What Is GEO?</Button>
                                            </Link>
                                        </li>
                                        <li>
                                            <Link href="/definitions">
                                                <Button variant="outline" size="sm">GEO Definitions</Button>
                                            </Link>
                                        </li>
                                    </ul>
                                </nav>
                            </CardContent>
                        </Card>
                    </section>

                    <!-- Navigation -->
                    <nav aria-label="Article navigation" class="flex items-center justify-between border-t pt-8">
                        <Link href="/geo-score-explained" class="inline-flex items-center text-muted-foreground hover:text-foreground">
                            <ArrowLeft class="mr-2 h-4 w-4" aria-hidden="true" />
                            Previous: GEO Score Explained
                        </Link>
                        <Link href="/ai-search-visibility-guide" class="inline-flex items-center text-primary hover:underline">
                            Next: AI Visibility Guide
                            <ArrowRight class="ml-2 h-4 w-4" aria-hidden="true" />
                        </Link>
                    </nav>
                </div>
            </article>

            <!-- CTA Section -->
            <section class="border-t bg-muted/30 py-12" aria-labelledby="cta-heading">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 text-center">
                    <h2 id="cta-heading" class="text-2xl font-bold">Ready to measure your progress?</h2>
                    <p class="mt-2 text-muted-foreground">Get your Citation Readiness Score and see which of these pillars need the most work on your site.</p>
                    <div class="mt-6">
                        <Link href="/register">
                            <Button size="lg" class="gap-2">
                                Get your Citation Readiness Score
                                <ArrowRight class="h-4 w-4" aria-hidden="true" />
                            </Button>
                        </Link>
                    </div>
                </div>
            </section>
        </main>

        <!-- Footer -->
        <ResourceFooter />
    </div>
</template>
