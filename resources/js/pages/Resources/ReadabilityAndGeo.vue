<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import {
    Globe,
    ArrowRight,
    ArrowLeft,
    BookOpen,
    Lightbulb,
    CheckCircle,
    Menu,
    Mail,
    Type,
    AlignLeft,
} from 'lucide-vue-next';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import ThemeSwitcher from '@/components/ThemeSwitcher.vue';

const aiExtractsFrom = [
    'Sentence clarity',
    'Paragraph structure',
    'Heading logic',
    'Direct explanations',
];

const readabilityTips = [
    'Short paragraphs',
    'Simple sentence structure',
    'Clear definitions',
    'Minimal jargon',
    'Consistent terminology',
];

const relatedDefinitions = [
    { slug: 'what-is-geo', title: 'What Is Generative Engine Optimization (GEO)?' },
    { slug: 'what-is-a-geo-score', title: 'What Is a GEO Score?' },
];

const relatedResources = [
    { slug: 'e-e-a-t-and-geo', title: 'E-E-A-T and Generative Engine Optimization (GEO)' },
    { slug: 'ai-citations-and-geo', title: 'Citations and GEO: How AI Chooses Sources' },
    { slug: 'question-coverage-for-geo', title: 'Question Coverage and GEO' },
    { slug: 'content-freshness-for-geo', title: 'Content Freshness and GEO' },
    { slug: 'multimedia-and-geo', title: 'Multimedia and GEO' },
];

const jsonLd = {
    '@context': 'https://schema.org',
    '@type': 'Article',
    headline: 'Readability and GEO',
    description: 'Learn how content readability affects AI comprehension and how to optimize your writing for better GEO performance.',
    url: 'https://geosource.ai/resources/readability-and-geo',
    datePublished: '2025-01-20',
    dateModified: '2025-01-20',
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
        '@id': 'https://geosource.ai/resources/readability-and-geo',
    },
};

const faqJsonLd = {
    '@context': 'https://schema.org',
    '@type': 'FAQPage',
    mainEntity: [
        {
            '@type': 'Question',
            name: 'What is readability in GEO?',
            acceptedAnswer: {
                '@type': 'Answer',
                text: 'Readability measures how easily content can be understood. For GEO, readability directly affects comprehension by AI systems.',
            },
        },
        {
            '@type': 'Question',
            name: 'Why does readability matter for AI?',
            acceptedAnswer: {
                '@type': 'Answer',
                text: 'LLMs extract meaning from sentence clarity, paragraph structure, heading logic, and direct explanations. Complex language introduces uncertainty.',
            },
        },
        {
            '@type': 'Question',
            name: 'How can I optimize readability for GEO?',
            acceptedAnswer: {
                '@type': 'Answer',
                text: 'Use short paragraphs, simple sentence structure, clear definitions, minimal jargon, and consistent terminology. Readable content improves retrieval success.',
            },
        },
    ],
};
</script>

