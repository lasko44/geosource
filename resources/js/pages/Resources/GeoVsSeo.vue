<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import {
    Globe,
    BookOpen,
    ArrowRight,
    ArrowLeft,
    Search,
    Brain,
    CheckCircle,
    XCircle,
    Lightbulb,
    Menu,
} from 'lucide-vue-next';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import ThemeSwitcher from '@/components/ThemeSwitcher.vue';

const keyDifferences = [
    { area: 'Output', seo: 'Ranked links', geo: 'Generated answers' },
    { area: 'Success metric', seo: 'Clicks', geo: 'Citations' },
    { area: 'Optimization', seo: 'Keywords', geo: 'Semantic clarity' },
    { area: 'Structure', seo: 'HTML', geo: 'Knowledge structure' },
    { area: 'Authority', seo: 'Backlinks', geo: 'Consistency + trust' },
];

const llmEvaluates = [
    'Definition quality',
    'Entity clarity',
    'Topic hierarchy',
    'Redundancy across sources',
    'Confidence in factual statements',
];

const relatedArticles = [
    { slug: 'what-is-geo', title: 'What Is GEO?' },
    { slug: 'how-ai-search-works', title: 'How AI Search Works' },
    { slug: 'how-llms-cite-sources', title: 'How LLMs Cite Sources' },
];

const jsonLd = {
    '@context': 'https://schema.org',
    '@type': 'Article',
    headline: 'GEO vs SEO: What\'s the Difference?',
    description: 'Understand the key differences between traditional SEO and Generative Engine Optimization. Learn why SEO alone is no longer enough.',
    url: 'https://geosource.ai/resources/geo-vs-seo',
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
        '@id': 'https://geosource.ai/resources/geo-vs-seo',
    },
};

const faqJsonLd = {
    '@context': 'https://schema.org',
    '@type': 'FAQPage',
    mainEntity: [
        {
            '@type': 'Question',
            name: 'What is the difference between GEO and SEO?',
            acceptedAnswer: {
                '@type': 'Answer',
                text: 'SEO optimizes for ranking in search results (links), while GEO optimizes for understanding by AI systems (answers). SEO measures clicks; GEO measures citations. SEO focuses on keywords; GEO focuses on semantic clarity.',
            },
        },
        {
            '@type': 'Question',
            name: 'Why is SEO alone no longer enough?',
            acceptedAnswer: {
                '@type': 'Answer',
                text: 'A page can rank #1 on Google yet never appear in ChatGPT answers. LLMs evaluate definition quality, entity clarity, topic hierarchy, redundancy across sources, and confidence in factual statements — factors that SEO does not measure.',
            },
        },
        {
            '@type': 'Question',
            name: 'Does GEO replace SEO?',
            acceptedAnswer: {
                '@type': 'Answer',
                text: 'No, GEO does not replace SEO. It complements it. SEO brings traffic from traditional search. GEO brings visibility inside AI systems. Sites that win long-term will optimize for both humans and machines.',
            },
        },
    ],
};
</script>

