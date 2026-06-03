<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import SkipNav from '@/components/resources/SkipNav.vue';
import ResourceHeader from '@/components/resources/ResourceHeader.vue';
import ResourceFooter from '@/components/resources/ResourceFooter.vue';
import {
    BookOpen,
    ArrowRight,
    Brain,
    Scale,
    Search,
    Quote,
    BarChart3,
    FileText,
    Eye,
    CheckSquare,
    Library,
    Server,
    UserCheck,
    Bot,
    Clock,
    Type,
    HelpCircle,
    Image,
    Calendar,
    Layers,
    Monitor,
    Sparkles,
    Compass,
    Microscope,
    AlertCircle,
} from 'lucide-vue-next';

const props = defineProps<{
    industries: { slug: string; name: string; title: string; meta_description: string; published_at: string; updated_at: string }[];
    platforms: { slug: string; name: string; title: string; meta_description: string; published_at: string; updated_at: string }[];
    comparisons: { slug: string; title: string; meta_description: string; published_at: string; updated_at: string }[];
    useCases: { slug: string; title: string; meta_description: string; published_at: string; updated_at: string }[];
}>();

const formatDate = (dateStr: string): string => {
    const d = new Date(dateStr + 'T00:00:00');
    return d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
};

// The single recommended starting point — featured prominently above
// everything else. New visitors should land here.
const startHere = {
    href: '/resources/what-is-geo',
    title: 'What is Generative Engine Optimization?',
    description: 'The definition, the core principles, and what changes about content strategy when AI assistants become the discovery layer. Start here if you are new to GEO.',
};

// Foundational reads — every GEO practitioner should read these before
// diving into pillar-specific or platform-specific material.
const foundations = [
    {
        slug: 'geo-vs-seo',
        title: 'GEO vs SEO',
        description: 'What changes when the optimization target is an AI assistant instead of a search ranking algorithm.',
        icon: Scale,
    },
    {
        slug: 'how-ai-search-works',
        title: 'How AI search engines work',
        description: 'The mechanics — RAG, vector embeddings, source selection — explained at a strategist level.',
        icon: Search,
    },
    {
        slug: 'how-llms-cite-sources',
        title: 'How LLMs choose sources to cite',
        description: 'What signals AI platforms actually use when deciding which pages to quote.',
        icon: Quote,
    },
    {
        slug: 'what-is-a-geo-score',
        title: 'What is a GEO Score?',
        description: 'The composite metric GeoSource uses to measure how citation-ready a page is.',
        icon: BarChart3,
    },
];

// Practical artifacts — checklists, frameworks, and the glossary. These
// are what most people are looking for when they say "show me what to do."
type ToolItem = {
    href: string;
    title: string;
    description: string;
    icon: any;
    underReview?: boolean;
    reviewNote?: string;
};

const tools: ToolItem[] = [
    {
        href: '/definitions',
        title: 'GEO Definitions',
        description: 'Authoritative glossary of GEO terminology.',
        icon: Library,
    },
    {
        href: '/geo-score-explained',
        title: 'GEO Score Explained',
        description: 'A deep dive on how the scoring engine evaluates a page.',
        icon: BarChart3,
        underReview: true,
        reviewNote: 'Some pillar weights described here predate our recent research and are being revised.',
    },
    {
        href: '/geo-optimization-checklist',
        title: 'Optimization Checklist',
        description: 'Page-by-page checklist for getting your content cite-ready, rebuilt around the signals our research validated.',
        icon: CheckSquare,
    },
    {
        href: '/ai-search-visibility-guide',
        title: 'AI Search Visibility Guide',
        description: 'End-to-end guide on what makes content visible to AI assistants.',
        icon: Eye,
    },
];

// Pillar-by-pillar deep-dives, split into two tracks: ones our research
// validated as drivers of AI citation, and ones our research found are
// either flat predictors or actively misleading in some contexts. The
// "under review" pages are kept for SEO continuity until rewritten.
type PillarItem = {
    slug: string;
    title: string;
    description: string;
    icon: any;
    underReview?: boolean;
    reviewNote?: string;
};

