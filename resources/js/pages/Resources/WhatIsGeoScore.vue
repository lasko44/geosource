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
    BarChart3,
    CheckCircle,
    Search,
    Brain,
    FileText,
    HelpCircle,
    Target,
    Lightbulb,
    Menu,
    Calendar,
    Mail,
} from 'lucide-vue-next';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import ThemeSwitcher from '@/components/ThemeSwitcher.vue';

const publishedDate = new Date('2026-01-18').toLocaleDateString('en-US', { month: 'long', year: 'numeric' });

const scoreEvaluates = [
    'Structured knowledge presence',
    'Definition quality',
    'Topic hierarchy',
    'Entity clarity',
    'FAQ coverage',
    'Machine-readable formatting',
    'Internal semantic consistency',
];

const geosourceAnalyzes = [
    { question: 'Can an AI clearly explain this topic?', icon: Brain },
    { question: 'Are definitions explicit?', icon: FileText },
    { question: 'Are concepts nested correctly?', icon: Target },
    { question: 'Is the topic authoritative or fragmented?', icon: Search },
];

const relatedArticles = [
    { slug: 'what-is-geo', title: 'What Is GEO?' },
    { slug: 'geo-vs-seo', title: 'GEO vs SEO' },
    { slug: 'geo-content-framework', title: 'GEO Content Framework' },
];

const jsonLd = {
    '@context': 'https://schema.org',
    '@type': 'Article',
    headline: 'What Is a GEO Score?',
    description: 'A GEO Score is a measurable evaluation of how well a webpage or domain is optimized for generative AI understanding and citation.',
    url: 'https://geosource.ai/resources/what-is-a-geo-score',
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
        '@id': 'https://geosource.ai/resources/what-is-a-geo-score',
    },
    about: {
        '@type': 'DefinedTerm',
        name: 'GEO Score',
        description: 'A measurable evaluation of how well a webpage or domain is optimized for generative AI understanding and citation.',
    },
};

const faqJsonLd = {
    '@context': 'https://schema.org',
    '@type': 'FAQPage',
    mainEntity: [
        {
            '@type': 'Question',
            name: 'What is a GEO Score?',
            acceptedAnswer: {
                '@type': 'Answer',
                text: 'A GEO Score is a measurable evaluation of how well a webpage or domain is optimized for generative AI understanding and citation. Unlike SEO scores, a GEO Score does not measure rankings or backlinks — it measures AI comprehension readiness.',
            },
        },
        {
            '@type': 'Question',
            name: 'What does a GEO Score evaluate?',
            acceptedAnswer: {
                '@type': 'Answer',
                text: 'A GEO Score evaluates structured knowledge presence, definition quality, topic hierarchy, entity clarity, FAQ coverage, machine-readable formatting, and internal semantic consistency.',
            },
        },
        {
            '@type': 'Question',
            name: 'How is GeoSource.ai\'s GEO Score different?',
            acceptedAnswer: {
                '@type': 'Answer',
                text: 'GeoSource.ai does not simply track mentions. It analyzes why content would or would not be cited. The platform evaluates whether an AI can clearly explain the topic, if definitions are explicit, if concepts are nested correctly, and if the topic is authoritative or fragmented.',
            },
        },
    ],
};
</script>

