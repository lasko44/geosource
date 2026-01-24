<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import {
    ArrowRight,
    ArrowLeft,
    Bot,
    Lightbulb,
    CheckCircle,
    XCircle,
    AlertTriangle,
    Calendar,
} from 'lucide-vue-next';
import SkipNav from '@/components/resources/SkipNav.vue';
import ResourceHeader from '@/components/resources/ResourceHeader.vue';
import ResourceFooter from '@/components/resources/ResourceFooter.vue';
import ResourceBreadcrumb from '@/components/resources/ResourceBreadcrumb.vue';

const publishedDate = '2026-01-20';
const publishedDateFormatted = new Date(publishedDate).toLocaleDateString('en-US', { month: 'long', year: 'numeric' });

const breadcrumbItems = [
    { label: 'Resources', href: '/resources' },
    { label: 'AI Accessibility for GEO' },
];

const accessibilityBarriers = [
    'Client-side rendering (JavaScript-only content)',
    'Gated content requiring authentication',
    'Heavy scripts that slow parsing',
    'Missing HTML structure',
    'Unclear or missing semantic markup',
];

const bestPractices = [
    'Server-side rendering (SSR)',
    'Clean, semantic HTML',
    'Proper heading hierarchy',
    'Readable, accessible text',
    'Public access to key resources',
];

const relatedDefinitions = [
    { slug: 'what-is-geo', title: 'What Is Generative Engine Optimization (GEO)?' },
    { slug: 'why-ssr-matters-for-geo', title: 'Why SSR Matters for GEO' },
];

const relatedResources = [
    { slug: 'e-e-a-t-and-geo', title: 'E-E-A-T and Generative Engine Optimization (GEO)' },
    { slug: 'ai-citations-and-geo', title: 'Citations and GEO: How AI Chooses Sources' },
    { slug: 'why-llms-txt-matters', title: 'Why llms.txt Matters for GEO' },
    { slug: 'readability-and-geo', title: 'Readability and GEO' },
    { slug: 'multimedia-and-geo', title: 'Multimedia and GEO' },
];

const jsonLd = {
    '@context': 'https://schema.org',
    '@type': 'Article',
    headline: 'AI Accessibility and GEO',
    description: 'Learn how AI accessibility affects your GEO performance and how to ensure AI systems can properly access and understand your content.',
    url: 'https://geosource.ai/resources/ai-accessibility-for-geo',
    datePublished: '2026-01-20',
    dateModified: '2026-01-20',
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
        '@id': 'https://geosource.ai/resources/ai-accessibility-for-geo',
    },
};

const faqJsonLd = {
    '@context': 'https://schema.org',
    '@type': 'FAQPage',
    mainEntity: [
        {
            '@type': 'Question',
            name: 'What is AI accessibility?',
            acceptedAnswer: {
                '@type': 'Answer',
                text: 'AI accessibility refers to how easily large language models can retrieve, parse, and understand your content. It is not the same as human accessibility but complements it.',
            },
        },
        {
            '@type': 'Question',
            name: 'Why does AI accessibility matter for GEO?',
            acceptedAnswer: {
                '@type': 'Answer',
                text: 'If AI cannot access your content, it cannot include it in answers. Common barriers include client-side rendering, gated content, heavy scripts, missing HTML, and unclear structure.',
            },
        },
        {
            '@type': 'Question',
            name: 'How can I improve AI accessibility?',
            acceptedAnswer: {
                '@type': 'Answer',
                text: 'Best practices include server-side rendering, clean HTML, semantic headings, readable text, and public access to resources. These directly impact GEO scores.',
            },
        },
    ],
};
</script>