const drivingPillars: PillarItem[] = [
    {
        slug: 'ai-citations-and-geo',
        title: 'Citations and GEO',
        description: 'How citing authoritative external sources in your own content raises your citation rate. A proven positive predictor in our research.',
        icon: Quote,
    },
    {
        slug: 'readability-and-geo',
        title: 'Readability and GEO',
        description: 'Clear, structured writing for AI comprehension. Neutral-to-positive direction in our studies.',
        icon: Type,
    },
    {
        slug: 'ai-accessibility-for-geo',
        title: 'AI Accessibility for GEO',
        description: 'Make sure crawlers and LLMs can actually reach and parse your content. A baseline requirement.',
        icon: Bot,
    },
    {
        slug: 'content-freshness-for-geo',
        title: 'Content Freshness',
        description: 'How recency affects AI visibility. A mild positive in our data.',
        icon: Clock,
    },
];

const technicalPillars: PillarItem[] = [
    {
        slug: 'why-llms-txt-matters',
        title: 'Why llms.txt matters',
        description: 'How an llms.txt file helps AI assistants discover and parse your site.',
        icon: FileText,
    },
    {
        slug: 'why-ssr-matters-for-geo',
        title: 'Why SSR matters for GEO',
        description: 'Server-side rendering and AI visibility — what LLMs actually crawl.',
        icon: Server,
    },
];

// Research-backed reframings — pages where our studies overturned the
// conventional wisdom. Kept as their own group so readers see them as
// findings, not as "deprecated."
const reframedPillars: PillarItem[] = [
    {
        slug: 'e-e-a-t-and-geo',
        title: 'E-E-A-T and GEO',
        description: 'Why visible E-E-A-T signals did not predict AI citation in our research, and what AI assistants reward instead.',
        icon: UserCheck,
    },
    {
        slug: 'multimedia-and-geo',
        title: 'Multimedia and GEO',
        description: 'Neutral for informational content, a drag on commercial pages. Accessibility-driven media still earns its keep.',
        icon: Image,
    },
    {
        slug: 'question-coverage-for-geo',
        title: 'Question Coverage and GEO',
        description: 'Listing questions does not get you cited. Answering them clearly, at the top, does. Reframed around Answerability.',
        icon: HelpCircle,
    },
];

const underReviewPillars: PillarItem[] = [
    {
        slug: 'geo-content-framework',
        title: 'GEO Content Framework',
        description: 'A four-pillar content framework for AI-ready pages.',
        icon: FileText,
        underReview: true,
        reviewNote: 'The original framework treats Topic Authority as a primary pillar; our research showed it is a weak/negative predictor. This page is being revised.',
    },
];

const allItemListElements = [
    { '@type': 'ListItem' as const, position: 1, name: startHere.title, url: `https://geosource.ai${startHere.href}` },
    ...foundations.map((a, i) => ({ '@type': 'ListItem' as const, position: 2 + i, name: a.title, url: `https://geosource.ai/resources/${a.slug}` })),
    ...tools.map((a, i) => ({ '@type': 'ListItem' as const, position: 6 + i, name: a.title, url: `https://geosource.ai${a.href}` })),
    ...drivingPillars.map((a, i) => ({ '@type': 'ListItem' as const, position: 10 + i, name: a.title, url: `https://geosource.ai/resources/${a.slug}` })),
];

const jsonLd = {
    '@context': 'https://schema.org',
    '@type': 'CollectionPage',
    name: 'Learn GEO',
    description: 'Foundational guides, pillar deep-dives, and platform-specific strategies for Generative Engine Optimization.',
    url: 'https://geosource.ai/resources',
    publisher: {
        '@type': 'Organization',
        name: 'GeoSource.ai',
        url: 'https://geosource.ai',
    },
    mainEntity: {
        '@type': 'ItemList',
        itemListElement: allItemListElements,
    },
};
</script>

