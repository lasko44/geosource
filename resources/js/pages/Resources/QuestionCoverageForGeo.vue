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
    HelpCircle,
    Lightbulb,
    CheckCircle,
    Menu,
    Mail,
    MessageCircleQuestion,
    ListChecks,
    Calendar,
} from 'lucide-vue-next';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import ThemeSwitcher from '@/components/ThemeSwitcher.vue';

const publishedDate = new Date('2026-01-20').toLocaleDateString('en-US', { month: 'long', year: 'numeric' });

const aiRetrievalBasis = [
    'Implied questions',
    'Conversational prompts',
    'FAQ patterns',
];

const improveQuestionCoverage = [
    'Include FAQs',
    'Use question headings',
    'Answer directly',
    'Expand topic depth',
];

const questionTypes = [
    { type: 'What', example: 'What is GEO?' },
    { type: 'How', example: 'How does GEO work?' },
    { type: 'Why', example: 'Why does GEO matter?' },
    { type: 'When', example: 'When should I use GEO?' },
    { type: 'Where', example: 'Where do I start with GEO?' },
];

const relatedDefinitions = [
    { slug: 'what-is-geo', title: 'What Is Generative Engine Optimization (GEO)?' },
    { slug: 'what-is-a-geo-score', title: 'What Is a GEO Score?' },
];

const relatedResources = [
    { slug: 'e-e-a-t-and-geo', title: 'E-E-A-T and Generative Engine Optimization (GEO)' },
    { slug: 'ai-citations-and-geo', title: 'Citations and GEO: How AI Chooses Sources' },
    { slug: 'readability-and-geo', title: 'Readability and GEO' },
    { slug: 'content-freshness-for-geo', title: 'Content Freshness and GEO' },
    { slug: 'multimedia-and-geo', title: 'Multimedia and GEO' },
];

const jsonLd = {
    '@context': 'https://schema.org',
    '@type': 'Article',
    headline: 'Question Coverage and GEO',
    description: 'Learn how comprehensive question coverage improves AI retrieval and how to structure content that answers what users and AI systems are asking.',
    url: 'https://geosource.ai/resources/question-coverage-for-geo',
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
        '@id': 'https://geosource.ai/resources/question-coverage-for-geo',
    },
};

const faqJsonLd = {
    '@context': 'https://schema.org',
    '@type': 'FAQPage',
    mainEntity: [
        {
            '@type': 'Question',
            name: 'What is question coverage in GEO?',
            acceptedAnswer: {
                '@type': 'Answer',
                text: 'Question coverage refers to how well content answers common user questions within a topic. AI systems rely heavily on Q&A-style knowledge.',
            },
        },
        {
            '@type': 'Question',
            name: 'Why does question coverage matter for AI?',
            acceptedAnswer: {
                '@type': 'Answer',
                text: 'AI models often retrieve content based on implied questions, conversational prompts, and FAQ patterns. Pages that answer questions explicitly perform better.',
            },
        },
        {
            '@type': 'Question',
            name: 'How can I improve question coverage?',
            acceptedAnswer: {
                '@type': 'Answer',
                text: 'Include FAQs, use question headings, answer directly, and expand topic depth. This increases retrieval opportunities for AI systems.',
            },
        },
    ],
};
</script>

