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
    Database,
    Cpu,
    FileCheck,
    MessageSquare,
    Quote,
    CheckCircle,
    XCircle,
    Menu,
} from 'lucide-vue-next';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import ThemeSwitcher from '@/components/ThemeSwitcher.vue';

const aiComponents = [
    { icon: Database, title: 'Pre-trained data', description: 'Knowledge learned during model training' },
    { icon: Search, title: 'Retrieval-augmented generation (RAG)', description: 'Real-time retrieval of relevant content' },
    { icon: Cpu, title: 'Vector embeddings', description: 'Semantic understanding of meaning' },
    { icon: FileCheck, title: 'Trusted source selection', description: 'Evaluation of source quality and reliability' },
];

const answerSteps = [
    { step: 1, title: 'User asks a question', description: 'The query enters the system' },
    { step: 2, title: 'Question is converted into a vector', description: 'Semantic meaning is encoded numerically' },
    { step: 3, title: 'AI retrieves semantically relevant content', description: 'Similar content is found using vector similarity' },
    { step: 4, title: 'Sources are evaluated for clarity and authority', description: 'Quality signals determine which sources to trust' },
    { step: 5, title: 'The model synthesizes an answer', description: 'Information is combined into a coherent response' },
    { step: 6, title: 'Citations are optionally attached', description: 'Sources may be referenced in the answer' },
];

const contentPreferences = [
    'Clear definitions',
    'Explicit headings',
    'Factual statements',
    'Consistent terminology',
    'Minimal fluff',
    'Structured formatting',
];

const relatedArticles = [
    { slug: 'what-is-geo', title: 'What Is GEO?' },
    { slug: 'how-llms-cite-sources', title: 'How LLMs Cite Sources' },
    { slug: 'geo-content-framework', title: 'GEO Content Framework' },
];

const jsonLd = {
    '@context': 'https://schema.org',
    '@type': 'Article',
    headline: 'How AI Search Engines Actually Work',
    description: 'Explore the mechanics of generative AI search including RAG, vector embeddings, and source selection. Learn why keyword ranking does not apply.',
    url: 'https://geosource.ai/resources/how-ai-search-works',
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
        '@id': 'https://geosource.ai/resources/how-ai-search-works',
    },
};

const faqJsonLd = {
    '@context': 'https://schema.org',
    '@type': 'FAQPage',
    mainEntity: [
        {
            '@type': 'Question',
            name: 'How do AI search engines work?',
            acceptedAnswer: {
                '@type': 'Answer',
                text: 'AI search engines operate using pre-trained data, retrieval-augmented generation (RAG), vector embeddings, and trusted source selection. They convert questions into vectors, retrieve semantically relevant content, evaluate sources for clarity and authority, synthesize an answer, and optionally attach citations.',
            },
        },
        {
            '@type': 'Question',
            name: 'Do AI search engines use keyword ranking?',
            acceptedAnswer: {
                '@type': 'Answer',
                text: 'No, AI search engines do not use keyword ranking. Unlike traditional search engines that rank pages by keywords and backlinks, generative AI systems select sources based on semantic similarity, clarity, structure, and factual consistency.',
            },
        },
        {
            '@type': 'Question',
            name: 'What content do AI systems prefer?',
            acceptedAnswer: {
                '@type': 'Answer',
                text: 'AI systems consistently favor content that includes clear definitions, explicit headings, factual statements, consistent terminology, minimal fluff, and structured formatting. This forms the foundation of GEO optimization.',
            },
        },
    ],
};
</script>

