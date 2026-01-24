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
    FileText,
    Database,
    Target,
    Code,
    Link as LinkIcon,
    HelpCircle,
    Quote,
    Layers,
    CheckCircle,
    Circle,
    Award,
    MessageSquare,
    UserCheck,
    Bot,
    Clock,
    Type,
    Image,
    Calendar,
} from 'lucide-vue-next';
import SkipNav from '@/components/resources/SkipNav.vue';
import ResourceHeader from '@/components/resources/ResourceHeader.vue';
import ResourceFooter from '@/components/resources/ResourceFooter.vue';
import ResourceBreadcrumb from '@/components/resources/ResourceBreadcrumb.vue';

const publishedDate = '2026-01-18';
const modifiedDate = '2026-01-20';
const formattedPublishedDate = new Date(publishedDate).toLocaleDateString('en-US', { month: 'long', year: 'numeric' });

const breadcrumbItems = [
    { label: 'Resources', href: '/resources' },
    { label: 'GEO Optimization Checklist' },
];

// Free Tier Pillars (100 points)
const freePillars = [
    {
        id: 'clear-definitions',
        title: 'Clear Definitions',
        icon: BookOpen,
        points: 20,
        description: 'Explicit definitions that AI can quote directly',
        link: '/definitions',
        items: [
            { task: 'Start with a clear "X is..." definition in the first paragraph', priority: 'High' },
            { task: 'Include your main topic/entity name in the definition', priority: 'High' },
            { task: 'Use definitional patterns: "X refers to...", "X is defined as..."', priority: 'High' },
            { task: 'Define technical terms inline or link to a glossary', priority: 'Medium' },
            { task: 'Avoid vague or circular definitions', priority: 'Medium' },
        ],
    },
    {
        id: 'structured-knowledge',
        title: 'Structured Knowledge',
        icon: Layers,
        points: 20,
        description: 'Organized content hierarchy for machine comprehension',
        items: [
            { task: 'Use exactly one H1 heading per page', priority: 'High' },
            { task: 'Add multiple H2 subheadings to break up content', priority: 'High' },
            { task: 'Follow proper heading hierarchy (H1 → H2 → H3)', priority: 'High' },
            { task: 'Use bullet points and numbered lists for multi-item content', priority: 'Medium' },
            { task: 'Add tables for comparisons and structured data', priority: 'Medium' },
            { task: 'Avoid skipping heading levels (e.g., H1 → H3)', priority: 'Medium' },
        ],
    },
    {
        id: 'topic-authority',
        title: 'Topic Authority',
        icon: Award,
        points: 25,
        description: 'Depth of coverage and expertise indicators',
        items: [
            { task: 'Write comprehensive content (800-1500+ words for key topics)', priority: 'High' },
            { task: 'Include examples, explanations, and evidence', priority: 'High' },
            { task: 'Add 3+ internal links to related content on your site', priority: 'High' },
            { task: 'Focus each page on a single, specific topic', priority: 'Medium' },
            { task: 'Create topic clusters with pillar pages and supporting content', priority: 'Medium' },
            { task: 'Use descriptive anchor text for links (not "click here")', priority: 'Low' },
        ],
    },
    {
        id: 'machine-readable',
        title: 'Machine-Readable Formatting',
        icon: Code,
        points: 15,
        description: 'Technical markup for AI understanding',
        link: '/resources/why-llms-txt-matters',
        items: [
            { task: 'Add JSON-LD Article schema to content pages', priority: 'High' },
            { task: 'Implement FAQPage schema for FAQ sections', priority: 'High' },
            { task: 'Use semantic HTML elements (<article>, <section>, <dfn>)', priority: 'High' },
            { task: 'Add descriptive alt text to all images', priority: 'High' },
            { task: 'Create an llms.txt file at your site root', priority: 'Medium' },
            { task: 'Add Organization schema on your homepage', priority: 'Medium' },
        ],
    },
    {
        id: 'answerability',
        title: 'High-Confidence Answerability',
        icon: MessageSquare,
        points: 20,
        description: 'Declarative statements AI can confidently cite',
        items: [
            { task: 'Use declarative sentences: "X is Y" instead of questions', priority: 'High' },
            { task: 'Start with the answer, not preamble ("In this article...")', priority: 'High' },
            { task: 'Include quotable snippets (50-150 characters) that answer questions', priority: 'High' },
            { task: 'Reduce hedging words: "maybe", "perhaps", "possibly"', priority: 'Medium' },
            { task: 'State facts confidently with supporting evidence', priority: 'Medium' },
            { task: 'Avoid marketing fluff and promotional language', priority: 'Low' },
        ],
    },
];