<template>
    <Head title="Readability and GEO - GeoSource.ai">
        <meta name="description" content="Learn how content readability affects AI comprehension and how to optimize your writing for better GEO performance." />
        <meta property="og:title" content="Readability and GEO" />
        <meta property="og:description" content="Learn how content readability affects AI comprehension." />
        <meta property="og:type" content="article" />
        <meta property="og:url" content="https://geosource.ai/resources/readability-and-geo" />
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" content="Readability and GEO" />
        <meta name="twitter:description" content="Learn how content readability affects AI comprehension." />
        <link rel="canonical" href="https://geosource.ai/resources/readability-and-geo" />
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
                <div class="flex items-center gap-2 sm:hidden">
                    <ThemeSwitcher />
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button variant="ghost" size="icon">
                                <Menu class="h-5 w-5" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="w-48">
                            <DropdownMenuItem as-child>
                                <Link href="/pricing" class="w-full">Pricing</Link>
                            </DropdownMenuItem>
                            <DropdownMenuItem as-child>
                                <Link href="/resources" class="w-full">Resources</Link>
                            </DropdownMenuItem>
                            <DropdownMenuItem v-if="$page.props.auth.user" as-child>
                                <Link href="/dashboard" class="w-full">Dashboard</Link>
                            </DropdownMenuItem>
                            <template v-else>
                                <DropdownMenuItem as-child>
                                    <Link href="/login" class="w-full">Log in</Link>
                                </DropdownMenuItem>
                                <DropdownMenuItem as-child>
                                    <Link href="/register" class="w-full font-medium text-primary">Get Started</Link>
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
                        <span class="text-foreground">Readability and GEO</span>
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
                            GEO Pillar
                        </Badge>
                        <h1 class="text-3xl font-bold tracking-tight sm:text-4xl lg:text-5xl">
                            Readability and GEO
                        </h1>
                        <p class="mt-4 text-lg text-muted-foreground">
                            How clear, accessible writing improves AI comprehension and citation probability.
                        </p>
                    </header>

                    <!-- Intro with required links -->
                    <section class="mb-12">
                        <p class="text-muted-foreground mb-4">
                            In <Link href="/resources/what-is-geo" class="text-primary hover:underline">Generative Engine Optimization (GEO)</Link>, readability isn't just about human comprehension—it directly affects how AI systems understand and cite your content. Improving readability can significantly boost your <Link href="/resources/what-is-a-geo-score" class="text-primary hover:underline">GEO Score</Link> and visibility in the <Link href="/" class="text-primary hover:underline">GeoSource.ai Platform</Link>.
                        </p>
                    </section>

                    <!-- Definition Section -->
                    <section class="mb-12" aria-labelledby="definition">
                        <h2 id="definition" class="text-2xl font-bold mb-6">What Is Readability?</h2>
                        <Card class="border-primary/50 bg-primary/5">
                            <CardContent class="pt-6">
                                <p class="text-lg leading-relaxed">
                                    <dfn class="font-semibold not-italic"><strong>Readability</strong></dfn> measures how easily content can be understood. For GEO, readability <strong>directly affects comprehension</strong> by AI systems.
                                </p>
                            </CardContent>
                        </Card>
                    </section>

                    <Separator class="my-12" />

                    <!-- Why Readability Matters -->
                    <section class="mb-12" aria-labelledby="why-matters">
                        <h2 id="why-matters" class="text-2xl font-bold mb-6">Why Readability Matters for AI</h2>
                        <p class="text-muted-foreground mb-6">
                            LLMs extract meaning from:
                        </p>
                        <Card>
                            <CardContent class="pt-6">
                                <ul class="space-y-3">
                                    <li v-for="item in aiExtractsFrom" :key="item" class="flex items-start gap-3">
                                        <CheckCircle class="h-5 w-5 text-green-500 shrink-0 mt-0.5" />
                                        <span>{{ item }}</span>
                                    </li>
                                </ul>
                            </CardContent>
                        </Card>
                        <Card class="mt-6 border-amber-500/50 bg-amber-500/5">
                            <CardContent class="pt-6">
                                <div class="flex items-start gap-3">
                                    <Lightbulb class="h-6 w-6 text-amber-500 shrink-0 mt-0.5" />
                                    <p class="text-muted-foreground">
                                        <strong class="text-foreground">Complex language introduces uncertainty.</strong> When AI can't parse meaning confidently, it won't cite the content.
                                    </p>
                                </div>
                            </CardContent>
                        </Card>
                    </section>

                    <Separator class="my-12" />

                    <!-- Optimizing Readability -->
                    <section class="mb-12" aria-labelledby="optimizing">
                        <h2 id="optimizing" class="text-2xl font-bold mb-6">Optimizing Readability for GEO</h2>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <Card v-for="tip in readabilityTips" :key="tip">
                                <CardContent class="pt-6">
                                    <div class="flex items-start gap-3">
                                        <Type class="h-5 w-5 text-primary shrink-0 mt-0.5" />
                                        <span class="font-medium">{{ tip }}</span>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                        <p class="mt-6 text-muted-foreground">
                            <strong class="text-foreground">Readable content improves retrieval success.</strong>
                        </p>
                    </section>

                    <Separator class="my-12" />

                    <!-- Practical Guidelines -->
                    <section class="mb-12" aria-labelledby="guidelines">
                        <h2 id="guidelines" class="text-2xl font-bold mb-6">Practical Guidelines</h2>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <Card>
                                <CardContent class="pt-6">
                                    <h3 class="font-semibold mb-3 flex items-center gap-2">
                                        <AlignLeft class="h-4 w-4 text-primary" />
                                        Sentence Structure
                                    </h3>
                                    <ul class="space-y-2 text-sm text-muted-foreground">
                                        <li>• Aim for 15-20 words per sentence</li>
                                        <li>• Vary sentence length for rhythm</li>
                                        <li>• Lead with the main point</li>
                                        <li>• Avoid nested clauses</li>
                                    </ul>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardContent class="pt-6">
                                    <h3 class="font-semibold mb-3 flex items-center gap-2">
                                        <Type class="h-4 w-4 text-primary" />
                                        Paragraph Structure
                                    </h3>
                                    <ul class="space-y-2 text-sm text-muted-foreground">
                                        <li>• One idea per paragraph</li>
                                        <li>• 3-5 sentences maximum</li>
                                        <li>• Use transitions between sections</li>
                                        <li>• White space improves scanning</li>
                                    </ul>
                                </CardContent>
                            </Card>
                        </div>
                    </section>

                    <Separator class="my-12" />

                    <!-- Readability Metrics -->
                    <section class="mb-12" aria-labelledby="metrics">
                        <h2 id="metrics" class="text-2xl font-bold mb-6">Readability Metrics</h2>
                        <p class="text-muted-foreground mb-6">
                            Common readability measurements include:
                        </p>
                        <Card>
                            <CardContent class="pt-6">
                                <ul class="space-y-4">
                                    <li>
                                        <strong class="text-foreground">Flesch Reading Ease</strong>
                                        <p class="text-sm text-muted-foreground mt-1">Score 60-70 is ideal for general audiences. Higher = easier to read.</p>
                                    </li>
                                    <li>
                                        <strong class="text-foreground">Flesch-Kincaid Grade Level</strong>
                                        <p class="text-sm text-muted-foreground mt-1">Target 8th-9th grade level for maximum accessibility.</p>
                                    </li>
                                    <li>
                                        <strong class="text-foreground">Average Sentence Length</strong>
                                        <p class="text-sm text-muted-foreground mt-1">15-20 words is optimal. Avoid sentences over 35 words.</p>
                                    </li>
                                </ul>
                            </CardContent>
                        </Card>
                    </section>

                    <Separator class="my-12" />

                    <!-- Key Takeaway -->
                    <section class="mb-12" aria-labelledby="takeaway">
                        <h2 id="takeaway" class="text-2xl font-bold mb-6">Key Takeaway</h2>
                        <Card class="border-primary bg-primary/5">
                            <CardContent class="pt-6">
                                <p class="text-xl font-medium text-center">
                                    If content is easy to explain,<br />
                                    it is easy to cite.
                                </p>
                            </CardContent>
                        </Card>
                    </section>

                    <Separator class="my-12" />

                    <!-- Related Definitions -->
                    <section class="mb-12" aria-labelledby="related-definitions">
                        <h2 id="related-definitions" class="text-2xl font-bold mb-6">Related Definitions</h2>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <Link
                                v-for="article in relatedDefinitions"
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

                    <Separator class="my-12" />

                    <!-- Related Resources -->
                    <section aria-labelledby="related">
                        <h2 id="related" class="text-2xl font-bold mb-6">Related Resources</h2>
                        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            <Link
                                v-for="article in relatedResources"
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
                        <Link href="/resources/content-freshness-for-geo" class="inline-flex items-center text-muted-foreground hover:text-foreground">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Previous: Content Freshness
                        </Link>
                        <Link href="/resources/question-coverage-for-geo" class="inline-flex items-center text-primary hover:underline">
                            Next: Question Coverage
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