<template>
    <Head title="GEO vs SEO: What's the Difference? - GeoSource.ai">
        <meta name="description" content="Understand the key differences between traditional SEO and Generative Engine Optimization. Learn why SEO alone is no longer enough for AI visibility." />
        <meta property="og:title" content="GEO vs SEO: What's the Difference?" />
        <meta property="og:description" content="Understand the key differences between traditional SEO and Generative Engine Optimization." />
        <meta property="og:type" content="article" />
        <meta property="og:url" content="https://geosource.ai/resources/geo-vs-seo" />
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" content="GEO vs SEO: What's the Difference?" />
        <meta name="twitter:description" content="Understand the key differences between traditional SEO and Generative Engine Optimization." />
        <link rel="canonical" href="https://geosource.ai/resources/geo-vs-seo" />
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
                <!-- Desktop Navigation -->
                <nav class="hidden items-center gap-2 sm:flex">
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

                <!-- Mobile Navigation -->
                <div class="flex items-center gap-2 sm:hidden">
                    <ThemeSwitcher />
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button variant="ghost" size="icon">
                                <Menu class="h-5 w-5" />
                                <span class="sr-only">Open menu</span>
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="w-48">
                            <DropdownMenuItem as-child>
                                <Link href="/resources" class="w-full">
                                    Resources
                                </Link>
                            </DropdownMenuItem>
                            <DropdownMenuItem v-if="$page.props.auth.user" as-child>
                                <Link href="/dashboard" class="w-full">
                                    Dashboard
                                </Link>
                            </DropdownMenuItem>
                            <template v-else>
                                <DropdownMenuItem as-child>
                                    <Link href="/login" class="w-full">
                                        Log in
                                    </Link>
                                </DropdownMenuItem>
                                <DropdownMenuItem as-child>
                                    <Link href="/register" class="w-full font-medium text-primary">
                                        Get Started
                                    </Link>
                                </DropdownMenuItem>
                            </template>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </div>
        </header>

        <main>
            <!-- Breadcrumb -->
            <div class="border-b bg-muted/30">
                <div class="mx-auto max-w-4xl px-4 py-4 sm:px-6 lg:px-8">
                    <nav class="flex items-center gap-2 text-sm text-muted-foreground">
                        <Link href="/resources" class="hover:text-foreground">Resources</Link>
                        <span>/</span>
                        <span class="text-foreground">GEO vs SEO</span>
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
                            Comparison
                        </Badge>
                        <h1 class="text-3xl font-bold tracking-tight sm:text-4xl lg:text-5xl">
                            GEO vs SEO: What's the Difference?
                        </h1>
                        <p class="mt-4 text-lg text-muted-foreground">
                            Understanding why traditional search optimization is no longer enough.
                        </p>
                    </header>

                    <!-- Core Difference -->
                    <section class="mb-12" aria-labelledby="core-difference">
                        <div class="grid gap-6 sm:grid-cols-2">
                            <Card class="border-muted">
                                <CardHeader>
                                    <div class="flex items-center gap-2">
                                        <Search class="h-6 w-6" />
                                        <CardTitle>SEO</CardTitle>
                                    </div>
                                </CardHeader>
                                <CardContent>
                                    <p class="text-xl font-medium">Optimizes for Ranking</p>
                                    <p class="mt-2 text-muted-foreground">
                                        Built for search engines that return lists of links.
                                    </p>
                                </CardContent>
                            </Card>
                            <Card class="border-primary/50 bg-primary/5">
                                <CardHeader>
                                    <div class="flex items-center gap-2">
                                        <Brain class="h-6 w-6 text-primary" />
                                        <CardTitle class="text-primary">GEO</CardTitle>
                                    </div>
                                </CardHeader>
                                <CardContent>
                                    <p class="text-xl font-medium">Optimizes for Understanding</p>
                                    <p class="mt-2 text-muted-foreground">
                                        Built for AI systems that return final answers.
                                    </p>
                                </CardContent>
                            </Card>
                        </div>
                        <p class="mt-6 text-center text-lg font-medium text-foreground">
                            This fundamental difference changes everything.
                        </p>
                    </section>

                    <Separator class="my-12" />

                    <!-- Key Differences Table -->
                    <section class="mb-12" aria-labelledby="key-differences">
                        <h2 id="key-differences" class="text-2xl font-bold mb-6">Key Differences</h2>
                        <div class="overflow-x-auto">
                            <table class="w-full border-collapse">
                                <thead>
                                    <tr class="border-b">
                                        <th class="py-3 px-4 text-left font-semibold">Area</th>
                                        <th class="py-3 px-4 text-left font-semibold">SEO</th>
                                        <th class="py-3 px-4 text-left font-semibold text-primary">GEO</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="row in keyDifferences" :key="row.area" class="border-b">
                                        <td class="py-3 px-4 font-medium">{{ row.area }}</td>
                                        <td class="py-3 px-4 text-muted-foreground">{{ row.seo }}</td>
                                        <td class="py-3 px-4 text-primary font-medium">{{ row.geo }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <Separator class="my-12" />

                    <!-- Why SEO Alone Is Not Enough -->
                    <section class="mb-12" aria-labelledby="seo-not-enough">
                        <h2 id="seo-not-enough" class="text-2xl font-bold mb-6">Why SEO Alone Is No Longer Enough</h2>

                        <Card class="mb-6 border-amber-500/50 bg-amber-500/5">
                            <CardContent class="pt-6">
                                <div class="flex items-start gap-3">
                                    <Lightbulb class="h-6 w-6 text-amber-500 shrink-0 mt-0.5" />
                                    <div>
                                        <p class="font-medium text-foreground">A page can:</p>
                                        <ul class="mt-2 space-y-1 text-muted-foreground">
                                            <li class="flex items-center gap-2">
                                                <CheckCircle class="h-4 w-4 text-green-500" />
                                                <span>Rank #1 on Google</span>
                                            </li>
                                            <li class="flex items-center gap-2">
                                                <XCircle class="h-4 w-4 text-destructive" />
                                                <span>Yet never appear in ChatGPT answers</span>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <p class="text-muted-foreground mb-4">Because LLMs evaluate:</p>
                        <ul class="space-y-3">
                            <li v-for="item in llmEvaluates" :key="item" class="flex items-start gap-3">
                                <CheckCircle class="h-5 w-5 text-primary shrink-0 mt-0.5" />
                                <span>{{ item }}</span>
                            </li>
                        </ul>

                        <Card class="mt-8">
                            <CardContent class="pt-6 text-center">
                                <p class="text-lg text-muted-foreground">SEO does not measure these factors.</p>
                                <p class="text-2xl font-bold text-primary mt-2">GEO does.</p>
                            </CardContent>
                        </Card>
                    </section>

                    <Separator class="my-12" />

                    <!-- The Future -->
                    <section class="mb-12" aria-labelledby="future">
                        <h2 id="future" class="text-2xl font-bold mb-6">The Future: SEO + GEO Together</h2>

                        <Card class="border-primary/50 bg-primary/5">
                            <CardContent class="pt-6">
                                <p class="text-center text-lg">
                                    <strong class="text-primary">GEO does not replace SEO.</strong>
                                </p>
                                <p class="text-center text-lg mt-2">
                                    It <strong>complements</strong> it.
                                </p>
                            </CardContent>
                        </Card>

                        <div class="mt-8 grid gap-6 sm:grid-cols-2">
                            <Card>
                                <CardContent class="pt-6">
                                    <div class="flex items-center gap-2 mb-2">
                                        <Search class="h-5 w-5" />
                                        <span class="font-medium">SEO</span>
                                    </div>
                                    <p class="text-muted-foreground">Brings traffic</p>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardContent class="pt-6">
                                    <div class="flex items-center gap-2 mb-2">
                                        <Brain class="h-5 w-5 text-primary" />
                                        <span class="font-medium text-primary">GEO</span>
                                    </div>
                                    <p class="text-muted-foreground">Brings visibility inside AI systems</p>
                                </CardContent>
                            </Card>
                        </div>

                        <p class="mt-8 text-center text-lg font-medium">
                            Sites that win long-term will optimize for <strong class="text-primary">both humans and machines</strong>.
                        </p>
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
                        <Link href="/resources/what-is-geo" class="inline-flex items-center text-muted-foreground hover:text-foreground">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Previous: What Is GEO?
                        </Link>
                        <Link href="/resources/how-ai-search-works" class="inline-flex items-center text-primary hover:underline">
                            Next: How AI Search Works
                            <ArrowRight class="ml-2 h-4 w-4" />
                        </Link>
                    </div>
                </div>
            </article>

            <!-- CTA Section -->
            <section class="border-t bg-muted/30 py-12">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 text-center">
                    <h2 class="text-2xl font-bold">Ready to optimize for both SEO and GEO?</h2>
                    <p class="mt-2 text-muted-foreground">Get your GEO Score and discover how AI systems see your content.</p>
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