// Pro Tier Pillars (+35 points)
const proPillars = [
    {
        id: 'eeat-signals',
        title: 'E-E-A-T Signals',
        icon: UserCheck,
        points: 15,
        description: 'Experience, Expertise, Authoritativeness, Trustworthiness',
        link: '/resources/e-e-a-t-and-geo',
        items: [
            { task: 'Add author attribution with name and link to bio', priority: 'High' },
            { task: 'Include author bio with credentials and expertise', priority: 'High' },
            { task: 'Add reviews, testimonials, or case studies', priority: 'Medium' },
            { task: 'Ensure visible contact information or link to contact page', priority: 'Medium' },
            { task: 'Highlight relevant qualifications and experience', priority: 'Medium' },
        ],
    },
    {
        id: 'citations-sources',
        title: 'Citations & Sources',
        icon: Quote,
        points: 12,
        description: 'Authoritative external references',
        link: '/resources/ai-citations-and-geo',
        items: [
            { task: 'Link to authoritative sources (.gov, .edu, research papers)', priority: 'High' },
            { task: 'Use inline citations: "according to [source]..."', priority: 'High' },
            { task: 'Include relevant statistics with sources', priority: 'Medium' },
            { task: 'Add a References or Sources section for credibility', priority: 'Medium' },
            { task: 'Cite recent, reputable publications', priority: 'Low' },
        ],
    },
    {
        id: 'ai-crawler-access',
        title: 'AI Crawler Access',
        icon: Bot,
        points: 8,
        description: 'Technical accessibility for AI systems',
        link: '/resources/ai-accessibility-for-geo',
        items: [
            { task: 'Allow AI crawlers in robots.txt (GPTBot, ClaudeBot, etc.)', priority: 'High' },
            { task: 'Remove noindex/nosnippet meta directives', priority: 'High' },
            { task: 'Add Sitemap reference to robots.txt', priority: 'Medium' },
            { task: 'Ensure pages load without JavaScript for crawlers', priority: 'Medium' },
        ],
    },
];

// Agency Tier Pillars (+40 points)
const agencyPillars = [
    {
        id: 'content-freshness',
        title: 'Content Freshness',
        icon: Clock,
        points: 10,
        description: 'Recency signals and update indicators',
        link: '/resources/content-freshness-for-geo',
        items: [
            { task: 'Add visible publication date to content', priority: 'High' },
            { task: 'Show "Last updated" date for evergreen content', priority: 'High' },
            { task: 'Add datePublished/dateModified to Schema.org data', priority: 'Medium' },
            { task: 'Include current year references where appropriate', priority: 'Medium' },
            { task: 'Review and update outdated content regularly', priority: 'Low' },
        ],
    },
    {
        id: 'readability',
        title: 'Readability',
        icon: Type,
        points: 10,
        description: 'Clear, accessible writing for AI parsing',
        link: '/resources/readability-and-geo',
        items: [
            { task: 'Write at 8th-9th grade reading level', priority: 'High' },
            { task: 'Keep sentences to 15-20 words on average', priority: 'High' },
            { task: 'Break paragraphs into 50-100 words', priority: 'Medium' },
            { task: 'Reduce complex words (3+ syllables) where possible', priority: 'Medium' },
            { task: 'Avoid very long sentences (35+ words)', priority: 'Low' },
        ],
    },
    {
        id: 'question-coverage',
        title: 'Question Coverage',
        icon: HelpCircle,
        points: 10,
        description: 'Direct answers to user queries',
        link: '/resources/question-coverage-for-geo',
        items: [
            { task: 'Add an FAQ section on key pages', priority: 'High' },
            { task: 'Use question-format headings ("What is X?", "How do I Y?")', priority: 'High' },
            { task: 'Add FAQPage schema markup to FAQ sections', priority: 'High' },
            { task: 'Cover "what is", "how to", and "why" question types', priority: 'Medium' },
            { task: 'Provide direct, complete answers after each question', priority: 'Medium' },
        ],
    },
    {
        id: 'multimedia-content',
        title: 'Multimedia Content',
        icon: Image,
        points: 10,
        description: 'Visual elements that enhance understanding',
        link: '/resources/multimedia-and-geo',
        items: [
            { task: 'Add 2+ relevant images to break up text', priority: 'High' },
            { task: 'Include descriptive alt text on all images', priority: 'High' },
            { task: 'Use <figure> and <figcaption> for image captions', priority: 'Medium' },
            { task: 'Add tables for comparative or structured data', priority: 'Medium' },
            { task: 'Include diagrams, charts, or code blocks where appropriate', priority: 'Low' },
        ],
    },
];