<template>
    <Head title="Learn GEO - GeoSource.ai">
        <meta name="description" content="Foundational guides, pillar deep-dives, and platform-specific strategies for optimizing content for AI search engines like ChatGPT, Perplexity, and Claude." />
        <meta property="og:title" content="Learn GEO - GeoSource.ai" />
        <meta property="og:description" content="Foundational guides, pillar deep-dives, and platform-specific strategies for optimizing content for AI search engines." />
        <meta property="og:type" content="website" />
        <meta property="og:url" content="https://geosource.ai/resources" />
        <meta property="og:site_name" content="GeoSource.ai" />
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" content="Learn GEO - GeoSource.ai" />
        <meta name="twitter:description" content="Foundational guides, pillar deep-dives, and platform-specific strategies for optimizing content for AI search engines." />
        <meta name="twitter:site" content="@geosourceai" />
        <meta name="robots" content="index, follow" />
        <link rel="canonical" href="https://geosource.ai/resources" />
        <component :is="'script'" type="application/ld+json">{{ JSON.stringify(jsonLd) }}</component>
    </Head>

    <div class="min-h-screen bg-background text-foreground">
        <SkipNav />
        <ResourceHeader />

        <main id="main-content" role="main">
            <!-- Hero -->
            <section class="border-b bg-gradient-to-b from-primary/5 to-background py-14 sm:py-20">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <Badge variant="secondary" class="mb-4">
                        <BookOpen class="mr-1.5 h-3 w-3" aria-hidden="true" />
                        Learn GEO
                    </Badge>
                    <h1 class="text-3xl font-bold tracking-tight sm:text-4xl lg:text-5xl">
                        Learn how to make content AI assistants will cite
                    </h1>
                    <p class="mt-4 max-w-3xl text-base text-muted-foreground sm:text-lg leading-relaxed">
                        Foundational guides, frameworks, and pillar deep-dives. For original studies and data from our research line, see <Link href="/research" class="text-primary font-medium hover:underline">Research</Link>.
                    </p>
                </div>
            </section>

            <!-- Start here -->
            <section aria-labelledby="start-here-heading" class="py-12 sm:py-16">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <div class="mb-6 flex items-center gap-2">
                        <Compass class="h-5 w-5 text-primary" aria-hidden="true" />
                        <h2 id="start-here-heading" class="text-sm font-semibold uppercase tracking-wide text-primary">Start here</h2>
                    </div>
                    <Link
                        :href="startHere.href"
                        class="block group focus:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 rounded-lg"
                        :aria-label="`${startHere.title}: ${startHere.description}`"
                    >
                        <Card class="border-2 border-primary/40 bg-gradient-to-br from-primary/5 to-transparent transition-shadow hover:shadow-lg">
                            <CardContent class="p-6 sm:p-8">
                                <Badge variant="secondary" class="mb-3">Foundation</Badge>
                                <h3 class="text-2xl font-bold leading-tight group-hover:text-primary transition-colors">
                                    {{ startHere.title }}
                                </h3>
                                <p class="mt-3 text-muted-foreground leading-relaxed">
                                    {{ startHere.description }}
                                </p>
                                <span class="mt-4 inline-flex items-center gap-1 text-sm font-medium text-primary">
                                    Read the introduction <ArrowRight class="h-3.5 w-3.5" aria-hidden="true" />
                                </span>
                            </CardContent>
                        </Card>
                    </Link>
                </div>
            </section>

            <!-- Foundations -->
            <section aria-labelledby="foundations-heading" class="border-t py-12 sm:py-16">
                <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                    <div class="mb-6 flex items-center justify-between gap-4">
                        <h2 id="foundations-heading" class="text-2xl font-bold tracking-tight sm:text-3xl">
                            Foundations
                        </h2>
                        <p class="hidden sm:block text-sm text-muted-foreground">Read these before the pillar deep-dives.</p>
                    </div>
                    <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4" role="list">
                        <li v-for="a in foundations" :key="a.slug">
                            <Link
                                :href="`/resources/${a.slug}`"
                                class="block group h-full focus:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 rounded-lg"
                                :aria-label="`${a.title}: ${a.description}`"
                            >
                                <Card class="h-full transition-colors hover:border-primary/50">
                                    <CardContent class="p-5">
                                        <component :is="a.icon" class="h-6 w-6 text-primary mb-3" aria-hidden="true" />
                                        <h3 class="font-semibold leading-snug group-hover:text-primary transition-colors">{{ a.title }}</h3>
                                        <p class="mt-2 text-sm text-muted-foreground leading-relaxed">{{ a.description }}</p>
                                    </CardContent>
                                </Card>
                            </Link>
                        </li>
                    </ul>
                </div>
            </section>

            <!-- Frameworks & tools -->
            <section aria-labelledby="tools-heading" class="border-t bg-muted/30 py-12 sm:py-16">
                <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                    <div class="mb-6">
                        <h2 id="tools-heading" class="text-2xl font-bold tracking-tight sm:text-3xl">
                            Frameworks &amp; tools
                        </h2>
                        <p class="mt-2 text-sm text-muted-foreground">Practical artifacts — the glossary, the scoring deep-dive, and our optimization checklist.</p>
                    </div>
                    <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4" role="list">
                        <li v-for="t in tools" :key="t.href">
                            <Link
                                :href="t.href"
                                class="block group h-full focus:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 rounded-lg"
                                :aria-label="`${t.title}: ${t.description}`"
                            >
                                <Card class="h-full transition-colors hover:border-primary/50">
                                    <CardContent class="p-5">
                                        <div class="flex items-start justify-between gap-2 mb-3">
                                            <component :is="t.icon" class="h-6 w-6 text-primary" aria-hidden="true" />
                                            <Badge v-if="t.underReview" variant="outline" class="border-amber-500/50 text-amber-600 dark:text-amber-400 text-[10px]">
                                                <AlertCircle class="h-3 w-3 mr-1" aria-hidden="true" />
                                                Under review
                                            </Badge>
                                        </div>
                                        <h3 class="font-semibold leading-snug group-hover:text-primary transition-colors">{{ t.title }}</h3>
                                        <p class="mt-2 text-sm text-muted-foreground leading-relaxed">{{ t.description }}</p>
                                        <p v-if="t.underReview && t.reviewNote" class="mt-2 text-xs text-amber-700 dark:text-amber-400 leading-relaxed">
                                            {{ t.reviewNote }}
                                        </p>
                                    </CardContent>
                                </Card>
                            </Link>
                        </li>
                    </ul>
                </div>
            </section>

            <!-- Pillar deep-dives -->
            <section aria-labelledby="pillars-heading" class="border-t py-12 sm:py-16">
                <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                    <div class="mb-6">
                        <h2 id="pillars-heading" class="text-2xl font-bold tracking-tight sm:text-3xl">
                            Pillar deep-dives
                        </h2>
                        <p class="mt-2 text-sm text-muted-foreground max-w-3xl leading-relaxed">
                            Detailed guides on each pillar in the GEO model. Pillars are grouped by what our research validated. Pages marked "Under review" are being rewritten to reflect findings from our recent studies.
                        </p>
                    </div>

                    <!-- Validated drivers -->
                    <div class="mb-10">
                        <h3 class="text-sm font-semibold uppercase tracking-wide text-green-600 dark:text-green-400 mb-3">Validated drivers</h3>
                        <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4" role="list">
                            <li v-for="p in drivingPillars" :key="p.slug">
                                <Link
                                    :href="`/resources/${p.slug}`"
                                    class="block group h-full focus:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 rounded-lg"
                                    :aria-label="`${p.title}: ${p.description}`"
                                >
                                    <Card class="h-full border-l-2 border-l-green-500/40 transition-colors hover:border-primary/50">
                                        <CardContent class="p-5">
                                            <component :is="p.icon" class="h-5 w-5 text-primary mb-3" aria-hidden="true" />
                                            <h4 class="font-semibold leading-snug group-hover:text-primary transition-colors">{{ p.title }}</h4>
                                            <p class="mt-2 text-sm text-muted-foreground leading-relaxed">{{ p.description }}</p>
                                        </CardContent>
                                    </Card>
                                </Link>
                            </li>
                        </ul>
                    </div>

                    <!-- Technical baseline -->
                    <div class="mb-10">
                        <h3 class="text-sm font-semibold uppercase tracking-wide text-muted-foreground mb-3">Technical baseline</h3>
                        <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4" role="list">
                            <li v-for="p in technicalPillars" :key="p.slug">
                                <Link
                                    :href="`/resources/${p.slug}`"
                                    class="block group h-full focus:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 rounded-lg"
                                    :aria-label="`${p.title}: ${p.description}`"
                                >
                                    <Card class="h-full transition-colors hover:border-primary/50">
                                        <CardContent class="p-5">
                                            <component :is="p.icon" class="h-5 w-5 text-primary mb-3" aria-hidden="true" />
                                            <h4 class="font-semibold leading-snug group-hover:text-primary transition-colors">{{ p.title }}</h4>
                                            <p class="mt-2 text-sm text-muted-foreground leading-relaxed">{{ p.description }}</p>
                                        </CardContent>
                                    </Card>
                                </Link>
                            </li>
                        </ul>
                    </div>

                    <!-- Research-backed reframings -->
                    <div v-if="reframedPillars.length" class="mb-10">
                        <h3 class="text-sm font-semibold uppercase tracking-wide text-blue-600 dark:text-blue-400 mb-3 flex items-center gap-2">
                            <Microscope class="h-3.5 w-3.5" aria-hidden="true" />
                            Research-backed corrections
                        </h3>
                        <p class="text-sm text-muted-foreground mb-4 max-w-3xl">
                            These pages explain what our studies found about pillars where the conventional advice doesn't match the data. Read these to learn what to do <em>instead</em>.
                        </p>
                        <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4" role="list">
                            <li v-for="p in reframedPillars" :key="p.slug">
                                <Link
                                    :href="`/resources/${p.slug}`"
                                    class="block group h-full focus:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 rounded-lg"
                                    :aria-label="`${p.title}: ${p.description}`"
                                >
                                    <Card class="h-full border-l-2 border-l-blue-500/40 transition-colors hover:border-primary/50">
                                        <CardContent class="p-5">
                                            <component :is="p.icon" class="h-5 w-5 text-primary mb-3" aria-hidden="true" />
                                            <h4 class="font-semibold leading-snug group-hover:text-primary transition-colors">{{ p.title }}</h4>
                                            <p class="mt-2 text-sm text-muted-foreground leading-relaxed">{{ p.description }}</p>
                                        </CardContent>
                                    </Card>
                                </Link>
                            </li>
                        </ul>
                    </div>

                    <!-- Under review -->
                    <div v-if="underReviewPillars.length">
                        <h3 class="text-sm font-semibold uppercase tracking-wide text-amber-600 dark:text-amber-400 mb-3 flex items-center gap-2">
                            <AlertCircle class="h-3.5 w-3.5" aria-hidden="true" />
                            Under review — being rewritten
                        </h3>
                        <p class="text-sm text-muted-foreground mb-4 max-w-3xl">
                            These guides predate our recent research and contain claims we have not been able to validate. They remain accessible while we revise them. See our <Link href="/research" class="text-primary hover:underline">research</Link> for the current findings.
                        </p>
                        <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4" role="list">
                            <li v-for="p in underReviewPillars" :key="p.slug">
                                <Link
                                    :href="`/resources/${p.slug}`"
                                    class="block group h-full focus:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 rounded-lg"
                                    :aria-label="`${p.title}: ${p.description}`"
                                >
                                    <Card class="h-full border-l-2 border-l-amber-500/40 bg-amber-50/30 dark:bg-amber-950/10 transition-colors hover:border-primary/50">
                                        <CardContent class="p-5">
                                            <div class="flex items-start justify-between gap-2 mb-3">
                                                <component :is="p.icon" class="h-5 w-5 text-primary" aria-hidden="true" />
                                                <Badge variant="outline" class="border-amber-500/50 text-amber-600 dark:text-amber-400 text-[10px]">
                                                    Under review
                                                </Badge>
                                            </div>
                                            <h4 class="font-semibold leading-snug group-hover:text-primary transition-colors">{{ p.title }}</h4>
                                            <p class="mt-2 text-sm text-muted-foreground leading-relaxed">{{ p.description }}</p>
                                            <p v-if="p.reviewNote" class="mt-2 text-xs text-amber-700 dark:text-amber-400 leading-relaxed">
                                                {{ p.reviewNote }}
                                            </p>
                                        </CardContent>
                                    </Card>
                                </Link>
                            </li>
                        </ul>
                    </div>
                </div>
            </section>

            <!-- Industry guides (dynamic) -->
            <section v-if="industries.length" aria-labelledby="industries-heading" class="border-t bg-muted/30 py-12 sm:py-16">
                <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                    <div class="mb-6">
                        <h2 id="industries-heading" class="text-2xl font-bold tracking-tight sm:text-3xl">GEO by industry</h2>
                        <p class="mt-2 text-sm text-muted-foreground">Industry-specific guides to GEO. Pick the one that matches your business.</p>
                    </div>
                    <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3" role="list">
                        <li v-for="industry in industries" :key="industry.slug">
                            <Link
                                :href="`/geo-for-${industry.slug}`"
                                class="block group h-full focus:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 rounded-lg"
                                :aria-label="`${industry.name}: ${industry.meta_description}`"
                            >
                                <Card class="h-full transition-colors hover:border-primary/50">
                                    <CardContent class="p-5">
                                        <div class="flex items-start justify-between gap-2 mb-3">
                                            <Layers class="h-6 w-6 text-primary" aria-hidden="true" />
                                            <Badge variant="outline" class="text-[10px]">Industry</Badge>
                                        </div>
                                        <h3 class="font-semibold leading-snug group-hover:text-primary transition-colors">{{ industry.name }}</h3>
                                        <p class="mt-2 text-sm text-muted-foreground leading-relaxed">{{ industry.meta_description }}</p>
                                    </CardContent>
                                </Card>
                            </Link>
                        </li>
                    </ul>
                </div>
            </section>

            <!-- Platform guides (dynamic) -->
            <section v-if="platforms.length" aria-labelledby="platforms-heading" class="border-t py-12 sm:py-16">
                <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                    <div class="mb-6">
                        <h2 id="platforms-heading" class="text-2xl font-bold tracking-tight sm:text-3xl">Optimize for each AI platform</h2>
                        <p class="mt-2 text-sm text-muted-foreground">Per-platform notes — though our research showed all major platforms behave more similarly than not.</p>
                    </div>
                    <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3" role="list">
                        <li v-for="platform in platforms" :key="platform.slug">
                            <Link
                                :href="`/optimize-for-${platform.slug}`"
                                class="block group h-full focus:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 rounded-lg"
                                :aria-label="`${platform.name}: ${platform.meta_description}`"
                            >
                                <Card class="h-full transition-colors hover:border-primary/50">
                                    <CardContent class="p-5">
                                        <div class="flex items-start justify-between gap-2 mb-3">
                                            <Monitor class="h-6 w-6 text-primary" aria-hidden="true" />
                                            <Badge variant="outline" class="text-[10px]">Platform</Badge>
                                        </div>
                                        <h3 class="font-semibold leading-snug group-hover:text-primary transition-colors">{{ platform.name }}</h3>
                                        <p class="mt-2 text-sm text-muted-foreground leading-relaxed">{{ platform.meta_description }}</p>
                                    </CardContent>
                                </Card>
                            </Link>
                        </li>
                    </ul>
                </div>
            </section>

            <!-- Comparisons (dynamic) -->
            <section v-if="comparisons.length" aria-labelledby="comparisons-heading" class="border-t bg-muted/30 py-12 sm:py-16">
                <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                    <div class="mb-6">
                        <h2 id="comparisons-heading" class="text-2xl font-bold tracking-tight sm:text-3xl">Tool comparisons</h2>
                        <p class="mt-2 text-sm text-muted-foreground">How GEO compares to traditional SEO tools.</p>
                    </div>
                    <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3" role="list">
                        <li v-for="comparison in comparisons" :key="comparison.slug">
                            <Link
                                :href="`/compare/${comparison.slug}`"
                                class="block group h-full focus:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 rounded-lg"
                                :aria-label="`${comparison.title}: ${comparison.meta_description}`"
                            >
                                <Card class="h-full transition-colors hover:border-primary/50">
                                    <CardContent class="p-5">
                                        <div class="flex items-start justify-between gap-2 mb-3">
                                            <Scale class="h-6 w-6 text-primary" aria-hidden="true" />
                                            <Badge variant="outline" class="text-[10px]">Comparison</Badge>
                                        </div>
                                        <h3 class="font-semibold leading-snug group-hover:text-primary transition-colors">{{ comparison.title }}</h3>
                                        <p class="mt-2 text-sm text-muted-foreground leading-relaxed">{{ comparison.meta_description }}</p>
                                    </CardContent>
                                </Card>
                            </Link>
                        </li>
                    </ul>
                </div>
            </section>

            <!-- How-To Guides (dynamic) -->
            <section v-if="useCases.length" aria-labelledby="use-cases-heading" class="border-t py-12 sm:py-16">
                <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                    <div class="mb-6">
                        <h2 id="use-cases-heading" class="text-2xl font-bold tracking-tight sm:text-3xl">How-to guides</h2>
                        <p class="mt-2 text-sm text-muted-foreground">Step-by-step walkthroughs for common GEO tasks.</p>
                    </div>
                    <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3" role="list">
                        <li v-for="useCase in useCases" :key="useCase.slug">
                            <Link
                                :href="`/how-to/${useCase.slug}`"
                                class="block group h-full focus:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 rounded-lg"
                                :aria-label="`${useCase.title}: ${useCase.meta_description}`"
                            >
                                <Card class="h-full transition-colors hover:border-primary/50">
                                    <CardContent class="p-5">
                                        <div class="flex items-start justify-between gap-2 mb-3">
                                            <Sparkles class="h-6 w-6 text-primary" aria-hidden="true" />
                                            <Badge variant="outline" class="text-[10px]">How-to</Badge>
                                        </div>
                                        <h3 class="font-semibold leading-snug group-hover:text-primary transition-colors">{{ useCase.title }}</h3>
                                        <p class="mt-2 text-sm text-muted-foreground leading-relaxed">{{ useCase.meta_description }}</p>
                                    </CardContent>
                                </Card>
                            </Link>
                        </li>
                    </ul>
                </div>
            </section>

            <!-- Research pointer -->
            <section aria-labelledby="research-pointer-heading" class="border-t py-12 sm:py-16">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <Card class="border-l-4 border-l-primary">
                        <CardContent class="p-6 sm:p-8">
                            <div class="flex items-start gap-4">
                                <Microscope class="h-6 w-6 text-primary mt-1 shrink-0" aria-hidden="true" />
                                <div>
                                    <h2 id="research-pointer-heading" class="text-xl font-semibold">Looking for the underlying research?</h2>
                                    <p class="mt-2 text-muted-foreground leading-relaxed">
                                        Most of these guides reference findings from our four-study research line. The studies themselves — methodology, raw data, and what each finding means in practice — live on our Research page.
                                    </p>
                                    <Link href="/research" class="mt-3 inline-flex items-center gap-1 text-sm font-medium text-primary hover:underline">
                                        Browse research <ArrowRight class="h-3.5 w-3.5" aria-hidden="true" />
                                    </Link>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </section>

            <!-- CTA -->
            <section aria-labelledby="cta-heading" class="border-t py-12 sm:py-16">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <div class="mx-auto max-w-2xl text-center">
                        <h2 id="cta-heading" class="text-2xl font-bold tracking-tight sm:text-3xl">
                            Ready to measure your GEO readiness?
                        </h2>
                        <p class="mt-4 text-lg text-muted-foreground">
                            Scan any URL and get your Citation Readiness Score plus actionable next steps.
                        </p>
                        <div class="mt-8">
                            <Link href="/register" class="inline-block focus:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 rounded-md">
                                <Button size="lg" class="gap-2">
                                    Start Free Analysis
                                    <ArrowRight class="h-4 w-4" aria-hidden="true" />
                                </Button>
                            </Link>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <ResourceFooter />
    </div>
</template>
