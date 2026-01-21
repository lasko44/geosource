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
    Image,
    Lightbulb,
    CheckCircle,
    Menu,
    Mail,
    Video,
    FileText,
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

const multimediaBenefits = [
    'Reinforcing topical relevance',
    'Improving user engagement',
    'Increasing perceived completeness',
    'Providing surrounding textual context',
];

const bestPractices = [
    'Always include alt text',
    'Provide transcripts for videos',
    'Summarize visuals in text',
    'Avoid media-only explanations',
];

const relatedDefinitions = [
    { slug: 'what-is-geo', title: 'What Is Generative Engine Optimization (GEO)?' },
    { slug: 'what-is-a-geo-score', title: 'What Is a GEO Score?' },
];

const relatedResources = [
    { slug: 'e-e-a-t-and-geo', title: 'E-E-A-T and Generative Engine Optimization (GEO)' },
    { slug: 'ai-accessibility-for-geo', title: 'AI Accessibility and GEO' },
    { slug: 'readability-and-geo', title: 'Readability and GEO' },
    { slug: 'question-coverage-for-geo', title: 'Question Coverage and GEO' },
    { slug: 'ai-citations-and-geo', title: 'Citations and GEO: How AI Chooses Sources' },
];

const jsonLd = {
    '@context': 'https://schema.org',
    '@type': 'Article',
    headline: 'Multimedia and GEO',
    description: 'Learn how multimedia content supports GEO and how to optimize images, videos, and other media for AI systems.',
    url: 'https://geosource.ai/resources/multimedia-and-geo',
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
        '@id': 'https://geosource.ai/resources/multimedia-and-geo',
    },
};

const faqJsonLd = {
    '@context': 'https://schema.org',
    '@type': 'FAQPage',
    mainEntity: [
        {
            '@type': 'Question',
            name: 'Does multimedia matter for GEO?',
            acceptedAnswer: {
                '@type': 'Answer',
                text: 'Yes, but not in the way people expect. AI cannot "watch" videos or "see" images, but it can interpret their context. Captions and transcripts matter most.',
            },
        },
        {
            '@type': 'Question',
            name: 'How does multimedia support GEO?',
            acceptedAnswer: {
                '@type': 'Answer',
                text: 'Multimedia supports GEO by reinforcing topical relevance, improving user engagement, increasing perceived completeness, and providing surrounding textual context.',
            },
        },
        {
            '@type': 'Question',
            name: 'What are multimedia best practices for GEO?',
            acceptedAnswer: {
                '@type': 'Answer',
                text: 'Always include alt text, provide transcripts for videos, summarize visuals in text, and avoid media-only explanations. Text remains primary for AI understanding.',
            },
        },
    ],
};
</script>