// Combine all pillars for JSON-LD
const allPillars = [...freePillars, ...proPillars, ...agencyPillars];

const jsonLd = {
    '@context': 'https://schema.org',
    '@type': 'HowTo',
    name: 'GEO Optimization Checklist',
    description: 'A step-by-step checklist for optimizing your website for generative AI systems and improving your GEO Score across 12 scoring pillars.',
    url: 'https://geosource.ai/geo-optimization-checklist',
    step: allPillars.flatMap((pillar, pillarIndex) =>
        pillar.items.map((item, itemIndex) => ({
            '@type': 'HowToStep',
            position: pillarIndex * 10 + itemIndex + 1,
            name: item.task,
            itemListElement: {
                '@type': 'HowToDirection',
                text: item.task,
            },
        }))
    ),
};

const articleJsonLd = {
    '@context': 'https://schema.org',
    '@type': 'Article',
    headline: 'GEO Optimization Checklist - Step-by-Step Guide',
    description: 'A comprehensive checklist for optimizing your website for generative AI systems. Improve your GEO Score across 12 pillars with actionable steps.',
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
            name: 'How do I optimize my website for AI search?',
            acceptedAnswer: {
                '@type': 'Answer',
                text: 'To optimize for AI search, focus on the 5 core GEO pillars: 1) Clear Definitions - start with "X is..." patterns, 2) Structured Knowledge - use proper heading hierarchy, 3) Topic Authority - write comprehensive content with internal links, 4) Machine-Readable Formatting - add JSON-LD schema and llms.txt, 5) High-Confidence Answerability - use declarative statements AI can quote.',
            },
        },
        {
            '@type': 'Question',
            name: 'What are the GEO scoring pillars?',
            acceptedAnswer: {
                '@type': 'Answer',
                text: 'GEO scores are based on 12 pillars across 3 tiers. Free tier (100 pts): Clear Definitions, Structured Knowledge, Topic Authority, Machine-Readable Formatting, Answerability. Pro tier (+35 pts): E-E-A-T Signals, Citations & Sources, AI Crawler Access. Agency tier (+40 pts): Content Freshness, Readability, Question Coverage, Multimedia Content.',
            },
        },
        {
            '@type': 'Question',
            name: 'What is the most important GEO optimization?',
            acceptedAnswer: {
                '@type': 'Answer',
                text: 'The highest-weighted pillar is Topic Authority (25 points), followed by Clear Definitions, Structured Knowledge, and Answerability (20 points each). Focus on comprehensive content with clear definitions at the start, proper heading structure, and declarative statements that AI can confidently cite.',
            },
        },
        {
            '@type': 'Question',
            name: 'What is the maximum GEO Score?',
            acceptedAnswer: {
                '@type': 'Answer',
                text: 'The maximum GEO Score depends on your plan tier: Free tier = 100 points (5 pillars), Pro tier = 135 points (8 pillars), Agency tier = 175 points (12 pillars). Your score is shown as a percentage of the maximum possible for your tier.',
            },
        },
    ],
};
</script>

