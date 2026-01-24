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
    CheckCircle,
    XCircle,
    Building2,
    User,
    Lightbulb,
    Target,
    Calendar,
} from 'lucide-vue-next';
import SkipNav from '@/components/resources/SkipNav.vue';
import ResourceHeader from '@/components/resources/ResourceHeader.vue';
import ResourceFooter from '@/components/resources/ResourceFooter.vue';
import ResourceBreadcrumb from '@/components/resources/ResourceBreadcrumb.vue';

const breadcrumbItems = [
    { label: 'Resources', href: '/resources' },
    { label: 'How LLMs Cite Sources' },
];

const publishedDate = new Date('2026-01-18').toLocaleDateString('en-US', { month: 'long', year: 'numeric' });

const citationSignals = [
    { signal: 'Direct definitions', description: '"X is defined as…"' },
    { signal: 'Structured formatting', description: 'Clear headings, lists, tables' },
    { signal: 'Topic depth', description: 'Comprehensive coverage of subject' },
    { signal: 'Entity consistency', description: 'Same terms used throughout' },
    { signal: 'Low contradiction risk', description: 'Factually consistent statements' },
    { signal: 'Alignment across sources', description: 'Matches other authoritative content' },
];

const bigBrandProblems = [
    'Long marketing pages',
    'Vague explanations',
    'Broad positioning statements',
];

const smallSiteAdvantages = [
    'Direct definitions',
    'Narrow topical focus',
    'Clean structure',
];

const relatedArticles = [
    { slug: 'how-ai-search-works', title: 'How AI Search Works' },
    { slug: 'what-is-a-geo-score', title: 'What Is a GEO Score?' },
    { slug: 'geo-content-framework', title: 'GEO Content Framework' },
];

const jsonLd = {
    '@context': 'https://schema.org',
    '@type': 'Article',
    headline: 'How Large Language Models Choose Which Sources to Cite',
    description: 'Discover the signals LLMs use to select high-confidence sources for citations. Learn why smaller sites can outperform big brands.',
    url: 'https://geosource.ai/resources/how-llms-cite-sources',
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
        '@id': 'https://geosource.ai/resources/how-llms-cite-sources',
    },
};

const faqJsonLd = {
    '@context': 'https://schema.org',
    '@type': 'FAQPage',
    mainEntity: [
        {
            '@type': 'Question',
            name: 'How do LLMs choose which sources to cite?',
            acceptedAnswer: {
                '@type': 'Answer',
                text: 'LLMs look for high-confidence answer sources using signals like direct definitions, structured formatting, topic depth, entity consistency, low contradiction risk, and alignment across multiple sources.',
            },
        },
        {
            '@type': 'Question',
            name: 'Why can smaller sites outperform big brands in AI citations?',
            acceptedAnswer: {
                '@type': 'Answer',
                text: 'Large brands often publish long marketing pages, vague explanations, and broad positioning statements. Smaller sites often publish direct definitions, narrow topical focus, and clean structure. Generative engines reward clarity — not brand size.',
            },
        },
        {
            '@type': 'Question',
            name: 'What type of content is unlikely to be cited by AI?',
            acceptedAnswer: {
                '@type': 'Answer',
                text: 'Pages that contain ambiguous language, marketing fluff, or unclear terminology are far less likely to be cited by AI systems. LLMs need clear, structured, factual content to generate confident answers.',
            },
        },
    ],
};
</script>

