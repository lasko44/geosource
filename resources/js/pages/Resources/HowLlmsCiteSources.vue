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
    Quote,
    CheckCircle,
    XCircle,
    Building2,
    User,
    Lightbulb,
    Target,
    Menu,
} from 'lucide-vue-next';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import ThemeSwitcher from '@/components/ThemeSwitcher.vue';

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
                    <Link href="/pricing">
                        <Button variant="ghost">Pricing</Button>
                    </Link>
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
                                <Link href="/pricing" class="w-full">
                                    Pricing
                                </Link>
                            </DropdownMenuItem>
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
                        <span class="text-foreground">How LLMs Cite Sources</span>
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
                            Deep Dive
                        </Badge>
                        <h1 class="text-3xl font-bold tracking-tight sm:text-4xl lg:text-5xl">
                            How Large Language Models Choose Which Sources to Cite
                        </h1>
                        <p class="mt-4 text-lg text-muted-foreground">
                            Understanding the citation selection process of AI systems.
                        </p>
                    </header>

                    <!-- Key Point -->
                    <section class="mb-12" aria-labelledby="key-point">
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
                                <CheckCircle class="h-5 w-5 text-green-500 shrink-0 mt-0.5" />
                                <div>
                                    <p class="font-medium">{{ item.signal }}</p>
                                    <p class="text-sm text-muted-foreground">{{ item.description }}</p>
                                </div>
                            </div>
                        </div>

                        <Card class="mt-8 border-amber-500/50 bg-amber-500/5">
                            <CardContent class="pt-6">
                                <div class="flex items-start gap-3">
                                    <Lightbulb class="h-6 w-6 text-amber-500 shrink-0 mt-0.5" />
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
                                        <Building2 class="h-5 w-5" />
                                        <CardTitle class="text-lg">Large Brands Often Publish</CardTitle>
                                    </div>
                                </CardHeader>
                                <CardContent>
                                    <ul class="space-y-2">
                                        <li v-for="item in bigBrandProblems" :key="item" class="flex items-center gap-2">
                                            <XCircle class="h-4 w-4 text-destructive shrink-0" />
                                            <span class="text-muted-foreground">{{ item }}</span>
                                        </li>
                                    </ul>
                                </CardContent>
                            </Card>

                            <Card class="border-green-500/50 bg-green-500/5">
                                <CardHeader>
                                    <div class="flex items-center gap-2">
                                        <User class="h-5 w-5 text-green-500" />
                                        <CardTitle class="text-lg">Smaller Sites Often Publish</CardTitle>
                                    </div>
                                </CardHeader>
                                <CardContent>
                                    <ul class="space-y-2">
                                        <li v-for="item in smallSiteAdvantages" :key="item" class="flex items-center gap-2">
                                            <CheckCircle class="h-4 w-4 text-green-500 shrink-0" />
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
                                    <Target class="h-8 w-8 text-primary shrink-0" />
                                    <div>
                                        <p class="text-lg">
                                            To earn AI citations, focus on:
                                        </p>
                                        <ul class="mt-4 space-y-2">
                                            <li class="flex items-center gap-2">
                                                <CheckCircle class="h-5 w-5 text-primary" />
                                                <span class="font-medium">Clear, direct definitions</span>
                                            </li>
                                            <li class="flex items-center gap-2">
                                                <CheckCircle class="h-5 w-5 text-primary" />
                                                <span class="font-medium">Structured, scannable content</span>
                                            </li>
                                            <li class="flex items-center gap-2">
                                                <CheckCircle class="h-5 w-5 text-primary" />
                                                <span class="font-medium">Consistent terminology</span>
                                            </li>
                                            <li class="flex items-center gap-2">
                                                <CheckCircle class="h-5 w-5 text-primary" />
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
                        <Link href="/resources/how-ai-search-works" class="inline-flex items-center text-muted-foreground hover:text-foreground">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Previous: How AI Search Works
                        </Link>
                        <Link href="/resources/what-is-a-geo-score" class="inline-flex items-center text-primary hover:underline">
                            Next: What Is a GEO Score?
                            <ArrowRight class="ml-2 h-4 w-4" />
                        </Link>
                    </div>
                </div>
            </article>

            <!-- CTA Section -->
            <section class="border-t bg-muted/30 py-12">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 text-center">
                    <h2 class="text-2xl font-bold">Make your content citation-ready</h2>
                    <p class="mt-2 text-muted-foreground">Get your GEO Score and see how likely AI systems are to cite your content.</p>
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