<template>
    <Head title="GEO Optimization Checklist - Step-by-Step Guide | GeoSource.ai">
        <meta name="description" content="A comprehensive checklist for optimizing your website for generative AI systems. Improve your GEO Score with actionable steps for content structure, definitions, FAQ coverage, and technical optimization." />
        <meta property="og:title" content="GEO Optimization Checklist - Step-by-Step Guide" />
        <meta property="og:description" content="Actionable checklist for improving your GEO Score and AI citation readiness." />
        <meta property="og:type" content="article" />
        <meta property="og:url" content="https://geosource.ai/geo-optimization-checklist" />
        <meta property="og:site_name" content="GeoSource.ai" />
        <meta property="article:published_time" :content="publishedDate" />
        <meta property="article:modified_time" :content="modifiedDate" />
        <meta property="article:author" content="GeoSource.ai" />
        <meta property="article:section" content="Checklist" />
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:site" content="@geosourceai" />
        <meta name="twitter:title" content="GEO Optimization Checklist - Step-by-Step Guide" />
        <meta name="twitter:description" content="Actionable checklist for improving your GEO Score and AI citation readiness." />
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
                    <header class="mb-12">
                        <Badge variant="secondary" class="mb-4">
                            <CheckSquare class="mr-1 h-3 w-3" aria-hidden="true" />
                            Practical Guide
                        </Badge>
                        <h1 id="page-title" class="text-3xl font-bold tracking-tight sm:text-4xl lg:text-5xl">
                            GEO Optimization Checklist
                        </h1>
                        <p class="mt-4 text-lg text-muted-foreground">
                            A step-by-step guide to optimizing your website for generative AI systems.
                        </p>
                        <div class="mt-4 flex items-center gap-2 text-sm text-muted-foreground">
                            <Calendar class="h-4 w-4" aria-hidden="true" />
                            <time :datetime="publishedDate">{{ formattedPublishedDate }}</time>
                        </div>
                    </header>

                    <!-- Introduction -->
                    <section class="mb-12" aria-labelledby="intro-heading">
                        <h2 id="intro-heading" class="sr-only">Introduction</h2>
                        <Card class="border-primary/50 bg-primary/5">
                            <CardContent class="pt-6">
                                <p class="text-lg">
                                    This checklist covers all 12 <Link href="/geo-score-explained" class="text-primary hover:underline font-medium">GEO Score</Link> pillars organized by plan tier. Work through each pillar systematically to improve your AI citation readiness. Learn more about <Link href="/resources/what-is-geo" class="text-primary hover:underline font-medium">Generative Engine Optimization (GEO)</Link> and get started with the <Link href="/" class="text-primary hover:underline font-medium">GeoSource.ai platform</Link>.
                                </p>
                            </CardContent>
                        </Card>
                    </section>

                    <!-- Scoring Summary -->
                    <section class="mb-8" aria-labelledby="scoring-summary-heading">
                        <h2 id="scoring-summary-heading" class="sr-only">Scoring Summary</h2>
                        <Card>
                            <CardContent class="pt-6">
                                <div class="grid gap-4 sm:grid-cols-3 text-center">
                                    <div class="p-4 rounded-lg bg-muted/50">
                                        <Badge variant="secondary" class="mb-2">Free</Badge>
                                        <p class="text-2xl font-bold">100 pts</p>
                                        <p class="text-sm text-muted-foreground">5 pillars</p>
                                    </div>
                                    <div class="p-4 rounded-lg bg-blue-500/10">
                                        <Badge class="bg-blue-500 mb-2">Pro</Badge>
                                        <p class="text-2xl font-bold">135 pts</p>
                                        <p class="text-sm text-muted-foreground">8 pillars</p>
                                    </div>
                                    <div class="p-4 rounded-lg bg-purple-500/10">
                                        <Badge class="bg-purple-500 mb-2">Agency</Badge>
                                        <p class="text-2xl font-bold">175 pts</p>
                                        <p class="text-sm text-muted-foreground">12 pillars</p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </section>

                    <!-- Priority Legend -->
                    <section class="mb-8" aria-labelledby="priority-legend-heading">
                        <h2 id="priority-legend-heading" class="sr-only">Priority Legend</h2>
                        <div class="flex flex-wrap items-center gap-4 text-sm">
                            <span class="font-medium">Priority:</span>
                            <span class="flex items-center gap-1">
                                <Badge variant="destructive" class="text-xs">High</Badge>
                                <span class="text-muted-foreground">Essential for AI visibility</span>
                            </span>
                            <span class="flex items-center gap-1">
                                <Badge variant="default" class="text-xs">Medium</Badge>
                                <span class="text-muted-foreground">Recommended</span>
                            </span>
                            <span class="flex items-center gap-1">
                                <Badge variant="secondary" class="text-xs">Low</Badge>
                                <span class="text-muted-foreground">Nice to have</span>
                            </span>
                        </div>
                    </section>

                    <!-- Free Tier Pillars -->
                    <section class="mb-12" aria-labelledby="free-tier-heading">
                        <div class="flex items-center gap-3 mb-6">
                            <h2 id="free-tier-heading" class="text-2xl font-bold">Free Tier Pillars</h2>
                            <Badge variant="secondary">100 points</Badge>
                        </div>
                        <p class="text-muted-foreground mb-6">Core pillars available to all users. These fundamentals are essential for AI visibility.</p>

                        <div class="space-y-6">
                            <Card v-for="(pillar, index) in freePillars" :key="pillar.id" :aria-labelledby="`pillar-${pillar.id}`">
                                <CardHeader>
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-primary/10">
                                                <component :is="pillar.icon" class="h-5 w-5 text-primary" aria-hidden="true" />
                                            </div>
                                            <div>
                                                <CardTitle :id="`pillar-${pillar.id}`" class="text-xl flex items-center gap-2">
                                                    {{ index + 1 }}. {{ pillar.title }}
                                                    <Badge variant="outline">{{ pillar.points }} pts</Badge>
                                                </CardTitle>
                                                <CardDescription>{{ pillar.description }}</CardDescription>
                                            </div>
                                        </div>
                                        <Link v-if="pillar.link" :href="pillar.link" class="text-primary hover:underline text-sm hidden sm:block">
                                            Learn more <span aria-hidden="true">→</span>
                                        </Link>
                                    </div>
                                </CardHeader>
                                <CardContent>
                                    <ul class="space-y-3" :aria-label="`${pillar.title} checklist items`">
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

                    <!-- Pro Tier Pillars -->
                    <section class="mb-12" aria-labelledby="pro-tier-heading">
                        <div class="flex items-center gap-3 mb-6">
                            <h2 id="pro-tier-heading" class="text-2xl font-bold">Pro Tier Pillars</h2>
                            <Badge class="bg-blue-500 hover:bg-blue-600">+35 points</Badge>
                        </div>
                        <p class="text-muted-foreground mb-6">Advanced pillars for Pro subscribers. These add trust signals and technical accessibility.</p>

                        <div class="space-y-6">
                            <Card v-for="(pillar, index) in proPillars" :key="pillar.id" class="border-blue-500/30" :aria-labelledby="`pillar-${pillar.id}`">
                                <CardHeader>
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-500/10">
                                                <component :is="pillar.icon" class="h-5 w-5 text-blue-500" aria-hidden="true" />
                                            </div>
                                            <div>
                                                <CardTitle :id="`pillar-${pillar.id}`" class="text-xl flex items-center gap-2">
                                                    {{ index + 6 }}. {{ pillar.title }}
                                                    <Badge class="bg-blue-500">{{ pillar.points }} pts</Badge>
                                                </CardTitle>
                                                <CardDescription>{{ pillar.description }}</CardDescription>
                                            </div>
                                        </div>
                                        <Link v-if="pillar.link" :href="pillar.link" class="text-blue-500 hover:underline text-sm hidden sm:block">
                                            Learn more <span aria-hidden="true">→</span>
                                        </Link>
                                    </div>
                                </CardHeader>
                                <CardContent>
                                    <ul class="space-y-3" :aria-label="`${pillar.title} checklist items`">
                                        <li
                                            v-for="item in pillar.items"
                                            :key="item.task"
                                            class="flex items-start gap-3 p-3 rounded-lg border border-blue-500/20 bg-card hover:bg-blue-500/5 transition-colors"
                                        >
                                            <Circle class="h-5 w-5 text-blue-500/50 shrink-0 mt-0.5" aria-hidden="true" />
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

                    <!-- Agency Tier Pillars -->
                    <section class="mb-12" aria-labelledby="agency-tier-heading">
                        <div class="flex items-center gap-3 mb-6">
                            <h2 id="agency-tier-heading" class="text-2xl font-bold">Agency Tier Pillars</h2>
                            <Badge class="bg-purple-500 hover:bg-purple-600">+40 points</Badge>
                        </div>
                        <p class="text-muted-foreground mb-6">Enterprise pillars for Agency subscribers. These provide comprehensive optimization insights.</p>

                        <div class="space-y-6">
                            <Card v-for="(pillar, index) in agencyPillars" :key="pillar.id" class="border-purple-500/30" :aria-labelledby="`pillar-${pillar.id}`">
                                <CardHeader>
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-purple-500/10">
                                                <component :is="pillar.icon" class="h-5 w-5 text-purple-500" aria-hidden="true" />
                                            </div>
                                            <div>
                                                <CardTitle :id="`pillar-${pillar.id}`" class="text-xl flex items-center gap-2">
                                                    {{ index + 9 }}. {{ pillar.title }}
                                                    <Badge class="bg-purple-500">{{ pillar.points }} pts</Badge>
                                                </CardTitle>
                                                <CardDescription>{{ pillar.description }}</CardDescription>
                                            </div>
                                        </div>
                                        <Link v-if="pillar.link" :href="pillar.link" class="text-purple-500 hover:underline text-sm hidden sm:block">
                                            Learn more <span aria-hidden="true">→</span>
                                        </Link>
                                    </div>
                                </CardHeader>
                                <CardContent>
                                    <ul class="space-y-3" :aria-label="`${pillar.title} checklist items`">
                                        <li
                                            v-for="item in pillar.items"
                                            :key="item.task"
                                            class="flex items-start gap-3 p-3 rounded-lg border border-purple-500/20 bg-card hover:bg-purple-500/5 transition-colors"
                                        >
                                            <Circle class="h-5 w-5 text-purple-500/50 shrink-0 mt-0.5" aria-hidden="true" />
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

                    <!-- Quick Wins -->
                    <section class="mb-12" aria-labelledby="quick-wins">
                        <h2 id="quick-wins" class="text-2xl font-bold mb-6">Quick Wins: Start Here</h2>
                        <Card class="border-green-500/50 bg-green-500/5">
                            <CardContent class="pt-6">
                                <p class="font-medium mb-4">If you can only do 5 things, focus on the highest-impact items from each Free pillar:</p>
                                <ol class="space-y-3">
                                    <li class="flex items-start gap-3">
                                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-green-500 text-white text-sm font-bold" aria-hidden="true">1</span>
                                        <div>
                                            <span class="font-medium">Clear Definitions (20 pts):</span>
                                            <span class="text-muted-foreground"> Start every page with "X is..." in the first paragraph</span>
                                        </div>
                                    </li>
                                    <li class="flex items-start gap-3">
                                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-green-500 text-white text-sm font-bold" aria-hidden="true">2</span>
                                        <div>
                                            <span class="font-medium">Structured Knowledge (20 pts):</span>
                                            <span class="text-muted-foreground"> Use one H1, multiple H2s, proper hierarchy</span>
                                        </div>
                                    </li>
                                    <li class="flex items-start gap-3">
                                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-green-500 text-white text-sm font-bold" aria-hidden="true">3</span>
                                        <div>
                                            <span class="font-medium">Topic Authority (25 pts):</span>
                                            <span class="text-muted-foreground"> Write 800+ words with 3+ internal links</span>
                                        </div>
                                    </li>
                                    <li class="flex items-start gap-3">
                                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-green-500 text-white text-sm font-bold" aria-hidden="true">4</span>
                                        <div>
                                            <span class="font-medium">Machine-Readable (15 pts):</span>
                                            <span class="text-muted-foreground"> Add JSON-LD Article schema and alt text</span>
                                        </div>
                                    </li>
                                    <li class="flex items-start gap-3">
                                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-green-500 text-white text-sm font-bold" aria-hidden="true">5</span>
                                        <div>
                                            <span class="font-medium">Answerability (20 pts):</span>
                                            <span class="text-muted-foreground"> Use declarative statements AI can quote</span>
                                        </div>
                                    </li>
                                </ol>
                            </CardContent>
                        </Card>
                    </section>

                    <Separator class="my-12" />

                    <!-- Internal Linking Block -->
                    <section class="mb-12" aria-labelledby="related-resources-heading">
                        <Card class="border-primary/50">
                            <CardContent class="pt-6">
                                <h3 id="related-resources-heading" class="text-lg font-bold mb-4">Related Resources</h3>
                                <p class="text-muted-foreground mb-4">
                                    Understand the fundamentals with our guide to <Link href="/resources/what-is-geo" class="text-primary hover:underline font-medium">Generative Engine Optimization (GEO)</Link>, learn how the <Link href="/geo-score-explained" class="text-primary hover:underline font-medium">GEO Score</Link> is calculated, and explore <Link href="/definitions" class="text-primary hover:underline font-medium">official GEO definitions</Link>.
                                </p>
                                <nav aria-label="Related resources">
                                    <ul class="flex flex-wrap gap-2" role="list">
                                        <li>
                                            <Link href="/resources/what-is-geo">
                                                <Button variant="outline" size="sm">What Is GEO?</Button>
                                            </Link>
                                        </li>
                                        <li>
                                            <Link href="/geo-score-explained">
                                                <Button variant="outline" size="sm">GEO Score Explained</Button>
                                            </Link>
                                        </li>
                                        <li>
                                            <Link href="/definitions">
                                                <Button variant="outline" size="sm">GEO Definitions</Button>
                                            </Link>
                                        </li>
                                        <li>
                                            <Link href="/ai-search-visibility-guide">
                                                <Button variant="outline" size="sm">AI Visibility Guide</Button>
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
                    <p class="mt-2 text-muted-foreground">Get your GEO Score and see which optimizations will have the biggest impact.</p>
                    <div class="mt-6">
                        <Link href="/register">
                            <Button size="lg" class="gap-2">
                                Get Your GEO Score
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
