<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import {
    BookOpen,
    ArrowRight,
    ArrowLeft,
    Database,
    Target,
    FileText,
    HelpCircle,
    CheckCircle,
    Layers,
    Brain,
    Calendar,
} from 'lucide-vue-next';
import SkipNav from '@/components/resources/SkipNav.vue';
import ResourceHeader from '@/components/resources/ResourceHeader.vue';
import ResourceFooter from '@/components/resources/ResourceFooter.vue';
import ResourceBreadcrumb from '@/components/resources/ResourceBreadcrumb.vue';

const publishedDate = new Date('2026-01-18').toLocaleDateString('en-US', { month: 'long', year: 'numeric' });

const breadcrumbItems = [
    { label: 'Resources', href: '/resources' },
    { label: 'GEO Content Framework' },
];

const frameworkPillars = [
    {
        title: 'Structured Knowledge',
        icon: Database,
        items: [
            'Clear definitions',
            'Declarative statements',
            'Minimal ambiguity',
        ],
    },
    {
        title: 'Topic Authority',
        icon: Target,
        items: [
            'Narrow topical focus',
            'Depth over breadth',
            'Clear subject boundaries',
        ],
    },
    {
        title: 'Machine-Readable Formatting',
        icon: FileText,
        items: [
            'Logical headings',
            'Bullet clarity',
            'Predictable layout',
        ],
    },
    {
        title: 'High-Confidence Answerability',
        icon: HelpCircle,
        items: [
            'Direct responses to known questions',
            'FAQ coverage',
            'Reduced hallucination risk',
        ],
    },
];

const relatedArticles = [
    { slug: 'what-is-geo', title: 'What Is GEO?' },
    { slug: 'what-is-a-geo-score', title: 'What Is a GEO Score?' },
    { slug: 'how-llms-cite-sources', title: 'How LLMs Cite Sources' },
];

const jsonLd = {
    '@context': 'https://schema.org',
    '@type': 'Article',
    headline: 'The GeoSource.ai GEO Content Framework',
    description: 'A structured framework designed specifically for generative AI systems. Learn the four pillars: Structured Knowledge, Topic Authority, Machine-Readable Formatting, and High-Confidence Answerability.',
    url: 'https://geosource.ai/resources/geo-content-framework',
    datePublished: '2026-01-18',
    dateModified: '2026-01-18',
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
    mainEntityOfPage: {
        '@type': 'WebPage',
        '@id': 'https://geosource.ai/resources/geo-content-framework',
    },
};

const faqJsonLd = {
    '@context': 'https://schema.org',
    '@type': 'FAQPage',
    mainEntity: [
        {
            '@type': 'Question',
            name: 'What is the GeoSource.ai GEO Content Framework?',
            acceptedAnswer: {
                '@type': 'Answer',
                text: 'The GeoSource.ai GEO Content Framework is a structured approach designed specifically for generative AI systems. It includes four pillars: Structured Knowledge, Topic Authority, Machine-Readable Formatting, and High-Confidence Answerability.',
            },
        },
        {
            '@type': 'Question',
            name: 'What are the four pillars of the GEO Content Framework?',
            acceptedAnswer: {
                '@type': 'Answer',
                text: 'The four pillars are: 1) Structured Knowledge - clear definitions, declarative statements, minimal ambiguity. 2) Topic Authority - narrow topical focus, depth over breadth, clear subject boundaries. 3) Machine-Readable Formatting - logical headings, bullet clarity, predictable layout. 4) High-Confidence Answerability - direct responses to questions, FAQ coverage, reduced hallucination risk.',
            },
        },
        {
            '@type': 'Question',
            name: 'Why does the GEO Content Framework work?',
            acceptedAnswer: {
                '@type': 'Answer',
                text: 'The framework mirrors how LLMs retrieve and evaluate information internally. By structuring content according to these principles, you increase the likelihood that AI systems will understand, trust, and cite your content.',
            },
        },
    ],
};
</script>

