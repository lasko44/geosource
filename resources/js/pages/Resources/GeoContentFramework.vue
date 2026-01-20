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
    Database,
    Target,
    FileText,
    HelpCircle,
    CheckCircle,
    Layers,
    Brain,
    Menu,
} from 'lucide-vue-next';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import ThemeSwitcher from '@/components/ThemeSwitcher.vue';

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
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" content="The GeoSource.ai GEO Content Framework" />
        <meta name="twitter:description" content="A structured framework designed specifically for generative AI systems." />
        <link rel="canonical" href="https://geosource.ai/resources/geo-content-framework" />
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
                        <span class="text-foreground">GEO Content Framework</span>
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
                            Framework
                        </Badge>
                        <h1 class="text-3xl font-bold tracking-tight sm:text-4xl lg:text-5xl">
                            The GeoSource.ai GEO Content Framework
                        </h1>
                        <p class="mt-4 text-lg text-muted-foreground">
                            A structured approach designed specifically for generative AI systems.
                        </p>
                    </header>

                    <!-- Introduction -->
                    <section class="mb-12" aria-labelledby="intro">
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
                                        <component :is="pillar.icon" class="h-6 w-6 text-primary" />
                                        <CardTitle>{{ pillar.title }}</CardTitle>
                                    </div>
                                </CardHeader>
                                <CardContent class="pt-6">
                                    <ul class="space-y-2">
                                        <li v-for="item in pillar.items" :key="item" class="flex items-center gap-2">
                                            <CheckCircle class="h-4 w-4 text-green-500 shrink-0" />
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
                                    <Brain class="h-8 w-8 text-primary shrink-0" />
                                    <div>
                                        <p class="text-lg font-medium">
                                            This framework mirrors how LLMs retrieve and evaluate information internally.
                                        </p>
                                        <p class="mt-4 text-muted-foreground">
                                            By structuring your content according to these four pillars, you:
                                        </p>
                                        <ul class="mt-4 space-y-2">
                                            <li class="flex items-center gap-2">
                                                <CheckCircle class="h-5 w-5 text-green-500" />
                                                <span>Make your content easier for AI to parse</span>
                                            </li>
                                            <li class="flex items-center gap-2">
                                                <CheckCircle class="h-5 w-5 text-green-500" />
                                                <span>Increase trust signals that AI systems recognize</span>
                                            </li>
                                            <li class="flex items-center gap-2">
                                                <CheckCircle class="h-5 w-5 text-green-500" />
                                                <span>Reduce the risk of being misunderstood or ignored</span>
                                            </li>
                                            <li class="flex items-center gap-2">
                                                <CheckCircle class="h-5 w-5 text-green-500" />
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
                                            <CheckCircle class="h-4 w-4 text-muted-foreground shrink-0 mt-0.5" />
                                            <span>Start with a clear definition</span>
                                        </li>
                                        <li class="flex items-start gap-2">
                                            <CheckCircle class="h-4 w-4 text-muted-foreground shrink-0 mt-0.5" />
                                            <span>Use hierarchical headings (H1 → H2 → H3)</span>
                                        </li>
                                        <li class="flex items-start gap-2">
                                            <CheckCircle class="h-4 w-4 text-muted-foreground shrink-0 mt-0.5" />
                                            <span>Include bullet points for lists</span>
                                        </li>
                                        <li class="flex items-start gap-2">
                                            <CheckCircle class="h-4 w-4 text-muted-foreground shrink-0 mt-0.5" />
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
                                            <CheckCircle class="h-4 w-4 text-muted-foreground shrink-0 mt-0.5" />
                                            <span>Stay focused on one topic per page</span>
                                        </li>
                                        <li class="flex items-start gap-2">
                                            <CheckCircle class="h-4 w-4 text-muted-foreground shrink-0 mt-0.5" />
                                            <span>Cover the topic comprehensively</span>
                                        </li>
                                        <li class="flex items-start gap-2">
                                            <CheckCircle class="h-4 w-4 text-muted-foreground shrink-0 mt-0.5" />
                                            <span>Link to related topics internally</span>
                                        </li>
                                        <li class="flex items-start gap-2">
                                            <CheckCircle class="h-4 w-4 text-muted-foreground shrink-0 mt-0.5" />
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
                                            <CheckCircle class="h-4 w-4 text-muted-foreground shrink-0 mt-0.5" />
                                            <span>Include an FAQ section</span>
                                        </li>
                                        <li class="flex items-start gap-2">
                                            <CheckCircle class="h-4 w-4 text-muted-foreground shrink-0 mt-0.5" />
                                            <span>Answer questions directly</span>
                                        </li>
                                        <li class="flex items-start gap-2">
                                            <CheckCircle class="h-4 w-4 text-muted-foreground shrink-0 mt-0.5" />
                                            <span>Use "X is defined as..." patterns</span>
                                        </li>
                                        <li class="flex items-start gap-2">
                                            <CheckCircle class="h-4 w-4 text-muted-foreground shrink-0 mt-0.5" />
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
                                            <CheckCircle class="h-4 w-4 text-muted-foreground shrink-0 mt-0.5" />
                                            <span>Add JSON-LD structured data</span>
                                        </li>
                                        <li class="flex items-start gap-2">
                                            <CheckCircle class="h-4 w-4 text-muted-foreground shrink-0 mt-0.5" />
                                            <span>Use semantic HTML tags</span>
                                        </li>
                                        <li class="flex items-start gap-2">
                                            <CheckCircle class="h-4 w-4 text-muted-foreground shrink-0 mt-0.5" />
                                            <span>Implement proper meta tags</span>
                                        </li>
                                        <li class="flex items-start gap-2">
                                            <CheckCircle class="h-4 w-4 text-muted-foreground shrink-0 mt-0.5" />
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
                        <Link href="/resources/what-is-a-geo-score" class="inline-flex items-center text-muted-foreground hover:text-foreground">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Previous: What Is a GEO Score?
                        </Link>
                        <Link href="/resources" class="inline-flex items-center text-primary hover:underline">
                            View All Resources
                            <ArrowRight class="ml-2 h-4 w-4" />
                        </Link>
                    </div>
                </div>
            </article>

            <!-- CTA Section -->
            <section class="border-t bg-muted/30 py-12">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 text-center">
                    <h2 class="text-2xl font-bold">Put the framework into practice</h2>
                    <p class="mt-2 text-muted-foreground">Get your GEO Score and see how your content measures up against the framework.</p>
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
                <div class="flex flex-col items-center gap-6">
                    <div class="flex items-center gap-2 rounded-lg bg-primary/10 px-4 py-2">
                        <Mail class="h-5 w-5 text-primary" />
                        <span class="text-sm font-medium">Need help?</span>
                        <a href="mailto:support@geosource.ai" class="text-sm font-semibold text-primary hover:underline">
                            support@geosource.ai
                        </a>
                    </div>
                    <div class="flex w-full flex-col items-center justify-between gap-4 sm:flex-row">
                        <div class="flex items-center gap-2">
                            <Globe class="h-6 w-6 text-primary" />
                            <span class="font-semibold">GeoSource.ai</span>
                        </div>
                        <p class="text-sm text-muted-foreground">
                            &copy; {{ new Date().getFullYear() }} GeoSource.ai. All rights reserved.
                        </p>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>