<template>
    <Head title="What Is a GEO Score? - GeoSource.ai">
        <meta name="description" content="A GEO Score is a measurable evaluation of how well a webpage or domain is optimized for generative AI understanding and citation. Learn what it measures and why it matters." />
        <meta property="og:title" content="What Is a GEO Score?" />
        <meta property="og:description" content="A GEO Score measures AI comprehension readiness — not rankings or backlinks." />
        <meta property="og:type" content="article" />
        <meta property="og:url" content="https://geosource.ai/resources/what-is-a-geo-score" />
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" content="What Is a GEO Score?" />
        <meta name="twitter:description" content="A GEO Score measures AI comprehension readiness — not rankings or backlinks." />
        <link rel="canonical" href="https://geosource.ai/resources/what-is-a-geo-score" />
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
                        <span class="text-foreground">What Is a GEO Score?</span>
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
                            Metrics
                        </Badge>
                        <h1 class="text-3xl font-bold tracking-tight sm:text-4xl lg:text-5xl">
                            What Is a GEO Score?
                        </h1>
                        <p class="mt-4 text-lg text-muted-foreground">
                            Understanding the metric that measures AI comprehension readiness.
                        </p>
                        <div class="mt-4 flex items-center gap-2 text-sm text-muted-foreground">
                            <Calendar class="h-4 w-4" />
                            <span>{{ publishedDate }}</span>
                        </div>
                    </header>

                    <!-- Definition -->
                    <section class="mb-12" aria-labelledby="definition">
                        <h2 id="definition" class="text-2xl font-bold mb-6">Definition</h2>
                        <Card class="border-primary/50 bg-primary/5">
                            <CardContent class="pt-6">
                                <p class="text-lg leading-relaxed">
                                    A <dfn id="geo-score-definition" class="font-semibold not-italic"><strong>GEO Score</strong></dfn> is a measurable evaluation of how well a webpage or domain is optimized for generative AI understanding and citation.
                                </p>
                            </CardContent>
                        </Card>

                        <Card class="mt-6 border-amber-500/50 bg-amber-500/5">
                            <CardContent class="pt-6">
                                <div class="flex items-start gap-3">
                                    <Lightbulb class="h-6 w-6 text-amber-500 shrink-0 mt-0.5" />
                                    <div>
                                        <p class="font-medium text-foreground">Key Distinction</p>
                                        <p class="text-muted-foreground mt-1">
                                            Unlike SEO scores, a GEO Score does not measure rankings or backlinks. It measures <strong class="text-foreground">AI comprehension readiness</strong>.
                                        </p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </section>

                    <Separator class="my-12" />

                    <!-- What It Evaluates -->
                    <section class="mb-12" aria-labelledby="evaluates">
                        <h2 id="evaluates" class="text-2xl font-bold mb-6">What a GEO Score Evaluates</h2>
                        <p class="text-muted-foreground mb-6">
                            A proper GEO Score typically includes:
                        </p>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div v-for="item in scoreEvaluates" :key="item" class="flex items-center gap-3 p-3 rounded-lg border bg-card">
                                <CheckCircle class="h-5 w-5 text-green-500 shrink-0" />
                                <span>{{ item }}</span>
                            </div>
                        </div>
                    </section>

                    <Separator class="my-12" />

                    <!-- Why GeoSource.ai Is Different -->
                    <section class="mb-12" aria-labelledby="different">
                        <h2 id="different" class="text-2xl font-bold mb-6">Why GeoSource.ai's GEO Score Is Different</h2>

                        <Card class="mb-6">
                            <CardContent class="pt-6">
                                <p class="text-lg">
                                    GeoSource.ai does not simply track mentions.
                                </p>
                                <p class="text-xl font-bold text-primary mt-2">
                                    It analyzes <em>why</em> content would or would not be cited.
                                </p>
                            </CardContent>
                        </Card>

                        <p class="text-muted-foreground mb-6">The platform evaluates:</p>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <Card v-for="item in geosourceAnalyzes" :key="item.question">
                                <CardContent class="pt-6">
                                    <div class="flex items-start gap-3">
                                        <component :is="item.icon" class="h-5 w-5 text-primary shrink-0 mt-0.5" />
                                        <p class="font-medium">{{ item.question }}</p>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>

                        <Card class="mt-8 border-primary/50 bg-primary/5">
                            <CardContent class="pt-6 text-center">
                                <p class="text-lg">
                                    This allows site owners to <strong class="text-primary">fix problems before AI visibility is lost</strong>.
                                </p>
                            </CardContent>
                        </Card>
                    </section>

                    <Separator class="my-12" />

                    <!-- Score Range -->
                    <section class="mb-12" aria-labelledby="score-range">
                        <h2 id="score-range" class="text-2xl font-bold mb-6">Understanding Your Score</h2>

                        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            <Card class="border-green-500/50">
                                <CardHeader class="pb-2">
                                    <CardTitle class="text-green-500">A Grade (80-100)</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <p class="text-sm text-muted-foreground">Excellent AI comprehension readiness</p>
                                </CardContent>
                            </Card>
                            <Card class="border-blue-500/50">
                                <CardHeader class="pb-2">
                                    <CardTitle class="text-blue-500">B Grade (60-79)</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <p class="text-sm text-muted-foreground">Good foundation with room for improvement</p>
                                </CardContent>
                            </Card>
                            <Card class="border-amber-500/50">
                                <CardHeader class="pb-2">
                                    <CardTitle class="text-amber-500">C Grade (40-59)</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <p class="text-sm text-muted-foreground">Needs significant optimization</p>
                                </CardContent>
                            </Card>
                            <Card class="border-destructive/50">
                                <CardHeader class="pb-2">
                                    <CardTitle class="text-destructive">D/F Grade (0-39)</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <p class="text-sm text-muted-foreground">Unlikely to be cited by AI systems</p>
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
                        <Link href="/resources/how-llms-cite-sources" class="inline-flex items-center text-muted-foreground hover:text-foreground">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Previous: How LLMs Cite Sources
                        </Link>
                        <Link href="/resources/geo-content-framework" class="inline-flex items-center text-primary hover:underline">
                            Next: GEO Content Framework
                            <ArrowRight class="ml-2 h-4 w-4" />
                        </Link>
                    </div>
                </div>
            </article>

            <!-- CTA Section -->
            <section class="border-t bg-muted/30 py-12">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 text-center">
                    <h2 class="text-2xl font-bold">Ready to get your GEO Score?</h2>
                    <p class="mt-2 text-muted-foreground">Discover how AI systems see your content and what to improve.</p>
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
