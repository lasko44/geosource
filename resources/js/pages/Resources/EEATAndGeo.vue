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
    UserCheck,
    Lightbulb,
    CheckCircle,
    Menu,
    Mail,
    Award,
    Shield,
    BookOpen,
    Target,
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

const eeatSignals = [
    {
        icon: BookOpen,
        title: 'Experience',
        description: 'First-hand explanations, real-world examples, practical guidance, and domain-specific language.',
    },
    {
        icon: Award,
        title: 'Expertise',
        description: 'Consistent topic focus, clear definitions, factual tone, and repeatable terminology.',
    },
    {
        icon: Target,
        title: 'Authoritativeness',
        description: 'Topic cluster coverage, defined terminology, canonical resources, and explained relationships.',
    },
    {
        icon: Shield,
        title: 'Trustworthiness',
        description: 'Stable information, updated content, predictable structure, and minimal contradictions.',
    },
];

const llmSignals = [
    'Consistent topic focus',
    'Clear definitions',
    'Factual tone',
    'Structured explanations',
    'Repeatable terminology',
    'Citation history',
];

const experienceIndicators = [
    'First-hand explanations',
    'Real-world examples',
    'Practical guidance',
    'Domain-specific language',
];

const relatedDefinitions = [
    { slug: 'what-is-geo', title: 'What Is Generative Engine Optimization (GEO)?' },
    { slug: 'what-is-a-geo-score', title: 'What Is a GEO Score?' },
];

const relatedResources = [
    { slug: 'ai-citations-and-geo', title: 'Citations and GEO: How AI Chooses Sources' },
    { slug: 'ai-accessibility-for-geo', title: 'AI Accessibility and GEO' },
    { slug: 'how-llms-cite-sources', title: 'How Large Language Models Choose Which Sources to Cite' },
    { slug: 'content-freshness-for-geo', title: 'Content Freshness and GEO' },
    { slug: 'readability-and-geo', title: 'Readability and GEO' },
];

const jsonLd = {
    '@context': 'https://schema.org',
    '@type': 'Article',
    headline: 'E-E-A-T and Generative Engine Optimization (GEO)',
    description: 'Learn how Experience, Expertise, Authoritativeness, and Trustworthiness (E-E-A-T) signals impact AI citation decisions and how to optimize for them in GEO.',
    url: 'https://geosource.ai/resources/e-e-a-t-and-geo',
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
        '@id': 'https://geosource.ai/resources/e-e-a-t-and-geo',
    },
    about: {
        '@type': 'DefinedTerm',
        name: 'E-E-A-T',
        description: 'Experience, Expertise, Authoritativeness, and Trustworthiness - signals that help AI systems determine source reliability.',
    },
};

const faqJsonLd = {
    '@context': 'https://schema.org',
    '@type': 'FAQPage',
    mainEntity: [
        {
            '@type': 'Question',
            name: 'What is E-E-A-T in the context of GEO?',
            acceptedAnswer: {
                '@type': 'Answer',
                text: 'E-E-A-T stands for Experience, Expertise, Authoritativeness, and Trustworthiness. In GEO, these signals help AI systems determine whether a source is reliable enough to reference in generated answers. Unlike traditional SEO, LLMs infer E-E-A-T through content signals rather than credentials.',
            },
        },
        {
            '@type': 'Question',
            name: 'How do LLMs interpret E-E-A-T signals?',
            acceptedAnswer: {
                '@type': 'Answer',
                text: 'LLMs infer E-E-A-T through signals such as consistent topic focus, clear definitions, factual tone, structured explanations, repeatable terminology, and citation history. GEO strengthens these signals by reducing ambiguity in content.',
            },
        },
        {
            '@type': 'Question',
            name: 'Why does E-E-A-T matter for AI citations?',
            acceptedAnswer: {
                '@type': 'Answer',
                text: 'E-E-A-T in GEO is about confidence, not credentials. The easier your content is to understand and reuse, the more likely AI systems are to cite it. Trust emerges when information is stable, content is updated, structure is predictable, and contradictions are minimal.',
            },
        },
    ],
};
</script>

