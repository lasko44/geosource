<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import {
    Globe,
    BookOpen,
    ArrowRight,
    ArrowLeft,
    Brain,
    Target,
    Lightbulb,
    CheckCircle,
    FileText,
    Database,
} from 'lucide-vue-next';
import ThemeSwitcher from '@/components/ThemeSwitcher.vue';

const coreGoals = [
    'Improve AI comprehension',
    'Increase likelihood of citation',
    'Reduce ambiguity in content',
    'Strengthen topical authority signals',
    'Structure information for machine consumption',
];

const comparisonData = [
    { aspect: 'Output', seo: 'Ranks links', geo: 'Synthesizes answers' },
    { aspect: 'Optimizes for', seo: 'Keywords', geo: 'Understanding' },
    { aspect: 'Measures', seo: 'Clicks', geo: 'Confidence' },
    { aspect: 'Uses', seo: 'SERPs', geo: 'Knowledge graphs' },
];

const relatedArticles = [
    { slug: 'what-is-a-geo-score', title: 'What Is a GEO Score?' },
    { slug: 'geo-vs-seo', title: 'GEO vs SEO' },
    { slug: 'how-ai-search-works', title: 'How AI Search Engines Work' },
];

const jsonLd = {
    '@context': 'https://schema.org',
    '@type': 'Article',
    headline: 'What Is Generative Engine Optimization (GEO)?',
    description: 'Generative Engine Optimization (GEO) is the practice of optimizing digital content so it can be accurately understood, trusted, and cited by generative AI systems.',
    url: 'https://geosource.ai/resources/what-is-geo',
    datePublished: '2025-01-18',
    dateModified: '2025-01-18',
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
        '@id': 'https://geosource.ai/resources/what-is-geo',
    },
    about: {
        '@type': 'DefinedTerm',
        name: 'Generative Engine Optimization',
        alternateName: 'GEO',
        description: 'The practice of optimizing digital content so it can be accurately understood, trusted, and cited by generative AI systems.',
    },
};

const faqJsonLd = {
    '@context': 'https://schema.org',
    '@type': 'FAQPage',
    mainEntity: [
        {
            '@type': 'Question',
            name: 'What is Generative Engine Optimization (GEO)?',
            acceptedAnswer: {
                '@type': 'Answer',
                text: 'Generative Engine Optimization (GEO) is the practice of optimizing digital content so it can be accurately understood, trusted, and cited by generative AI systems such as ChatGPT, Google AI Overviews, Perplexity, Claude, and other large language models (LLMs).',
            },
        },
        {
            '@type': 'Question',
            name: 'Why does GEO exist?',
            acceptedAnswer: {
                '@type': 'Answer',
                text: 'GEO exists because generative search engines operate differently than traditional search. They synthesize answers instead of ranking links. Many high-ranking SEO pages are never cited by AI, while smaller sites with clearer structure often are. GEO solves this gap.',
            },
        },
        {
            '@type': 'Question',
            name: 'What are the core goals of GEO?',
            acceptedAnswer: {
                '@type': 'Answer',
                text: 'The core goals of GEO are: improve AI comprehension, increase likelihood of citation, reduce ambiguity in content, strengthen topical authority signals, and structure information for machine consumption.',
            },
        },
    ],
};
</script>