<template>
    <Head title="AI Accessibility and GEO - GeoSource.ai">
        <meta name="description" content="Learn how AI accessibility affects your GEO performance and how to ensure AI systems can properly access and understand your content." />
        <meta property="og:title" content="AI Accessibility and GEO" />
        <meta property="og:description" content="Learn how AI accessibility affects your GEO performance." />
        <meta property="og:type" content="article" />
        <meta property="og:url" content="https://geosource.ai/resources/ai-accessibility-for-geo" />
        <meta property="og:site_name" content="GeoSource.ai" />
        <meta property="article:published_time" content="2026-01-20" />
        <meta property="article:modified_time" content="2026-01-20" />
        <meta property="article:author" content="GeoSource.ai" />
        <meta property="article:section" content="Technical" />
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" content="AI Accessibility and GEO" />
        <meta name="twitter:description" content="Learn how AI accessibility affects your GEO performance." />
        <meta name="twitter:site" content="@geosourceai" />
        <meta name="robots" content="index, follow" />
        <link rel="canonical" href="https://geosource.ai/resources/ai-accessibility-for-geo" />
        <component :is="'script'" type="application/ld+json">{{ JSON.stringify(jsonLd) }}</component>
        <component :is="'script'" type="application/ld+json">{{ JSON.stringify(faqJsonLd) }}</component>
    </Head>

    <div class="min-h-screen bg-background text-foreground">
        <SkipNav />
        <ResourceHeader />

        <main id="main-content" role="main">
            <ResourceBreadcrumb :items="breadcrumbItems" />

            <!-- Article -->
            <article class="py-12">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <!-- Header -->
                    <header class="mb-12">
                        <Badge variant="secondary" class="mb-4">
                            <Bot class="mr-1 h-3 w-3" aria-hidden="true" />
                            GEO Pillar
                        </Badge>
                        <h1 class="text-3xl font-bold tracking-tight sm:text-4xl lg:text-5xl">
                            AI Accessibility and GEO
                        </h1>
                        <p class="mt-4 text-lg text-muted-foreground">
                            Ensuring AI systems can access and understand your content is the foundation of GEO success.
                        </p>
                        <div class="mt-4 flex items-center gap-2 text-sm text-muted-foreground">
                            <Calendar class="h-4 w-4" aria-hidden="true" />
                            <time :datetime="publishedDate">{{ publishedDateFormatted }}</time>
                        </div>
                    </header>

                    <!-- Intro with required links -->
                    <section class="mb-12" aria-labelledby="intro-heading">
                        <h2 id="intro-heading" class="sr-only">Introduction</h2>
                        <p class="text-muted-foreground mb-4">
                            In <Link href="/resources/what-is-geo" class="text-primary hover:underline">Generative Engine Optimization (GEO)</Link>, accessibility is the foundation of visibility. If AI systems cannot access your content, they cannot cite it. This directly impacts your <Link href="/resources/what-is-a-geo-score" class="text-primary hover:underline">GEO Score</Link> and visibility in the <Link href="/" class="text-primary hover:underline">GeoSource.ai Platform</Link>.
                        </p>
                    </section>

                    <!-- Definition Section -->
                    <section class="mb-12" aria-labelledby="definition">
                        <h2 id="definition" class="text-2xl font-bold mb-6">What Is AI Accessibility?</h2>
                        <Card class="border-primary/50 bg-primary/5">
                            <CardContent class="pt-6">
                                <p class="text-lg leading-relaxed">
                                    <dfn class="font-semibold not-italic"><strong>AI accessibility</strong></dfn> refers to how easily large language models can <strong>retrieve, parse, and understand</strong> your content. It is not the same as human accessibility — but it complements it.
                                </p>
                            </CardContent>
                        </Card>
                    </section>

                    <Separator class="my-12" />

                    <!-- Why It Matters -->
                    <section class="mb-12" aria-labelledby="why-matters">
                        <h2 id="why-matters" class="text-2xl font-bold mb-6">Why Accessibility Matters for GEO</h2>
                        <Card class="border-amber-500/50 bg-amber-500/5">
                            <CardContent class="pt-6">
                                <div class="flex items-start gap-3">
                                    <AlertTriangle class="h-6 w-6 text-amber-500 shrink-0 mt-0.5" aria-hidden="true" />
                                    <p class="text-lg">
                                        <strong class="text-foreground">If AI cannot access your content, it cannot include it in answers.</strong>
                                    </p>
                                </div>
                            </CardContent>
                        </Card>
                    </section>

                    <Separator class="my-12" />

                    <!-- Barriers -->
                    <section class="mb-12" aria-labelledby="barriers">
                        <h2 id="barriers" class="text-2xl font-bold mb-6">Common Accessibility Barriers</h2>
                        <p class="text-muted-foreground mb-6">
                            These barriers prevent AI systems from accessing and understanding your content:
                        </p>
                        <Card class="border-red-500/30 bg-red-500/5">
                            <CardContent class="pt-6">
                                <ul class="space-y-3">
                                    <li v-for="barrier in accessibilityBarriers" :key="barrier" class="flex items-start gap-3">
                                        <XCircle class="h-5 w-5 text-red-500 shrink-0 mt-0.5" aria-hidden="true" />
                                        <span>{{ barrier }}</span>
                                    </li>
                                </ul>
                            </CardContent>
                        </Card>
                    </section>

                    <Separator class="my-12" />

                    <!-- Best Practices -->
                    <section class="mb-12" aria-labelledby="best-practices">
                        <h2 id="best-practices" class="text-2xl font-bold mb-6">Improving AI Accessibility</h2>
                        <p class="text-muted-foreground mb-6">
                            Best practices for ensuring AI can access your content:
                        </p>
                        <Card>
                            <CardContent class="pt-6">
                                <ul class="space-y-3">
                                    <li v-for="practice in bestPractices" :key="practice" class="flex items-start gap-3">
                                        <CheckCircle class="h-5 w-5 text-green-500 shrink-0 mt-0.5" aria-hidden="true" />
                                        <span>{{ practice }}</span>
                                    </li>
                                </ul>
                            </CardContent>
                        </Card>
                        <p class="mt-6 text-muted-foreground">
                            <strong class="text-foreground">These directly impact GEO scores.</strong> Learn more about <Link href="/resources/why-ssr-matters-for-geo" class="text-primary hover:underline">why SSR matters for GEO</Link>.
                        </p>
                    </section>

                    <Separator class="my-12" />

                    <!-- Technical Considerations -->
                    <section class="mb-12" aria-labelledby="technical">
                        <h2 id="technical" class="text-2xl font-bold mb-6">Technical Considerations</h2>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <Card>
                                <CardContent class="pt-6">
                                    <h3 class="font-semibold text-green-600 mb-2">Recommended</h3>
                                    <ul class="space-y-2 text-sm text-muted-foreground">
                                        <li>• Server-side rendering (SSR/SSG)</li>
                                        <li>• Semantic HTML5 elements</li>
                                        <li>• Proper heading hierarchy (H1-H6)</li>
                                        <li>• Alt text on images</li>
                                        <li>• robots.txt allowing AI crawlers</li>
                                    </ul>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardContent class="pt-6">
                                    <h3 class="font-semibold text-red-600 mb-2">Avoid</h3>
                                    <ul class="space-y-2 text-sm text-muted-foreground">
                                        <li>• JavaScript-only rendering</li>
                                        <li>• Content behind login walls</li>
                                        <li>• Blocking AI crawlers in robots.txt</li>
                                        <li>• Excessive scripts and bloat</li>
                                        <li>• Missing meta tags</li>
                                    </ul>
                                </CardContent>
                            </Card>
                        </div>
                    </section>

                    <Separator class="my-12" />

                    <!-- Key Takeaway -->
                    <section class="mb-12" aria-labelledby="takeaway">
                        <h2 id="takeaway" class="text-2xl font-bold mb-6">Key Takeaway</h2>
                        <Card class="border-primary bg-primary/5">
                            <CardContent class="pt-6">
                                <p class="text-xl font-medium text-center">
                                    Accessibility determines visibility.<br />
                                    If AI can't see it, it can't cite it.
                                </p>
                            </CardContent>
                        </Card>
                    </section>

                    <Separator class="my-12" />

                    <!-- Related Definitions -->
                    <section class="mb-12" aria-labelledby="related-definitions">
                        <h2 id="related-definitions" class="text-2xl font-bold mb-6">Related Definitions</h2>
                        <ul class="grid gap-4 sm:grid-cols-2" aria-label="Related definition articles">
                            <li v-for="article in relatedDefinitions" :key="article.slug">
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

                    <Separator class="my-12" />

                    <!-- Related Resources -->
                    <section aria-labelledby="related">
                        <h2 id="related" class="text-2xl font-bold mb-6">Related Resources</h2>
                        <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3" aria-label="Related resource articles">
                            <li v-for="article in relatedResources" :key="article.slug">
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
                        <Link href="/resources/ai-citations-and-geo" class="inline-flex items-center text-muted-foreground hover:text-foreground">
                            <ArrowLeft class="mr-2 h-4 w-4" aria-hidden="true" />
                            Previous: Citations and GEO
                        </Link>
                        <Link href="/resources/content-freshness-for-geo" class="inline-flex items-center text-primary hover:underline">
                            Next: Content Freshness
                            <ArrowRight class="ml-2 h-4 w-4" aria-hidden="true" />
                        </Link>
                    </nav>
                </div>
            </article>

            <!-- CTA Section -->
            <section class="border-t bg-muted/30 py-12" aria-labelledby="cta-heading">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 text-center">
                    <h2 id="cta-heading" class="text-2xl font-bold">Ready to optimize for AI?</h2>
                    <p class="mt-2 text-muted-foreground">Get your GEO Score and start improving your AI visibility.</p>
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

        <ResourceFooter />
    </div>
</template>