<template>
    <Head title="Question Coverage and GEO - GeoSource.ai">
        <meta name="description" content="Learn how comprehensive question coverage improves AI retrieval and how to structure content that answers what users and AI systems are asking." />
        <meta property="og:title" content="Question Coverage and GEO" />
        <meta property="og:description" content="Learn how comprehensive question coverage improves AI retrieval." />
        <meta property="og:type" content="article" />
        <meta property="og:url" content="https://geosource.ai/resources/question-coverage-for-geo" />
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" content="Question Coverage and GEO" />
        <meta name="twitter:description" content="Learn how comprehensive question coverage improves AI retrieval." />
        <link rel="canonical" href="https://geosource.ai/resources/question-coverage-for-geo" />
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
                        <span class="text-foreground">Question Coverage and GEO</span>
                    </nav>
                </div>
            </div>

            <!-- Article -->
            <article class="py-12">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <!-- Header -->
                    <header class="mb-12">
                        <Badge variant="secondary" class="mb-4">
                            <HelpCircle class="mr-1 h-3 w-3" />
                            GEO Pillar
                        </Badge>
                        <h1 class="text-3xl font-bold tracking-tight sm:text-4xl lg:text-5xl">
                            Question Coverage and GEO
                        </h1>
                        <p class="mt-4 text-lg text-muted-foreground">
                            How answering user questions directly improves AI retrieval and citation probability.
                        </p>
                        <div class="mt-4 flex items-center gap-2 text-sm text-muted-foreground">
                            <Calendar class="h-4 w-4" />
                            <span>{{ publishedDate }}</span>
                        </div>
                    </header>

                    <!-- Intro with required links -->
                    <section class="mb-12">
                        <p class="text-muted-foreground mb-4">
                            In <Link href="/resources/what-is-geo" class="text-primary hover:underline">Generative Engine Optimization (GEO)</Link>, question coverage is essential because AI systems think in questions. Understanding how to anticipate and answer queries can significantly improve your <Link href="/resources/what-is-a-geo-score" class="text-primary hover:underline">GEO Score</Link> and visibility in the <Link href="/" class="text-primary hover:underline">GeoSource.ai Platform</Link>.
                        </p>
                    </section>

                    <!-- Definition Section -->
                    <section class="mb-12" aria-labelledby="definition">
                        <h2 id="definition" class="text-2xl font-bold mb-6">What Is Question Coverage?</h2>
                        <Card class="border-primary/50 bg-primary/5">
                            <CardContent class="pt-6">
                                <p class="text-lg leading-relaxed">
                                    <dfn class="font-semibold not-italic"><strong>Question coverage</strong></dfn> refers to how well content answers common user questions within a topic. <strong>AI systems rely heavily on Q&A-style knowledge.</strong>
                                </p>
                            </CardContent>
                        </Card>
                    </section>

                    <Separator class="my-12" />

                    <!-- Why It Matters -->
                    <section class="mb-12" aria-labelledby="why-matters">
                        <h2 id="why-matters" class="text-2xl font-bold mb-6">Why Question Coverage Matters</h2>
                        <p class="text-muted-foreground mb-6">
                            AI models often retrieve content based on:
                        </p>
                        <Card>
                            <CardContent class="pt-6">
                                <ul class="space-y-3">
                                    <li v-for="basis in aiRetrievalBasis" :key="basis" class="flex items-start gap-3">
                                        <CheckCircle class="h-5 w-5 text-green-500 shrink-0 mt-0.5" />
                                        <span>{{ basis }}</span>
                                    </li>
                                </ul>
                            </CardContent>
                        </Card>
                        <Card class="mt-6 border-amber-500/50 bg-amber-500/5">
                            <CardContent class="pt-6">
                                <div class="flex items-start gap-3">
                                    <Lightbulb class="h-6 w-6 text-amber-500 shrink-0 mt-0.5" />
                                    <p class="text-muted-foreground">
                                        <strong class="text-foreground">Pages that answer questions explicitly perform better.</strong> Direct answers increase citation probability.
                                    </p>
                                </div>
                            </CardContent>
                        </Card>
                    </section>

                    <Separator class="my-12" />

                    <!-- How to Improve -->
                    <section class="mb-12" aria-labelledby="improve">
                        <h2 id="improve" class="text-2xl font-bold mb-6">How to Improve Question Coverage</h2>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <Card v-for="tip in improveQuestionCoverage" :key="tip">
                                <CardContent class="pt-6">
                                    <div class="flex items-start gap-3">
                                        <ListChecks class="h-5 w-5 text-primary shrink-0 mt-0.5" />
                                        <span class="font-medium">{{ tip }}</span>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                        <p class="mt-6 text-muted-foreground">
                            <strong class="text-foreground">This increases retrieval opportunities.</strong>
                        </p>
                    </section>

                    <Separator class="my-12" />

                    <!-- Question Types -->
                    <section class="mb-12" aria-labelledby="question-types">
                        <h2 id="question-types" class="text-2xl font-bold mb-6">Question Types to Cover</h2>
                        <p class="text-muted-foreground mb-6">
                            Comprehensive content should address multiple question types:
                        </p>
                        <div class="overflow-x-auto">
                            <table class="w-full border-collapse">
                                <thead>
                                    <tr class="border-b">
                                        <th class="py-3 px-4 text-left font-semibold">Type</th>
                                        <th class="py-3 px-4 text-left font-semibold">Example</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="q in questionTypes" :key="q.type" class="border-b">
                                        <td class="py-3 px-4">
                                            <span class="inline-flex items-center gap-2">
                                                <MessageCircleQuestion class="h-4 w-4 text-primary" />
                                                {{ q.type }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-muted-foreground">{{ q.example }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <Separator class="my-12" />

                    <!-- FAQ Schema -->
                    <section class="mb-12" aria-labelledby="faq-schema">
                        <h2 id="faq-schema" class="text-2xl font-bold mb-6">Using FAQ Schema</h2>
                        <p class="text-muted-foreground mb-6">
                            Enhance question coverage with structured data:
                        </p>
                        <Card>
                            <CardContent class="pt-6">
                                <ul class="space-y-3">
                                    <li class="flex items-start gap-3">
                                        <CheckCircle class="h-5 w-5 text-green-500 shrink-0 mt-0.5" />
                                        <span>Add FAQPage schema to Q&A sections</span>
                                    </li>
                                    <li class="flex items-start gap-3">
                                        <CheckCircle class="h-5 w-5 text-green-500 shrink-0 mt-0.5" />
                                        <span>Use question-based headings (H2, H3)</span>
                                    </li>
                                    <li class="flex items-start gap-3">
                                        <CheckCircle class="h-5 w-5 text-green-500 shrink-0 mt-0.5" />
                                        <span>Provide direct answers immediately after questions</span>
                                    </li>
                                    <li class="flex items-start gap-3">
                                        <CheckCircle class="h-5 w-5 text-green-500 shrink-0 mt-0.5" />
                                        <span>Keep answers concise and definitive</span>
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
                                    AI thinks in questions —<br />
                                    GEO content should answer them.
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
                        <Link href="/resources/readability-and-geo" class="inline-flex items-center text-muted-foreground hover:text-foreground">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Previous: Readability and GEO
                        </Link>
                        <Link href="/resources/multimedia-and-geo" class="inline-flex items-center text-primary hover:underline">
                            Next: Multimedia and GEO
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
