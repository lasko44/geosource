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
    Quote,
    Lightbulb,
    CheckCircle,
    Menu,
    Mail,
    XCircle,
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

const citationFactors = [
    'Clear explanations',
    'Neutral tone',
    'Definitional clarity',
    'Strong structure',
    'Minimal promotional language',
];

const improveCitation = [
    'Publish clear definitions',
    'Use headings consistently',
    'Answer questions directly',
    'Avoid fluff and filler content',
    'Maintain topical focus',
];

const relatedDefinitions = [
    { slug: 'what-is-geo', title: 'What Is Generative Engine Optimization (GEO)?' },
    { slug: 'how-llms-cite-sources', title: 'How Large Language Models Choose Which Sources to Cite' },
];

const relatedResources = [
    { slug: 'e-e-a-t-and-geo', title: 'E-E-A-T and Generative Engine Optimization (GEO)' },
    { slug: 'ai-accessibility-for-geo', title: 'AI Accessibility and GEO' },
    { slug: 'readability-and-geo', title: 'Readability and GEO' },
    { slug: 'question-coverage-for-geo', title: 'Question Coverage and GEO' },
    { slug: 'content-freshness-for-geo', title: 'Content Freshness and GEO' },
];

const jsonLd = {
    '@context': 'https://schema.org',
    '@type': 'Article',
    headline: 'Citations and GEO: How AI Chooses Sources',
    description: 'Learn how AI systems decide which sources to cite in generated answers and how to optimize your content for citation readiness in GEO.',
    url: 'https://geosource.ai/resources/ai-citations-and-geo',
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
        '@id': 'https://geosource.ai/resources/ai-citations-and-geo',
    },
};

const faqJsonLd = {
    '@context': 'https://schema.org',
    '@type': 'FAQPage',
    mainEntity: [
        {
            '@type': 'Question',
            name: 'What is an AI citation?',
            acceptedAnswer: {
                '@type': 'Answer',
                text: 'An AI citation occurs when a generative system references a website as the source of information within an answer. Unlike search rankings, citations depend on confidence and clarity, not popularity.',
            },
        },
        {
            '@type': 'Question',
            name: 'What does citation readiness mean in GEO?',
            acceptedAnswer: {
                '@type': 'Answer',
                text: 'Citation readiness means your content is structured in a way that allows AI to extract meaning easily, summarize accurately, and attribute information safely. GEO optimizes content for these behaviors.',
            },
        },
        {
            '@type': 'Question',
            name: 'How can I improve my citation probability?',
            acceptedAnswer: {
                '@type': 'Answer',
                text: 'To improve citation probability: publish definitions, use headings consistently, answer questions directly, avoid fluff, and maintain topical focus. AI cites the clearest content, not the loudest.',
            },
        },
    ],
};
</script>