<template>
    <Head title="What Is Generative Engine Optimization (GEO)? - GeoSource.ai">
        <meta name="description" content="Generative Engine Optimization (GEO) is the practice of optimizing digital content so it can be accurately understood, trusted, and cited by generative AI systems like ChatGPT, Perplexity, and Claude." />
        <meta property="og:title" content="What Is Generative Engine Optimization (GEO)?" />
        <meta property="og:description" content="Learn the definition, core principles, and goals of GEO — the practice of optimizing content for AI systems." />
        <meta property="og:type" content="article" />
        <meta property="og:url" content="https://geosource.ai/resources/what-is-geo" />
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" content="What Is Generative Engine Optimization (GEO)?" />
        <meta name="twitter:description" content="Learn the definition, core principles, and goals of GEO — the practice of optimizing content for AI systems." />
        <link rel="canonical" href="https://geosource.ai/resources/what-is-geo" />
        <component :is="'script'" type="application/ld+json">{{ JSON.stringify(jsonLd) }}</component>
        <component :is="'script'" type="application/ld+json">{{ JSON.stringify(faqJsonLd) }}</component>
    </Head>

    <div class="min-h-screen bg-background text-foreground">
        <!-- Navigation -->
        <header class="sticky top-0 z-50 border-b bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60">
            <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                <Link href="/" class="flex items-center gap-2">
                    <Globe class="h-8 w-8 text-primary" />
                    <span class="text-xl font-bold">GeoSource.ai</span>
                </Link>
                <nav class="flex items-center gap-2">
                    <Link href="/resources">
                        <Button variant="ghost">Resources</Button>
                    </Link>
                    <Link v-if="$page.props.auth.user" href="/dashboard">
                        <Button variant="outline">Dashboard</Button>
                    </Link>
                    <template v-else>
                        <Link href="/login">
                            <Button variant="ghost">Log in</Button>
                        </Link>
                        <Link href="/register">
                            <Button>Get Started</Button>
                        </Link>
                    </template>
                    <ThemeSwitcher />
                </nav>
            </div>
        </header>

        <main>
            <!-- Breadcrumb -->
            <div class="border-b bg-muted/30">
                <div class="mx-auto max-w-4xl px-4 py-4 sm:px-6 lg:px-8">
                    <nav class="flex items-center gap-2 text-sm text-muted-foreground">
                        <Link href="/resources" class="hover:text-foreground">Resources</Link>
                        <span>/</span>
                        <span class="text-foreground">What Is GEO?</span>
                    </nav>
                </div>
            </div>

            <!-- Article -->
            <article class="py-12">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <!-- Header -->
                    <header class="mb-12">
                        <Badge variant="secondary" class="mb-4">
                            <BookOpen class="mr-1 h-3 w-3" />
                            Foundation
                        </Badge>
                        <h1 class="text-3xl font-bold tracking-tight sm:text-4xl lg:text-5xl">
                            What Is Generative Engine Optimization (GEO)?
                        </h1>
                        <p class="mt-4 text-lg text-muted-foreground">
                            The complete guide to understanding GEO and why it matters for AI visibility.
                        </p>
                    </header>

                    <!-- Definition Section -->
                    <section class="mb-12" aria-labelledby="definition">
                        <h2 id="definition" class="text-2xl font-bold mb-6">Definition</h2>
                        <Card class="border-primary/50 bg-primary/5">
                            <CardContent class="pt-6">
                                <p class="text-lg leading-relaxed">
                                    <dfn id="geo-definition" class="font-semibold not-italic"><strong>Generative Engine Optimization (GEO)</strong></dfn> is the practice of optimizing digital content so it can be accurately understood, trusted, and cited by generative AI systems such as ChatGPT, Google AI Overviews, Perplexity, Claude, and other large language models (LLMs).
                                </p>
                            </CardContent>
                        </Card>
                        <div class="mt-6 space-y-4 text-muted-foreground">
                            <p>
                                Unlike traditional search engines, generative engines do not rank web pages by backlinks or keyword density. Instead, they <strong class="text-foreground">synthesize answers</strong> by selecting high-confidence sources based on clarity, structure, topical authority, and factual consistency.
                            </p>
                            <p class="text-lg font-medium text-foreground">
                                GEO focuses on making content AI-readable and citation-ready.
                            </p>
                        </div>
                    </section>

                    <Separator class="my-12" />

                    <!-- Why GEO Exists Section -->
                    <section class="mb-12" aria-labelledby="why-geo-exists">
                        <h2 id="why-geo-exists" class="text-2xl font-bold mb-6">Why GEO Exists</h2>
                        <p class="text-muted-foreground mb-6">
                            Generative search engines operate differently than Google:
                        </p>

                        <!-- Comparison Table -->
                        <div class="overflow-x-auto">
                            <table class="w-full border-collapse">
                                <thead>
                                    <tr class="border-b">
                                        <th class="py-3 px-4 text-left font-semibold">Aspect</th>
                                        <th class="py-3 px-4 text-left font-semibold">Traditional SEO</th>
                                        <th class="py-3 px-4 text-left font-semibold text-primary">Generative AI</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="row in comparisonData" :key="row.aspect" class="border-b">
                                        <td class="py-3 px-4 text-muted-foreground">{{ row.aspect }}</td>
                                        <td class="py-3 px-4">{{ row.seo }}</td>
                                        <td class="py-3 px-4 text-primary font-medium">{{ row.geo }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <Card class="mt-8 border-amber-500/50 bg-amber-500/5">
                            <CardContent class="pt-6">
                                <div class="flex items-start gap-3">
                                    <Lightbulb class="h-6 w-6 text-amber-500 shrink-0 mt-0.5" />
                                    <div>
                                        <p class="font-medium text-foreground">Key Insight</p>
                                        <p class="text-muted-foreground mt-1">
                                            Many high-ranking SEO pages are never cited by AI, while smaller sites with clearer structure often are. <strong class="text-foreground">GEO exists to solve this gap.</strong>
                                        </p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </section>

                    <Separator class="my-12" />

                    <!-- Core Goals Section -->
                    <section class="mb-12" aria-labelledby="core-goals">
                        <h2 id="core-goals" class="text-2xl font-bold mb-6">Core Goals of GEO</h2>
                        <ol class="space-y-4" role="list">
                            <li v-for="(goal, index) in coreGoals" :key="goal" class="flex items-start gap-3">
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-primary text-primary-foreground text-sm font-bold">
                                    {{ index + 1 }}
                                </span>
                                <span class="text-lg pt-0.5">{{ goal }}</span>
                            </li>
                        </ol>
                    </section>

                    <Separator class="my-12" />

                    <!-- GEO in One Sentence -->
                    <section class="mb-12" aria-labelledby="summary">
                        <h2 id="summary" class="text-2xl font-bold mb-6">GEO in One Sentence</h2>
                        <Card class="border-primary bg-primary/5">
                            <CardContent class="pt-6">
                                <p class="text-xl font-medium text-center">
                                    GEO helps AI systems understand what your site is about — and trust it enough to cite it.
                                </p>
                            </CardContent>
                        </Card>
                    </section>

                    <Separator class="my-12" />

                    <!-- Related Resources -->
                    <section aria-labelledby="related">
                        <h2 id="related" class="text-2xl font-bold mb-6">Related Resources</h2>
                        <div class="grid gap-4 sm:grid-cols-3">
                            <Link
                                v-for="article in relatedArticles"
                                :key="article.slug"
                                :href="`/resources/${article.slug}`"
                                class="group"
                            >
                                <Card class="h-full transition-colors hover:border-primary/50">
                                    <CardContent class="pt-6">
                                        <p class="font-medium group-hover:text-primary transition-colors">
                                            {{ article.title }}
                                        </p>
                                        <span class="inline-flex items-center text-sm text-primary mt-2">
                                            Read more
                                            <ArrowRight class="ml-1 h-3 w-3 transition-transform group-hover:translate-x-1" />
                                        </span>
                                    </CardContent>
                                </Card>
                            </Link>
                        </div>
                    </section>

                    <!-- Navigation -->
                    <div class="mt-12 flex items-center justify-between border-t pt-8">
                        <Link href="/resources" class="inline-flex items-center text-muted-foreground hover:text-foreground">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Back to Resources
                        </Link>
                        <Link href="/resources/geo-vs-seo" class="inline-flex items-center text-primary hover:underline">
                            Next: GEO vs SEO
                            <ArrowRight class="ml-2 h-4 w-4" />
                        </Link>
                    </div>
                </div>
            </article>

            <!-- CTA Section -->
            <section class="border-t bg-muted/30 py-12">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 text-center">
                    <h2 class="text-2xl font-bold">Ready to optimize for AI?</h2>
                    <p class="mt-2 text-muted-foreground">Get your GEO Score and start improving your AI visibility.</p>
                    <div class="mt-6">
                        <Link href="/register">
                            <Button size="lg" class="gap-2">
                                Get Your GEO Score
                                <ArrowRight class="h-4 w-4" />
                            </Button>
                        </Link>
                    </div>
                </div>
            </section>
        </main>

        <!-- Footer -->
        <footer class="border-t py-12">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col items-center justify-between gap-4 sm:flex-row">
                    <div class="flex items-center gap-2">
                        <Globe class="h-6 w-6 text-primary" />
                        <span class="font-semibold">GeoSource.ai</span>
                    </div>
                    <p class="text-sm text-muted-foreground">
                        &copy; {{ new Date().getFullYear() }} GeoSource.ai. All rights reserved.
                    </p>
                </div>
            </div>
        </footer>
    </div>
</template>