<template>
    <Head title="The GeoSource.ai GEO Content Framework - GeoSource.ai">
        <meta name="description" content="A structured framework designed specifically for generative AI systems. Learn the four pillars of effective GEO content optimization." />
        <meta property="og:title" content="The GeoSource.ai GEO Content Framework" />
        <meta property="og:description" content="A structured framework designed specifically for generative AI systems." />
        <meta property="og:type" content="article" />
        <meta property="og:url" content="https://geosource.ai/resources/geo-content-framework" />
        <meta property="og:site_name" content="GeoSource.ai" />
        <meta property="article:published_time" content="2026-01-18T00:00:00Z" />
        <meta property="article:modified_time" content="2026-01-18T00:00:00Z" />
        <meta property="article:author" content="GeoSource.ai" />
        <meta property="article:section" content="Resources" />
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:site" content="@geosourceai" />
        <meta name="twitter:title" content="The GeoSource.ai GEO Content Framework" />
        <meta name="twitter:description" content="A structured framework designed specifically for generative AI systems." />
        <meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1" />
        <link rel="canonical" href="https://geosource.ai/resources/geo-content-framework" />
        <component :is="'script'" type="application/ld+json">{{ JSON.stringify(jsonLd) }}</component>
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
                            <BookOpen class="mr-1 h-3 w-3" aria-hidden="true" />
                            Framework
                        </Badge>
                        <h1 class="text-3xl font-bold tracking-tight sm:text-4xl lg:text-5xl">
                            The GeoSource.ai GEO Content Framework
                        </h1>
                        <p class="mt-4 text-lg text-muted-foreground">
                            A structured approach designed specifically for generative AI systems.
                        </p>
                        <div class="mt-4 flex items-center gap-2 text-sm text-muted-foreground">
                            <Calendar class="h-4 w-4" aria-hidden="true" />
                            <time datetime="2026-01-18">{{ publishedDate }}</time>
                        </div>
                    </header>

                    <!-- Introduction -->
                    <section class="mb-12" aria-labelledby="intro-heading">
                        <h2 id="intro-heading" class="sr-only">Introduction</h2>
                        <Card class="border-primary/50 bg-primary/5">
                            <CardContent class="pt-6">
                                <p class="text-lg">
                                    GeoSource.ai uses a structured framework designed specifically for generative AI systems.
                                </p>
                            </CardContent>
                        </Card>
                    </section>

                    <!-- The Four Pillars -->
                    <section class="mb-12" aria-labelledby="pillars">
                        <h2 id="pillars" class="text-2xl font-bold mb-6">The Framework Includes</h2>

                        <div class="space-y-6">
                            <Card v-for="(pillar, index) in frameworkPillars" :key="pillar.title" class="overflow-hidden">
                                <CardHeader class="bg-muted/50">
                                    <div class="flex items-center gap-3">
                                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary text-primary-foreground text-sm font-bold">
                                            {{ index + 1 }}
                                        </span>
                                        <component :is="pillar.icon" class="h-6 w-6 text-primary" aria-hidden="true" />
                                        <CardTitle>{{ pillar.title }}</CardTitle>
                                    </div>
                                </CardHeader>
                                <CardContent class="pt-6">
                                    <ul class="space-y-2">
                                        <li v-for="item in pillar.items" :key="item" class="flex items-center gap-2">
                                            <CheckCircle class="h-4 w-4 text-green-500 shrink-0" aria-hidden="true" />
                                            <span>{{ item }}</span>
                                        </li>
                                    </ul>
                                </CardContent>
                            </Card>
                        </div>
                    </section>

                    <Separator class="my-12" />

                    <!-- Why It Works -->
                    <section class="mb-12" aria-labelledby="why-it-works">
                        <h2 id="why-it-works" class="text-2xl font-bold mb-6">Why This Framework Works</h2>

                        <Card class="border-primary/50 bg-primary/5">
                            <CardContent class="pt-6">
                                <div class="flex items-start gap-4">
                                    <Brain class="h-8 w-8 text-primary shrink-0" aria-hidden="true" />
                                    <div>
                                        <p class="text-lg font-medium">
                                            This framework mirrors how LLMs retrieve and evaluate information internally.
                                        </p>
                                        <p class="mt-4 text-muted-foreground">
                                            By structuring your content according to these four pillars, you:
                                        </p>
                                        <ul class="mt-4 space-y-2">
                                            <li class="flex items-center gap-2">
                                                <CheckCircle class="h-5 w-5 text-green-500" aria-hidden="true" />
                                                <span>Make your content easier for AI to parse</span>
                                            </li>
                                            <li class="flex items-center gap-2">
                                                <CheckCircle class="h-5 w-5 text-green-500" aria-hidden="true" />
                                                <span>Increase trust signals that AI systems recognize</span>
                                            </li>
                                            <li class="flex items-center gap-2">
                                                <CheckCircle class="h-5 w-5 text-green-500" aria-hidden="true" />
                                                <span>Reduce the risk of being misunderstood or ignored</span>
                                            </li>
                                            <li class="flex items-center gap-2">
                                                <CheckCircle class="h-5 w-5 text-green-500" aria-hidden="true" />
                                                <span>Improve your likelihood of being cited</span>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </section>

                    <Separator class="my-12" />

                    <!-- Implementation Checklist -->
                    <section class="mb-12" aria-labelledby="checklist">
                        <h2 id="checklist" class="text-2xl font-bold mb-6">Implementation Checklist</h2>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <Card>
                                <CardHeader>
                                    <CardTitle class="text-lg">Content Structure</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <ul class="space-y-2 text-sm">
                                        <li class="flex items-start gap-2">
                                            <CheckCircle class="h-4 w-4 text-muted-foreground shrink-0 mt-0.5" aria-hidden="true" />
                                            <span>Start with a clear definition</span>
                                        </li>
                                        <li class="flex items-start gap-2">
                                            <CheckCircle class="h-4 w-4 text-muted-foreground shrink-0 mt-0.5" aria-hidden="true" />
                                            <span>Use hierarchical headings (H1 → H2 → H3)</span>
                                        </li>
                                        <li class="flex items-start gap-2">
                                            <CheckCircle class="h-4 w-4 text-muted-foreground shrink-0 mt-0.5" aria-hidden="true" />
                                            <span>Include bullet points for lists</span>
                                        </li>
                                        <li class="flex items-start gap-2">
                                            <CheckCircle class="h-4 w-4 text-muted-foreground shrink-0 mt-0.5" aria-hidden="true" />
                                            <span>Add tables for comparisons</span>
                                        </li>
                                    </ul>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader>
                                    <CardTitle class="text-lg">Topic Coverage</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <ul class="space-y-2 text-sm">
                                        <li class="flex items-start gap-2">
                                            <CheckCircle class="h-4 w-4 text-muted-foreground shrink-0 mt-0.5" aria-hidden="true" />
                                            <span>Stay focused on one topic per page</span>
                                        </li>
                                        <li class="flex items-start gap-2">
                                            <CheckCircle class="h-4 w-4 text-muted-foreground shrink-0 mt-0.5" aria-hidden="true" />
                                            <span>Cover the topic comprehensively</span>
                                        </li>
                                        <li class="flex items-start gap-2">
                                            <CheckCircle class="h-4 w-4 text-muted-foreground shrink-0 mt-0.5" aria-hidden="true" />
                                            <span>Link to related topics internally</span>
                                        </li>
                                        <li class="flex items-start gap-2">
                                            <CheckCircle class="h-4 w-4 text-muted-foreground shrink-0 mt-0.5" aria-hidden="true" />
                                            <span>Use consistent terminology</span>
                                        </li>
                                    </ul>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader>
                                    <CardTitle class="text-lg">Answerability</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <ul class="space-y-2 text-sm">
                                        <li class="flex items-start gap-2">
                                            <CheckCircle class="h-4 w-4 text-muted-foreground shrink-0 mt-0.5" aria-hidden="true" />
                                            <span>Include an FAQ section</span>
                                        </li>
                                        <li class="flex items-start gap-2">
                                            <CheckCircle class="h-4 w-4 text-muted-foreground shrink-0 mt-0.5" aria-hidden="true" />
                                            <span>Answer questions directly</span>
                                        </li>
                                        <li class="flex items-start gap-2">
                                            <CheckCircle class="h-4 w-4 text-muted-foreground shrink-0 mt-0.5" aria-hidden="true" />
                                            <span>Use "X is defined as..." patterns</span>
                                        </li>
                                        <li class="flex items-start gap-2">
                                            <CheckCircle class="h-4 w-4 text-muted-foreground shrink-0 mt-0.5" aria-hidden="true" />
                                            <span>State facts confidently</span>
                                        </li>
                                    </ul>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader>
                                    <CardTitle class="text-lg">Technical Optimization</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <ul class="space-y-2 text-sm">
                                        <li class="flex items-start gap-2">
                                            <CheckCircle class="h-4 w-4 text-muted-foreground shrink-0 mt-0.5" aria-hidden="true" />
                                            <span>Add JSON-LD structured data</span>
                                        </li>
                                        <li class="flex items-start gap-2">
                                            <CheckCircle class="h-4 w-4 text-muted-foreground shrink-0 mt-0.5" aria-hidden="true" />
                                            <span>Use semantic HTML tags</span>
                                        </li>
                                        <li class="flex items-start gap-2">
                                            <CheckCircle class="h-4 w-4 text-muted-foreground shrink-0 mt-0.5" aria-hidden="true" />
                                            <span>Implement proper meta tags</span>
                                        </li>
                                        <li class="flex items-start gap-2">
                                            <CheckCircle class="h-4 w-4 text-muted-foreground shrink-0 mt-0.5" aria-hidden="true" />
                                            <span>Ensure fast page load times</span>
                                        </li>
                                    </ul>
                                </CardContent>
                            </Card>
                        </div>
                    </section>

                    <Separator class="my-12" />

                    <!-- Related Resources -->
                    <section aria-labelledby="related">
                        <h2 id="related" class="text-2xl font-bold mb-6">Related Resources</h2>
                        <ul class="grid gap-4 sm:grid-cols-3" aria-label="Related resource articles">
                            <li v-for="article in relatedArticles" :key="article.slug">
                                <Link
                                    :href="`/resources/${article.slug}`"
                                    class="group block h-full"
                                    :aria-label="`Read more about ${article.title}`"
                                >
                                    <Card class="h-full transition-colors hover:border-primary/50">
                                        <CardContent class="pt-6">
                                            <p class="font-medium group-hover:text-primary transition-colors">
                                                {{ article.title }}
                                            </p>
                                            <span class="inline-flex items-center text-sm text-primary mt-2">
                                                Read more
                                                <ArrowRight class="ml-1 h-3 w-3 transition-transform group-hover:translate-x-1" aria-hidden="true" />
                                            </span>
                                        </CardContent>
                                    </Card>
                                </Link>
                            </li>
                        </ul>
                    </section>

                    <!-- Navigation -->
                    <nav class="mt-12 flex items-center justify-between border-t pt-8" aria-label="Article navigation">
                        <Link href="/resources/what-is-a-geo-score" class="inline-flex items-center text-muted-foreground hover:text-foreground">
                            <ArrowLeft class="mr-2 h-4 w-4" aria-hidden="true" />
                            Previous: What Is a GEO Score?
                        </Link>
                        <Link href="/resources" class="inline-flex items-center text-primary hover:underline">
                            View All Resources
                            <ArrowRight class="ml-2 h-4 w-4" aria-hidden="true" />
                        </Link>
                    </nav>
                </div>
            </article>

            <!-- CTA Section -->
            <section class="border-t bg-muted/30 py-12" aria-labelledby="cta-heading">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 text-center">
                    <h2 id="cta-heading" class="text-2xl font-bold">Put the framework into practice</h2>
                    <p class="mt-2 text-muted-foreground">Get your GEO Score and see how your content measures up against the framework.</p>
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