<template>
    <Head title="Citations and GEO: How AI Chooses Sources - GeoSource.ai">
        <meta name="description" content="Learn how AI systems decide which sources to cite in generated answers and how to optimize your content for citation readiness in GEO." />
        <meta property="og:title" content="Citations and GEO: How AI Chooses Sources" />
        <meta property="og:description" content="Learn how AI systems decide which sources to cite and how to optimize for citation readiness." />
        <meta property="og:type" content="article" />
        <meta property="og:url" content="https://geosource.ai/resources/ai-citations-and-geo" />
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" content="Citations and GEO: How AI Chooses Sources" />
        <meta name="twitter:description" content="Learn how AI systems decide which sources to cite." />
        <link rel="canonical" href="https://geosource.ai/resources/ai-citations-and-geo" />
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
                        <span class="text-foreground">Citations and GEO</span>
                    </nav>
                </div>
            </div>

            <!-- Article -->
            <article class="py-12">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <!-- Header -->
                    <header class="mb-12">
                        <Badge variant="secondary" class="mb-4">
                            <Quote class="mr-1 h-3 w-3" />
                            GEO Pillar
                        </Badge>
                        <h1 class="text-3xl font-bold tracking-tight sm:text-4xl lg:text-5xl">
                            Citations and GEO: How AI Chooses Sources
                        </h1>
                        <p class="mt-4 text-lg text-muted-foreground">
                            Understanding what makes AI systems cite your content in generated answers.
                        </p>
                        <div class="mt-4 flex items-center gap-2 text-sm text-muted-foreground">
                            <Calendar class="h-4 w-4" />
                            <span>{{ publishedDate }}</span>
                        </div>
                    </header>

                    <!-- Intro with required links -->
                    <section class="mb-12">
                        <p class="text-muted-foreground mb-4">
                            In <Link href="/resources/what-is-geo" class="text-primary hover:underline">Generative Engine Optimization (GEO)</Link>, citations are the currency of visibility. When AI cites your content, it validates your authority and drives qualified traffic. Understanding how to optimize for citations can significantly improve your <Link href="/resources/what-is-a-geo-score" class="text-primary hover:underline">GEO Score</Link> and visibility in the <Link href="/" class="text-primary hover:underline">GeoSource.ai Platform</Link>.
                        </p>
                    </section>

                    <!-- Definition Section -->
                    <section class="mb-12" aria-labelledby="definition">
                        <h2 id="definition" class="text-2xl font-bold mb-6">What Is an AI Citation?</h2>
                        <Card class="border-primary/50 bg-primary/5">
                            <CardContent class="pt-6">
                                <p class="text-lg leading-relaxed">
                                    An <dfn class="font-semibold not-italic"><strong>AI citation</strong></dfn> occurs when a generative system references a website as the source of information within an answer. Unlike search rankings, citations depend on <strong>confidence and clarity</strong>, not popularity.
                                </p>
                            </CardContent>
                        </Card>
                    </section>

                    <Separator class="my-12" />

                    <!-- What AI Looks For -->
                    <section class="mb-12" aria-labelledby="ai-factors">
                        <h2 id="ai-factors" class="text-2xl font-bold mb-6">What AI Looks for Before Citing</h2>
                        <p class="text-muted-foreground mb-6">
                            LLMs favor sources that demonstrate:
                        </p>
                        <Card>
                            <CardContent class="pt-6">
                                <ul class="space-y-3">
                                    <li v-for="factor in citationFactors" :key="factor" class="flex items-start gap-3">
                                        <CheckCircle class="h-5 w-5 text-green-500 shrink-0 mt-0.5" />
                                        <span>{{ factor }}</span>
                                    </li>
                                </ul>
                            </CardContent>
                        </Card>
                        <Card class="mt-6 border-amber-500/50 bg-amber-500/5">
                            <CardContent class="pt-6">
                                <div class="flex items-start gap-3">
                                    <Lightbulb class="h-6 w-6 text-amber-500 shrink-0 mt-0.5" />
                                    <p class="text-muted-foreground">
                                        <strong class="text-foreground">This is why marketing-heavy pages are rarely cited.</strong> Promotional language introduces uncertainty that AI systems avoid.
                                    </p>
                                </div>
                            </CardContent>
                        </Card>
                    </section>

                    <Separator class="my-12" />

                    <!-- Citation Readiness -->
                    <section class="mb-12" aria-labelledby="citation-readiness">
                        <h2 id="citation-readiness" class="text-2xl font-bold mb-6">Citation Readiness in GEO</h2>
                        <p class="text-muted-foreground mb-6">
                            <strong class="text-foreground">Citation readiness</strong> means your content is structured in a way that allows AI to:
                        </p>
                        <div class="grid gap-4 sm:grid-cols-3">
                            <Card>
                                <CardContent class="pt-6 text-center">
                                    <p class="font-medium">Extract meaning easily</p>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardContent class="pt-6 text-center">
                                    <p class="font-medium">Summarize accurately</p>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardContent class="pt-6 text-center">
                                    <p class="font-medium">Attribute information safely</p>
                                </CardContent>
                            </Card>
                        </div>
                        <p class="mt-6 text-muted-foreground">
                            <strong class="text-foreground">GEO optimizes content for these behaviors.</strong>
                        </p>
                    </section>

                    <Separator class="my-12" />

                    <!-- How to Improve -->
                    <section class="mb-12" aria-labelledby="improve">
                        <h2 id="improve" class="text-2xl font-bold mb-6">How to Improve Citation Probability</h2>
                        <Card>
                            <CardContent class="pt-6">
                                <ul class="space-y-3">
                                    <li v-for="tip in improveCitation" :key="tip" class="flex items-start gap-3">
                                        <CheckCircle class="h-5 w-5 text-green-500 shrink-0 mt-0.5" />
                                        <span>{{ tip }}</span>
                                    </li>
                                </ul>
                            </CardContent>
                        </Card>
                    </section>

                    <Separator class="my-12" />

                    <!-- What to Avoid -->
                    <section class="mb-12" aria-labelledby="avoid">
                        <h2 id="avoid" class="text-2xl font-bold mb-6">What Reduces Citation Probability</h2>
                        <Card class="border-red-500/30 bg-red-500/5">
                            <CardContent class="pt-6">
                                <ul class="space-y-3">
                                    <li class="flex items-start gap-3">
                                        <XCircle class="h-5 w-5 text-red-500 shrink-0 mt-0.5" />
                                        <span>Heavy promotional language</span>
                                    </li>
                                    <li class="flex items-start gap-3">
                                        <XCircle class="h-5 w-5 text-red-500 shrink-0 mt-0.5" />
                                        <span>Vague or ambiguous statements</span>
                                    </li>
                                    <li class="flex items-start gap-3">
                                        <XCircle class="h-5 w-5 text-red-500 shrink-0 mt-0.5" />
                                        <span>Missing structure and headings</span>
                                    </li>
                                    <li class="flex items-start gap-3">
                                        <XCircle class="h-5 w-5 text-red-500 shrink-0 mt-0.5" />
                                        <span>Inconsistent terminology</span>
                                    </li>
                                    <li class="flex items-start gap-3">
                                        <XCircle class="h-5 w-5 text-red-500 shrink-0 mt-0.5" />
                                        <span>Off-topic tangents</span>
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
                                    AI doesn't cite the loudest site —<br />
                                    it cites the clearest one.
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
                        <Link href="/resources/e-e-a-t-and-geo" class="inline-flex items-center text-muted-foreground hover:text-foreground">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Previous: E-E-A-T and GEO
                        </Link>
                        <Link href="/resources/ai-accessibility-for-geo" class="inline-flex items-center text-primary hover:underline">
                            Next: AI Accessibility
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