<template>
    <Head title="E-E-A-T and Generative Engine Optimization (GEO) - GeoSource.ai">
        <meta name="description" content="Learn how Experience, Expertise, Authoritativeness, and Trustworthiness (E-E-A-T) signals impact AI citation decisions and how to optimize for them in GEO." />
        <meta property="og:title" content="E-E-A-T and Generative Engine Optimization (GEO)" />
        <meta property="og:description" content="Learn how E-E-A-T signals impact AI citation decisions and how to optimize for them." />
        <meta property="og:type" content="article" />
        <meta property="og:url" content="https://geosource.ai/resources/e-e-a-t-and-geo" />
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" content="E-E-A-T and Generative Engine Optimization (GEO)" />
        <meta name="twitter:description" content="Learn how E-E-A-T signals impact AI citation decisions." />
        <link rel="canonical" href="https://geosource.ai/resources/e-e-a-t-and-geo" />
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
                        <span class="text-foreground">E-E-A-T and GEO</span>
                    </nav>
                </div>
            </div>

            <!-- Article -->
            <article class="py-12">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <!-- Header -->
                    <header class="mb-12">
                        <Badge variant="secondary" class="mb-4">
                            <UserCheck class="mr-1 h-3 w-3" />
                            GEO Pillar
                        </Badge>
                        <h1 class="text-3xl font-bold tracking-tight sm:text-4xl lg:text-5xl">
                            E-E-A-T and Generative Engine Optimization (GEO)
                        </h1>
                        <p class="mt-4 text-lg text-muted-foreground">
                            How Experience, Expertise, Authoritativeness, and Trustworthiness signals help AI systems determine citation confidence.
                        </p>
                        <div class="mt-4 flex items-center gap-2 text-sm text-muted-foreground">
                            <Calendar class="h-4 w-4" />
                            <span>{{ publishedDate }}</span>
                        </div>
                    </header>

                    <!-- Intro with required links -->
                    <section class="mb-12">
                        <p class="text-muted-foreground mb-4">
                            E-E-A-T plays a critical role in <Link href="/resources/what-is-geo" class="text-primary hover:underline">Generative Engine Optimization (GEO)</Link> because LLMs must determine whether a source is reliable enough to reference in generated answers. Understanding these signals is key to improving your <Link href="/resources/what-is-a-geo-score" class="text-primary hover:underline">GEO Score</Link> and AI visibility through the <Link href="/" class="text-primary hover:underline">GeoSource.ai Platform</Link>.
                        </p>
                    </section>

                    <!-- Definition Section -->
                    <section class="mb-12" aria-labelledby="definition">
                        <h2 id="definition" class="text-2xl font-bold mb-6">What Is E-E-A-T?</h2>
                        <Card class="border-primary/50 bg-primary/5">
                            <CardContent class="pt-6">
                                <p class="text-lg leading-relaxed">
                                    <dfn class="font-semibold not-italic"><strong>E-E-A-T</strong></dfn> stands for <strong>Experience, Expertise, Authoritativeness, and Trustworthiness</strong>. While originally associated with traditional SEO, E-E-A-T plays a critical role in Generative Engine Optimization (GEO) because LLMs must determine whether a source is reliable enough to reference in generated answers.
                                </p>
                            </CardContent>
                        </Card>
                    </section>

                    <Separator class="my-12" />

                    <!-- How LLMs Interpret E-E-A-T -->
                    <section class="mb-12" aria-labelledby="llm-interpretation">
                        <h2 id="llm-interpretation" class="text-2xl font-bold mb-6">How LLMs Interpret E-E-A-T</h2>
                        <p class="text-muted-foreground mb-6">
                            LLMs do not evaluate trust the way humans do. Instead, they infer E-E-A-T through signals such as:
                        </p>
                        <Card>
                            <CardContent class="pt-6">
                                <ul class="space-y-3">
                                    <li v-for="signal in llmSignals" :key="signal" class="flex items-start gap-3">
                                        <CheckCircle class="h-5 w-5 text-green-500 shrink-0 mt-0.5" />
                                        <span>{{ signal }}</span>
                                    </li>
                                </ul>
                            </CardContent>
                        </Card>
                        <Card class="mt-6 border-amber-500/50 bg-amber-500/5">
                            <CardContent class="pt-6">
                                <div class="flex items-start gap-3">
                                    <Lightbulb class="h-6 w-6 text-amber-500 shrink-0 mt-0.5" />
                                    <p class="text-muted-foreground">
                                        <strong class="text-foreground">GEO strengthens these signals by reducing ambiguity.</strong> Clear, structured content makes it easier for AI to assess reliability.
                                    </p>
                                </div>
                            </CardContent>
                        </Card>
                    </section>

                    <Separator class="my-12" />

                    <!-- Four Pillars -->
                    <section class="mb-12" aria-labelledby="four-pillars">
                        <h2 id="four-pillars" class="text-2xl font-bold mb-6">The Four Pillars of E-E-A-T in GEO</h2>
                        <div class="grid gap-6 sm:grid-cols-2">
                            <Card v-for="pillar in eeatSignals" :key="pillar.title" class="h-full">
                                <CardContent class="pt-6">
                                    <div class="flex items-start gap-4">
                                        <div class="rounded-lg bg-primary/10 p-2">
                                            <component :is="pillar.icon" class="h-5 w-5 text-primary" />
                                        </div>
                                        <div>
                                            <h3 class="font-semibold">{{ pillar.title }}</h3>
                                            <p class="mt-1 text-sm text-muted-foreground">{{ pillar.description }}</p>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </section>

                    <Separator class="my-12" />

                    <!-- Experience in GEO -->
                    <section class="mb-12" aria-labelledby="experience">
                        <h2 id="experience" class="text-2xl font-bold mb-6">Experience in GEO</h2>
                        <p class="text-muted-foreground mb-6">
                            AI systems look for demonstrated experience, not claims. Content written from lived experience improves citation confidence.
                        </p>
                        <Card>
                            <CardContent class="pt-6">
                                <h3 class="font-semibold mb-4">Experience Indicators</h3>
                                <ul class="space-y-3">
                                    <li v-for="indicator in experienceIndicators" :key="indicator" class="flex items-start gap-3">
                                        <CheckCircle class="h-5 w-5 text-green-500 shrink-0 mt-0.5" />
                                        <span>{{ indicator }}</span>
                                    </li>
                                </ul>
                            </CardContent>
                        </Card>
                    </section>

                    <Separator class="my-12" />

                    <!-- Expertise and Authority -->
                    <section class="mb-12" aria-labelledby="expertise">
                        <h2 id="expertise" class="text-2xl font-bold mb-6">Expertise and Authority</h2>
                        <p class="text-muted-foreground mb-6">
                            Authority is reinforced when a site:
                        </p>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <Card>
                                <CardContent class="pt-6">
                                    <ul class="space-y-2 text-sm">
                                        <li class="flex items-start gap-2">
                                            <CheckCircle class="h-4 w-4 text-green-500 shrink-0 mt-0.5" />
                                            <span>Consistently covers a topic cluster</span>
                                        </li>
                                        <li class="flex items-start gap-2">
                                            <CheckCircle class="h-4 w-4 text-green-500 shrink-0 mt-0.5" />
                                            <span>Defines terminology clearly</span>
                                        </li>
                                    </ul>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardContent class="pt-6">
                                    <ul class="space-y-2 text-sm">
                                        <li class="flex items-start gap-2">
                                            <CheckCircle class="h-4 w-4 text-green-500 shrink-0 mt-0.5" />
                                            <span>Explains relationships between concepts</span>
                                        </li>
                                        <li class="flex items-start gap-2">
                                            <CheckCircle class="h-4 w-4 text-green-500 shrink-0 mt-0.5" />
                                            <span>Publishes canonical resources</span>
                                        </li>
                                    </ul>
                                </CardContent>
                            </Card>
                        </div>
                        <p class="mt-6 text-muted-foreground">
                            This is why <Link href="/definitions" class="text-primary hover:underline">definition hubs</Link> matter in GEO.
                        </p>
                    </section>

                    <Separator class="my-12" />

                    <!-- Trust and AI Confidence -->
                    <section class="mb-12" aria-labelledby="trust">
                        <h2 id="trust" class="text-2xl font-bold mb-6">Trust and AI Confidence</h2>
                        <p class="text-muted-foreground mb-6">
                            Trust emerges when:
                        </p>
                        <Card>
                            <CardContent class="pt-6">
                                <ul class="space-y-3">
                                    <li class="flex items-start gap-3">
                                        <CheckCircle class="h-5 w-5 text-green-500 shrink-0 mt-0.5" />
                                        <span>Information is stable</span>
                                    </li>
                                    <li class="flex items-start gap-3">
                                        <CheckCircle class="h-5 w-5 text-green-500 shrink-0 mt-0.5" />
                                        <span>Content is updated regularly</span>
                                    </li>
                                    <li class="flex items-start gap-3">
                                        <CheckCircle class="h-5 w-5 text-green-500 shrink-0 mt-0.5" />
                                        <span>Structure is predictable</span>
                                    </li>
                                    <li class="flex items-start gap-3">
                                        <CheckCircle class="h-5 w-5 text-green-500 shrink-0 mt-0.5" />
                                        <span>Contradictions are minimal</span>
                                    </li>
                                </ul>
                            </CardContent>
                        </Card>
                        <p class="mt-6 text-muted-foreground">
                            <strong class="text-foreground">GEO increases trust by making meaning explicit.</strong>
                        </p>
                    </section>

                    <Separator class="my-12" />

                    <!-- Key Takeaway -->
                    <section class="mb-12" aria-labelledby="takeaway">
                        <h2 id="takeaway" class="text-2xl font-bold mb-6">Key Takeaway</h2>
                        <Card class="border-primary bg-primary/5">
                            <CardContent class="pt-6">
                                <p class="text-xl font-medium text-center">
                                    E-E-A-T in GEO is about confidence, not credentials.<br />
                                    The easier your content is to understand and reuse, the more likely AI systems are to cite it.
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
                        <Link href="/resources" class="inline-flex items-center text-muted-foreground hover:text-foreground">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Back to Resources
                        </Link>
                        <Link href="/resources/ai-citations-and-geo" class="inline-flex items-center text-primary hover:underline">
                            Next: Citations and GEO
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