<template>
    <Head title="How AI Search Engines Actually Work - GeoSource.ai">
        <meta name="description" content="Explore the mechanics of generative AI search including RAG, vector embeddings, and source selection. Learn why keyword ranking does not apply to AI systems." />
        <meta property="og:title" content="How AI Search Engines Actually Work" />
        <meta property="og:description" content="Explore the mechanics of generative AI search including RAG, vector embeddings, and source selection." />
        <meta property="og:type" content="article" />
        <meta property="og:url" content="https://geosource.ai/resources/how-ai-search-works" />
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" content="How AI Search Engines Actually Work" />
        <meta name="twitter:description" content="Explore the mechanics of generative AI search including RAG, vector embeddings, and source selection." />
        <link rel="canonical" href="https://geosource.ai/resources/how-ai-search-works" />
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
                        <span class="text-foreground">How AI Search Works</span>
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
                            Technical
                        </Badge>
                        <h1 class="text-3xl font-bold tracking-tight sm:text-4xl lg:text-5xl">
                            How AI Search Engines Actually Work
                        </h1>
                        <p class="mt-4 text-lg text-muted-foreground">
                            Understanding the technology behind generative AI search systems.
                        </p>
                    </header>

                    <!-- Key Difference -->
                    <section class="mb-12" aria-labelledby="key-difference">
                        <Card class="border-amber-500/50 bg-amber-500/5">
                            <CardContent class="pt-6">
                                <div class="flex items-start gap-3">
                                    <XCircle class="h-6 w-6 text-amber-500 shrink-0 mt-0.5" />
                                    <div>
                                        <p class="font-medium text-foreground text-lg">
                                            Generative AI engines do not "crawl the web" like Google.
                                        </p>
                                        <p class="text-muted-foreground mt-2">
                                            Instead, they operate using a fundamentally different approach.
                                        </p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </section>

                    <!-- AI Components -->
                    <section class="mb-12" aria-labelledby="ai-components">
                        <h2 id="ai-components" class="text-2xl font-bold mb-6">How Generative AI Operates</h2>
                        <p class="text-muted-foreground mb-6">
                            Generative AI engines operate using a combination of:
                        </p>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <Card v-for="component in aiComponents" :key="component.title">
                                <CardContent class="pt-6">
                                    <div class="flex items-start gap-3">
                                        <component :is="component.icon" class="h-6 w-6 text-primary shrink-0" />
                                        <div>
                                            <p class="font-medium">{{ component.title }}</p>
                                            <p class="text-sm text-muted-foreground mt-1">{{ component.description }}</p>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </section>

                    <Separator class="my-12" />

                    <!-- Step-by-Step Process -->
                    <section class="mb-12" aria-labelledby="answer-generation">
                        <h2 id="answer-generation" class="text-2xl font-bold mb-6">Step-by-Step AI Answer Generation</h2>
                        <ol class="space-y-4" role="list">
                            <li v-for="item in answerSteps" :key="item.step" class="flex items-start gap-4">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary text-primary-foreground text-sm font-bold">
                                    {{ item.step }}
                                </span>
                                <div class="pt-1">
                                    <p class="font-medium">{{ item.title }}</p>
                                    <p class="text-sm text-muted-foreground">{{ item.description }}</p>
                                </div>
                            </li>
                        </ol>

                        <Card class="mt-8 border-destructive/50 bg-destructive/5">
                            <CardContent class="pt-6 text-center">
                                <p class="text-lg font-medium text-foreground">
                                    At no point does keyword ranking occur.
                                </p>
                            </CardContent>
                        </Card>
                    </section>

                    <Separator class="my-12" />

                    <!-- What AI Systems Prefer -->
                    <section class="mb-12" aria-labelledby="ai-preferences">
                        <h2 id="ai-preferences" class="text-2xl font-bold mb-6">What AI Systems Prefer</h2>
                        <p class="text-muted-foreground mb-6">
                            LLMs consistently favor content that includes:
                        </p>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div v-for="item in contentPreferences" :key="item" class="flex items-center gap-3">
                                <CheckCircle class="h-5 w-5 text-green-500 shrink-0" />
                                <span>{{ item }}</span>
                            </div>
                        </div>

                        <Card class="mt-8 border-primary/50 bg-primary/5">
                            <CardContent class="pt-6 text-center">
                                <p class="text-lg">
                                    This is the foundation of <strong class="text-primary">GEO optimization</strong>.
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
                        <Link href="/resources/geo-vs-seo" class="inline-flex items-center text-muted-foreground hover:text-foreground">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Previous: GEO vs SEO
                        </Link>
                        <Link href="/resources/how-llms-cite-sources" class="inline-flex items-center text-primary hover:underline">
                            Next: How LLMs Cite Sources
                            <ArrowRight class="ml-2 h-4 w-4" />
                        </Link>
                    </div>
                </div>
            </article>

            <!-- CTA Section -->
            <section class="border-t bg-muted/30 py-12">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 text-center">
                    <h2 class="text-2xl font-bold">Optimize your content for AI systems</h2>
                    <p class="mt-2 text-muted-foreground">Get your GEO Score and see how AI search engines view your site.</p>
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