<template>
    <Head title="Multimedia and GEO - GeoSource.ai">
        <meta name="description" content="Learn how multimedia content supports GEO and how to optimize images, videos, and other media for AI systems." />
        <meta property="og:title" content="Multimedia and GEO" />
        <meta property="og:description" content="Learn how multimedia content supports GEO." />
        <meta property="og:type" content="article" />
        <meta property="og:url" content="https://geosource.ai/resources/multimedia-and-geo" />
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" content="Multimedia and GEO" />
        <meta name="twitter:description" content="Learn how multimedia content supports GEO." />
        <link rel="canonical" href="https://geosource.ai/resources/multimedia-and-geo" />
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
                        <span class="text-foreground">Multimedia and GEO</span>
                    </nav>
                </div>
            </div>

            <!-- Article -->
            <article class="py-12">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <!-- Header -->
                    <header class="mb-12">
                        <Badge variant="secondary" class="mb-4">
                            <Image class="mr-1 h-3 w-3" />
                            GEO Pillar
                        </Badge>
                        <h1 class="text-3xl font-bold tracking-tight sm:text-4xl lg:text-5xl">
                            Multimedia and GEO
                        </h1>
                        <p class="mt-4 text-lg text-muted-foreground">
                            How images, videos, and other media support AI understanding when paired with text.
                        </p>
                        <div class="mt-4 flex items-center gap-2 text-sm text-muted-foreground">
                            <Calendar class="h-4 w-4" />
                            <span>{{ publishedDate }}</span>
                        </div>
                    </header>

                    <!-- Intro with required links -->
                    <section class="mb-12">
                        <p class="text-muted-foreground mb-4">
                            In <Link href="/resources/what-is-geo" class="text-primary hover:underline">Generative Engine Optimization (GEO)</Link>, multimedia plays a supporting role. While AI cannot directly interpret visual content, the context around media matters significantly. Understanding this relationship can help improve your <Link href="/resources/what-is-a-geo-score" class="text-primary hover:underline">GEO Score</Link> through the <Link href="/" class="text-primary hover:underline">GeoSource.ai Platform</Link>.
                        </p>
                    </section>

                    <!-- Definition Section -->
                    <section class="mb-12" aria-labelledby="definition">
                        <h2 id="definition" class="text-2xl font-bold mb-6">Does Multimedia Matter for GEO?</h2>
                        <Card class="border-primary/50 bg-primary/5">
                            <CardContent class="pt-6">
                                <p class="text-lg leading-relaxed">
                                    <strong>Yes — but not in the way people expect.</strong> AI cannot "watch" videos or "see" images — but it can interpret their context. <strong>Captions and transcripts matter most.</strong>
                                </p>
                            </CardContent>
                        </Card>
                    </section>

                    <Separator class="my-12" />

                    <!-- How Multimedia Supports GEO -->
                    <section class="mb-12" aria-labelledby="how-supports">
                        <h2 id="how-supports" class="text-2xl font-bold mb-6">How Multimedia Supports GEO</h2>
                        <p class="text-muted-foreground mb-6">
                            Multimedia helps by:
                        </p>
                        <Card>
                            <CardContent class="pt-6">
                                <ul class="space-y-3">
                                    <li v-for="benefit in multimediaBenefits" :key="benefit" class="flex items-start gap-3">
                                        <CheckCircle class="h-5 w-5 text-green-500 shrink-0 mt-0.5" />
                                        <span>{{ benefit }}</span>
                                    </li>
                                </ul>
                            </CardContent>
                        </Card>
                    </section>

                    <Separator class="my-12" />

                    <!-- Best Practices -->
                    <section class="mb-12" aria-labelledby="best-practices">
                        <h2 id="best-practices" class="text-2xl font-bold mb-6">Best Practices</h2>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <Card v-for="practice in bestPractices" :key="practice">
                                <CardContent class="pt-6">
                                    <div class="flex items-start gap-3">
                                        <CheckCircle class="h-5 w-5 text-green-500 shrink-0 mt-0.5" />
                                        <span class="font-medium">{{ practice }}</span>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </section>

                    <Separator class="my-12" />

                    <!-- Media-Specific Guidelines -->
                    <section class="mb-12" aria-labelledby="media-types">
                        <h2 id="media-types" class="text-2xl font-bold mb-6">Media-Specific Guidelines</h2>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <Card>
                                <CardContent class="pt-6">
                                    <h3 class="font-semibold mb-3 flex items-center gap-2">
                                        <Image class="h-4 w-4 text-primary" />
                                        Images
                                    </h3>
                                    <ul class="space-y-2 text-sm text-muted-foreground">
                                        <li>• Descriptive alt text required</li>
                                        <li>• Use figure/figcaption elements</li>
                                        <li>• Describe content in surrounding text</li>
                                        <li>• Choose images that support the topic</li>
                                    </ul>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardContent class="pt-6">
                                    <h3 class="font-semibold mb-3 flex items-center gap-2">
                                        <Video class="h-4 w-4 text-primary" />
                                        Videos
                                    </h3>
                                    <ul class="space-y-2 text-sm text-muted-foreground">
                                        <li>• Full transcripts essential</li>
                                        <li>• Closed captions improve accessibility</li>
                                        <li>• Summarize key points in text</li>
                                        <li>• Use VideoObject schema</li>
                                    </ul>
                                </CardContent>
                            </Card>
                        </div>
                    </section>

                    <Separator class="my-12" />

                    <!-- What to Avoid -->
                    <section class="mb-12" aria-labelledby="avoid">
                        <h2 id="avoid" class="text-2xl font-bold mb-6">What to Avoid</h2>
                        <Card class="border-red-500/30 bg-red-500/5">
                            <CardContent class="pt-6">
                                <ul class="space-y-3">
                                    <li class="flex items-start gap-3">
                                        <XCircle class="h-5 w-5 text-red-500 shrink-0 mt-0.5" />
                                        <span>Media-only explanations without text</span>
                                    </li>
                                    <li class="flex items-start gap-3">
                                        <XCircle class="h-5 w-5 text-red-500 shrink-0 mt-0.5" />
                                        <span>Missing alt text on images</span>
                                    </li>
                                    <li class="flex items-start gap-3">
                                        <XCircle class="h-5 w-5 text-red-500 shrink-0 mt-0.5" />
                                        <span>Videos without transcripts</span>
                                    </li>
                                    <li class="flex items-start gap-3">
                                        <XCircle class="h-5 w-5 text-red-500 shrink-0 mt-0.5" />
                                        <span>Infographics without text summaries</span>
                                    </li>
                                    <li class="flex items-start gap-3">
                                        <XCircle class="h-5 w-5 text-red-500 shrink-0 mt-0.5" />
                                        <span>Relying on images to convey meaning</span>
                                    </li>
                                </ul>
                            </CardContent>
                        </Card>
                    </section>

                    <Separator class="my-12" />

                    <!-- Text Remains Primary -->
                    <section class="mb-12" aria-labelledby="text-primary">
                        <h2 id="text-primary" class="text-2xl font-bold mb-6">Text Remains Primary</h2>
                        <Card class="border-amber-500/50 bg-amber-500/5">
                            <CardContent class="pt-6">
                                <div class="flex items-start gap-3">
                                    <FileText class="h-6 w-6 text-amber-500 shrink-0 mt-0.5" />
                                    <div>
                                        <p class="font-medium text-foreground">Remember: AI understands text, not pixels.</p>
                                        <p class="text-muted-foreground mt-1">
                                            Every piece of visual content should be accompanied by text that explains its meaning. The text is what AI actually reads and cites.
                                        </p>
                                    </div>
                                </div>
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
                                    Multimedia supports GEO —<br />
                                    but text explains meaning.
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
                        <Link href="/resources/question-coverage-for-geo" class="inline-flex items-center text-muted-foreground hover:text-foreground">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Previous: Question Coverage
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