<template>
    <Head title="How Large Language Models Choose Which Sources to Cite - GeoSource.ai">
        <meta name="description" content="Discover the signals LLMs use to select high-confidence sources for citations. Learn why smaller sites can outperform big brands in AI search." />
        <meta property="og:title" content="How Large Language Models Choose Which Sources to Cite" />
        <meta property="og:description" content="Discover the signals LLMs use to select high-confidence sources for citations." />
        <meta property="og:type" content="article" />
        <meta property="og:url" content="https://geosource.ai/resources/how-llms-cite-sources" />
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" content="How Large Language Models Choose Which Sources to Cite" />
        <meta name="twitter:description" content="Discover the signals LLMs use to select high-confidence sources for citations." />
        <link rel="canonical" href="https://geosource.ai/resources/how-llms-cite-sources" />
        <meta property="og:site_name" content="GeoSource.ai" />
        <meta property="article:published_time" content="2026-01-18" />
        <meta property="article:modified_time" content="2026-01-18" />
        <meta property="article:author" content="GeoSource.ai" />
        <meta property="article:section" content="Deep Dive" />
        <meta name="twitter:site" content="@geosourceai" />
        <meta name="robots" content="index, follow" />
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
                            <BookOpen class="mr-1 h-3 w-3" aria-hidden="true" />
                            Deep Dive
                        </Badge>
                        <h1 class="text-3xl font-bold tracking-tight sm:text-4xl lg:text-5xl">
                            How Large Language Models Choose Which Sources to Cite
                        </h1>
                        <p class="mt-4 text-lg text-muted-foreground">
                            Understanding the citation selection process of AI systems.
                        </p>
                        <div class="mt-4 flex items-center gap-2 text-sm text-muted-foreground">
                            <Calendar class="h-4 w-4" aria-hidden="true" />
                            <time datetime="2026-01-18">{{ publishedDate }}</time>
                        </div>
                    </header>

                    <!-- Key Point -->
                    <section class="mb-12" aria-labelledby="key-point-heading">
                        <h2 id="key-point-heading" class="sr-only">Key Point</h2>
                        <Card class="border-primary/50 bg-primary/5">
                            <CardContent class="pt-6">
                                <p class="text-lg">
                                    AI systems do not choose citations randomly.
                                </p>
                                <p class="text-xl font-bold text-primary mt-2">
                                    They look for high-confidence answer sources.
                                </p>
                            </CardContent>
                        </Card>
                    </section>

                    <!-- Citation Signals -->
                    <section class="mb-12" aria-labelledby="citation-signals">
                        <h2 id="citation-signals" class="text-2xl font-bold mb-6">Citation Signals Commonly Used</h2>
                        <div class="space-y-4">
                            <div v-for="item in citationSignals" :key="item.signal" class="flex items-start gap-3 p-4 rounded-lg border bg-card">
                                <CheckCircle class="h-5 w-5 text-green-500 shrink-0 mt-0.5" aria-hidden="true" />
                                <div>
                                    <p class="font-medium">{{ item.signal }}</p>
                                    <p class="text-sm text-muted-foreground">{{ item.description }}</p>
                                </div>
                            </div>
                        </div>

                        <Card class="mt-8 border-amber-500/50 bg-amber-500/5">
                            <CardContent class="pt-6">
                                <div class="flex items-start gap-3">
                                    <Lightbulb class="h-6 w-6 text-amber-500 shrink-0 mt-0.5" aria-hidden="true" />
                                    <p class="text-muted-foreground">
                                        Pages that contain <strong class="text-foreground">ambiguous language</strong>, <strong class="text-foreground">marketing fluff</strong>, or <strong class="text-foreground">unclear terminology</strong> are far less likely to be cited.
                                    </p>
                                </div>
                            </CardContent>
                        </Card>
                    </section>

                    <Separator class="my-12" />

                    <!-- Why Smaller Sites Can Win -->
                    <section class="mb-12" aria-labelledby="smaller-sites">
                        <h2 id="smaller-sites" class="text-2xl font-bold mb-6">Why Smaller Sites Can Outperform Big Brands</h2>

                        <div class="grid gap-6 sm:grid-cols-2">
                            <Card class="border-destructive/50">
                                <CardHeader>
                                    <div class="flex items-center gap-2">
                                        <Building2 class="h-5 w-5" aria-hidden="true" />
                                        <CardTitle class="text-lg">Large Brands Often Publish</CardTitle>
                                    </div>
                                </CardHeader>
                                <CardContent>
                                    <ul class="space-y-2">
                                        <li v-for="item in bigBrandProblems" :key="item" class="flex items-center gap-2">
                                            <XCircle class="h-4 w-4 text-destructive shrink-0" aria-hidden="true" />
                                            <span class="text-muted-foreground">{{ item }}</span>
                                        </li>
                                    </ul>
                                </CardContent>
                            </Card>

                            <Card class="border-green-500/50 bg-green-500/5">
                                <CardHeader>
                                    <div class="flex items-center gap-2">
                                        <User class="h-5 w-5 text-green-500" aria-hidden="true" />
                                        <CardTitle class="text-lg">Smaller Sites Often Publish</CardTitle>
                                    </div>
                                </CardHeader>
                                <CardContent>
                                    <ul class="space-y-2">
                                        <li v-for="item in smallSiteAdvantages" :key="item" class="flex items-center gap-2">
                                            <CheckCircle class="h-4 w-4 text-green-500 shrink-0" aria-hidden="true" />
                                            <span>{{ item }}</span>
                                        </li>
                                    </ul>
                                </CardContent>
                            </Card>
                        </div>

                        <Card class="mt-8 border-primary/50 bg-primary/5">
                            <CardContent class="pt-6 text-center">
                                <p class="text-lg">
                                    Generative engines reward <strong class="text-primary">clarity</strong> — not brand size.
                                </p>
                                <p class="text-xl font-bold mt-2">
                                    This is why GEO matters.
                                </p>
                            </CardContent>
                        </Card>
                    </section>

                    <Separator class="my-12" />

                    <!-- Key Takeaway -->
                    <section class="mb-12" aria-labelledby="takeaway">
                        <h2 id="takeaway" class="text-2xl font-bold mb-6">Key Takeaway</h2>
                        <Card class="border-primary bg-primary/5">
                            <CardContent class="pt-6">
                                <div class="flex items-start gap-4">
                                    <Target class="h-8 w-8 text-primary shrink-0" aria-hidden="true" />
                                    <div>
                                        <p class="text-lg">
                                            To earn AI citations, focus on:
                                        </p>
                                        <ul class="mt-4 space-y-2">
                                            <li class="flex items-center gap-2">
                                                <CheckCircle class="h-5 w-5 text-primary" aria-hidden="true" />
                                                <span class="font-medium">Clear, direct definitions</span>
                                            </li>
                                            <li class="flex items-center gap-2">
                                                <CheckCircle class="h-5 w-5 text-primary" aria-hidden="true" />
                                                <span class="font-medium">Structured, scannable content</span>
                                            </li>
                                            <li class="flex items-center gap-2">
                                                <CheckCircle class="h-5 w-5 text-primary" aria-hidden="true" />
                                                <span class="font-medium">Consistent terminology</span>
                                            </li>
                                            <li class="flex items-center gap-2">
                                                <CheckCircle class="h-5 w-5 text-primary" aria-hidden="true" />
                                                <span class="font-medium">Factual, verifiable statements</span>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </section>

                    <Separator class="my-12" />

                    <!-- Related Resources -->
                    <section aria-labelledby="related">
                        <h2 id="related" class="text-2xl font-bold mb-6">Related Resources</h2>
                        <ul role="list" class="grid gap-4 sm:grid-cols-3">
                            <li v-for="article in relatedArticles" :key="article.slug">
                                <Link
                                    :href="`/resources/${article.slug}`"
                                    class="group"
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
                        <Link href="/resources/how-ai-search-works" class="inline-flex items-center text-muted-foreground hover:text-foreground">
                            <ArrowLeft class="mr-2 h-4 w-4" aria-hidden="true" />
                            Previous: How AI Search Works
                        </Link>
                        <Link href="/resources/what-is-a-geo-score" class="inline-flex items-center text-primary hover:underline">
                            Next: What Is a GEO Score?
                            <ArrowRight class="ml-2 h-4 w-4" aria-hidden="true" />
                        </Link>
                    </nav>
                </div>
            </article>

            <!-- CTA Section -->
            <section class="border-t bg-muted/30 py-12" aria-labelledby="cta-heading">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 text-center">
                    <h2 id="cta-heading" class="text-2xl font-bold">Make your content citation-ready</h2>
                    <p class="mt-2 text-muted-foreground">Get your GEO Score and see how likely AI systems are to cite your content.</p>
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
