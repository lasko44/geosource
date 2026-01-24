<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import {
    BookOpen,
    ArrowRight,
    CheckCircle,
    Quote,
    Brain,
    BarChart3,
    Eye,
    FileCheck,
    Database,
    Search,
} from 'lucide-vue-next';
import SkipNav from '@/components/resources/SkipNav.vue';
import ResourceHeader from '@/components/resources/ResourceHeader.vue';
import ResourceFooter from '@/components/resources/ResourceFooter.vue';
import ResourceBreadcrumb from '@/components/resources/ResourceBreadcrumb.vue';

const breadcrumbItems = [
    { label: 'Home', href: '/' },
    { label: 'Resources', href: '/resources' },
    { label: 'GEO Definitions' },
];

const definitions = [
    {
        id: 'geo',
        term: 'Generative Engine Optimization (GEO)',
        icon: Brain,
        definition: 'Generative Engine Optimization (GEO) is the process of structuring and publishing digital content so that generative AI systems — such as ChatGPT, Google AI Overviews, Perplexity, and Claude — can clearly understand, trust, and cite it when generating answers.',
        details: [
            'Unlike traditional SEO, which optimizes pages to rank in search results, GEO optimizes content for AI comprehension and citation selection.',
            'GEO focuses on clarity, structure, topical authority, and machine-readable formatting rather than keywords or backlinks.',
        ],
        related: [
            { title: 'What Is GEO', href: '/resources/what-is-geo' },
            { title: 'GEO Score Explained', href: '/geo-score-explained' },
            { title: 'AI Search Visibility Guide', href: '/ai-search-visibility-guide' },
        ],
    },
    {
        id: 'geo-score',
        term: 'GEO Score',
        icon: BarChart3,
        definition: 'A GEO Score is a quantitative measurement of how well a website or webpage is optimized for generative AI understanding and citation.',
        details: [
            'A GEO Score evaluates whether content can be confidently used by AI systems to produce accurate, reliable answers.',
            'Unlike SEO scores, a GEO Score does not measure rankings or traffic. It measures citation readiness.',
        ],
        factors: [
            'Structured knowledge clarity',
            'Explicit definitions',
            'Topic hierarchy',
            'Entity consistency',
            'FAQ coverage',
            'Machine-readable formatting',
        ],
        related: [
            { title: 'What Is a GEO Score?', href: '/resources/what-is-a-geo-score' },
            { title: 'GEO Score Explained', href: '/geo-score-explained' },
        ],
    },
    {
        id: 'ai-visibility',
        term: 'AI Visibility',
        icon: Eye,
        definition: 'AI Visibility refers to how frequently and accurately a brand, website, or source appears within generative AI responses across platforms such as ChatGPT, Gemini, Claude, and Perplexity.',
        details: [
            'High AI visibility means a source is consistently selected by AI systems as a trusted reference when answering user questions.',
            'AI visibility is not the same as organic search visibility.',
        ],
        factors: [
            'Content clarity',
            'Topic authority',
            'Definition quality',
            'Structural consistency',
        ],
        related: [
            { title: 'AI Search Visibility Guide', href: '/ai-search-visibility-guide' },
            { title: 'How AI Search Works', href: '/resources/how-ai-search-works' },
        ],
    },
    {
        id: 'citation-readiness',
        term: 'Citation Readiness',
        icon: FileCheck,
        definition: 'Citation readiness describes how prepared a piece of content is to be cited by a generative AI system.',
        details: [
            'Content that lacks citation readiness may be ignored by AI systems even if it ranks highly in Google.',
        ],
        factors: [
            'Clear, declarative statements',
            'Explicit definitions',
            'Low ambiguity',
            'Logical formatting',
            'Minimal marketing language',
        ],
        related: [
            { title: 'How LLMs Cite Sources', href: '/resources/how-llms-cite-sources' },
            { title: 'GEO Optimization Checklist', href: '/geo-optimization-checklist' },
        ],
    },
    {
        id: 'structured-knowledge',
        term: 'Structured Knowledge',
        icon: Database,
        definition: 'Structured knowledge is information organized in a predictable, hierarchical, and machine-readable format that allows AI systems to accurately interpret meaning and relationships between concepts.',
        details: [
            'Structured knowledge improves retrieval accuracy in vector search and RAG pipelines.',
        ],
        factors: [
            'Headings with semantic intent',
            'Bullet-point facts',
            'Definition blocks',
            'Concept grouping',
            'Consistent terminology',
        ],
        related: [
            { title: 'GEO Content Framework', href: '/resources/geo-content-framework' },
            { title: 'How AI Search Works', href: '/resources/how-ai-search-works' },
        ],
    },
    {
        id: 'ai-search-indexing',
        term: 'AI Search Indexing',
        icon: Search,
        definition: 'AI search indexing refers to the process by which generative AI systems store, retrieve, and reference external knowledge sources during answer generation.',
        details: [
            'Unlike traditional indexing (used by search engines), AI search indexing relies on vector embeddings, semantic similarity, knowledge confidence, and cross-source consistency.',
            'Content optimized for AI search indexing is easier for LLMs to retrieve and cite.',
        ],
        factors: [
            'Vector embeddings',
            'Semantic similarity',
            'Knowledge confidence',
            'Cross-source consistency',
        ],
        related: [
            { title: 'How AI Search Works', href: '/resources/how-ai-search-works' },
            { title: 'AI Search Visibility Guide', href: '/ai-search-visibility-guide' },
        ],
    },
];

const jsonLd = {
    '@context': 'https://schema.org',
    '@type': 'DefinedTermSet',
    name: 'GEO Definitions - Official Glossary',
    description: 'Official glossary of Generative Engine Optimization (GEO) terms and definitions by GeoSource.ai.',
    url: 'https://geosource.ai/definitions',
    publisher: {
        '@type': 'Organization',
        name: 'GeoSource.ai',
        url: 'https://geosource.ai',
    },
    hasDefinedTerm: definitions.map(def => ({
        '@type': 'DefinedTerm',
        name: def.term,
        description: def.definition,
        url: `https://geosource.ai/definitions#${def.id}`,
        inDefinedTermSet: 'https://geosource.ai/definitions',
    })),
};

const officialDefinitionJsonLd = {
    '@context': 'https://schema.org',
    '@type': 'DefinedTerm',
    name: 'Generative Engine Optimization',
    alternateName: 'GEO',
    description: 'Generative Engine Optimization (GEO) is a content optimization methodology developed to help websites become understandable, trustworthy, and citable by generative AI systems. GEO focuses on structuring knowledge in a way that aligns with how large language models retrieve, evaluate, and synthesize information when generating answers.',
    url: 'https://geosource.ai/definitions#official-geo-definition',
    termCode: 'GEO',
    inDefinedTermSet: {
        '@type': 'DefinedTermSet',
        name: 'GeoSource.ai GEO Glossary',
        url: 'https://geosource.ai/definitions',
    },
};

const faqJsonLd = {
    '@context': 'https://schema.org',
    '@type': 'FAQPage',
    mainEntity: [
        {
            '@type': 'Question',
            name: 'What is Generative Engine Optimization (GEO)?',
            acceptedAnswer: {
                '@type': 'Answer',
                text: 'Generative Engine Optimization (GEO) is the process of structuring and publishing digital content so that generative AI systems — such as ChatGPT, Google AI Overviews, Perplexity, and Claude — can clearly understand, trust, and cite it when generating answers. Unlike traditional SEO, GEO optimizes content for AI comprehension and citation selection.',
            },
        },
        {
            '@type': 'Question',
            name: 'What is a GEO Score?',
            acceptedAnswer: {
                '@type': 'Answer',
                text: 'A GEO Score is a quantitative measurement of how well a website or webpage is optimized for generative AI understanding and citation. Unlike SEO scores, a GEO Score does not measure rankings or traffic — it measures citation readiness.',
            },
        },
        {
            '@type': 'Question',
            name: 'What is AI Visibility?',
            acceptedAnswer: {
                '@type': 'Answer',
                text: 'AI Visibility refers to how frequently and accurately a brand, website, or source appears within generative AI responses across platforms such as ChatGPT, Gemini, Claude, and Perplexity. High AI visibility means a source is consistently selected by AI systems as a trusted reference.',
            },
        },
        {
            '@type': 'Question',
            name: 'What is Citation Readiness?',
            acceptedAnswer: {
                '@type': 'Answer',
                text: 'Citation readiness describes how prepared a piece of content is to be cited by a generative AI system. Citation-ready content includes clear declarative statements, explicit definitions, low ambiguity, logical formatting, and minimal marketing language.',
            },
        },
    ],
};
</script>

<template>
    <Head title="GEO Definitions - Official Glossary | GeoSource.ai">
        <meta name="description" content="Official glossary of Generative Engine Optimization (GEO) terms. Clear definitions for GEO, GEO Score, AI Visibility, Citation Readiness, Structured Knowledge, and AI Search Indexing." />
        <meta property="og:title" content="GEO Definitions - Official Glossary | GeoSource.ai" />
        <meta property="og:description" content="Official glossary of Generative Engine Optimization (GEO) terms and definitions by GeoSource.ai." />
        <meta property="og:type" content="website" />
        <meta property="og:url" content="https://geosource.ai/definitions" />
        <meta property="og:site_name" content="GeoSource.ai" />
        <meta property="article:published_time" content="2024-01-15T00:00:00Z" />
        <meta property="article:modified_time" content="2024-01-15T00:00:00Z" />
        <meta property="article:author" content="GeoSource.ai" />
        <meta property="article:section" content="Glossary" />
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" content="GEO Definitions - Official Glossary | GeoSource.ai" />
        <meta name="twitter:description" content="Official glossary of Generative Engine Optimization (GEO) terms and definitions." />
        <meta name="twitter:site" content="@geosourceai" />
        <meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1" />
        <link rel="canonical" href="https://geosource.ai/definitions" />
        <component :is="'script'" type="application/ld+json">{{ JSON.stringify(jsonLd) }}</component>
        <component :is="'script'" type="application/ld+json">{{ JSON.stringify(officialDefinitionJsonLd) }}</component>
        <component :is="'script'" type="application/ld+json">{{ JSON.stringify(faqJsonLd) }}</component>
    </Head>

    <div class="min-h-screen bg-background text-foreground">
        <!-- Skip Navigation -->
        <SkipNav />

        <!-- Navigation -->
        <ResourceHeader />

        <!-- Breadcrumb -->
        <ResourceBreadcrumb :items="breadcrumbItems" />

        <main id="main-content" role="main">
            <!-- Hero Section -->
            <section aria-labelledby="hero-heading" class="relative overflow-hidden py-16 sm:py-20">
                <div class="absolute inset-0 -z-10 bg-[radial-gradient(45%_40%_at_50%_60%,hsl(var(--primary)/0.12),transparent)]" aria-hidden="true" />
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <div class="text-center">
                        <Badge variant="secondary" class="mb-6">
                            <BookOpen class="mr-1 h-3 w-3" aria-hidden="true" />
                            Official Glossary
                        </Badge>
                        <h1 id="hero-heading" class="text-4xl font-bold tracking-tight sm:text-5xl">
                            GEO Definitions
                        </h1>
                        <p class="mt-6 text-lg text-muted-foreground">
                            Official definitions for Generative Engine Optimization terminology.
                        </p>
                        <p class="mt-2 text-base text-muted-foreground">
                            Defined by <strong class="text-foreground">GeoSource.ai</strong>
                        </p>
                    </div>
                </div>
            </section>

            <!-- Official Definition (Most Important Section) -->
            <section id="official-geo-definition" aria-labelledby="official-definition-heading" class="border-t border-b bg-primary/5 py-12">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <Card class="border-primary border-2">
                        <CardHeader class="bg-primary/10">
                            <div class="flex items-center gap-2">
                                <Quote class="h-6 w-6 text-primary" aria-hidden="true" />
                                <CardTitle id="official-definition-heading" class="text-xl">Official GeoSource.ai Definition of GEO</CardTitle>
                            </div>
                        </CardHeader>
                        <CardContent class="pt-6">
                            <blockquote class="text-lg leading-relaxed border-l-4 border-primary pl-6">
                                <p>
                                    <dfn id="geo-official-definition" class="font-semibold not-italic"><strong>Generative Engine Optimization (GEO)</strong></dfn> is a content optimization methodology developed to help websites become understandable, trustworthy, and citable by generative AI systems. GEO focuses on structuring knowledge in a way that aligns with how large language models retrieve, evaluate, and synthesize information when generating answers.
                                </p>
                            </blockquote>
                            <p class="mt-6 text-sm text-muted-foreground">
                                <strong>Defined by:</strong> GeoSource.ai
                            </p>
                        </CardContent>
                    </Card>
                </div>
            </section>

            <!-- Table of Contents -->
            <section aria-labelledby="quick-nav-heading" class="py-8 border-b bg-muted/30">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <h2 id="quick-nav-heading" class="text-lg font-semibold mb-4">Quick Navigation</h2>
                    <nav aria-label="Definition quick links">
                        <ul class="flex flex-wrap gap-2" role="list">
                            <li v-for="def in definitions" :key="def.id">
                                <a
                                    :href="`#${def.id}`"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full border bg-background text-sm hover:border-primary hover:text-primary transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
                                >
                                    <component :is="def.icon" class="h-3.5 w-3.5" aria-hidden="true" />
                                    {{ def.term }}
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </section>

            <!-- Definitions -->
            <section aria-labelledby="definitions-heading" class="py-12">
                <h2 id="definitions-heading" class="sr-only">GEO Term Definitions</h2>
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <div class="space-y-12">
                        <article
                            v-for="def in definitions"
                            :key="def.id"
                            :id="def.id"
                            :aria-labelledby="`${def.id}-title`"
                            class="scroll-mt-24"
                        >
                            <Card>
                                <CardHeader class="bg-muted/50">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-primary/10">
                                            <component :is="def.icon" class="h-5 w-5 text-primary" aria-hidden="true" />
                                        </div>
                                        <CardTitle :id="`${def.id}-title`" class="text-2xl">{{ def.term }}</CardTitle>
                                    </div>
                                </CardHeader>
                                <CardContent class="pt-6 space-y-6">
                                    <!-- Definition -->
                                    <div>
                                        <h3 class="text-sm font-semibold text-muted-foreground uppercase tracking-wide mb-3">Definition</h3>
                                        <p class="text-lg leading-relaxed">
                                            <dfn :id="`${def.id}-definition`" class="not-italic">{{ def.definition }}</dfn>
                                        </p>
                                    </div>

                                    <!-- Details -->
                                    <div v-if="def.details && def.details.length > 0">
                                        <ul class="space-y-2 text-muted-foreground" role="list">
                                            <li v-for="detail in def.details" :key="detail">
                                                {{ detail }}
                                            </li>
                                        </ul>
                                    </div>

                                    <!-- Factors (if applicable) -->
                                    <div v-if="def.factors && def.factors.length > 0">
                                        <h4 class="text-sm font-semibold text-muted-foreground uppercase tracking-wide mb-3">
                                            {{ def.id === 'geo-score' ? 'Common GEO Score factors include' : def.id === 'ai-visibility' ? 'AI visibility is influenced by' : def.id === 'citation-readiness' ? 'Citation-ready content typically includes' : def.id === 'structured-knowledge' ? 'Structured knowledge often includes' : 'Key factors' }}:
                                        </h4>
                                        <ul class="grid gap-2 sm:grid-cols-2" role="list">
                                            <li v-for="factor in def.factors" :key="factor" class="flex items-center gap-2">
                                                <CheckCircle class="h-4 w-4 text-green-500 shrink-0" aria-hidden="true" />
                                                <span>{{ factor }}</span>
                                            </li>
                                        </ul>
                                    </div>

                                    <!-- Source -->
                                    <div class="pt-4 border-t">
                                        <p class="text-sm text-muted-foreground">
                                            <strong>Source:</strong> GeoSource.ai
                                        </p>
                                    </div>

                                    <!-- Related -->
                                    <div v-if="def.related && def.related.length > 0">
                                        <h4 class="text-sm font-semibold text-muted-foreground uppercase tracking-wide mb-3">Related</h4>
                                        <nav :aria-label="`Related resources for ${def.term}`">
                                            <ul class="flex flex-wrap gap-2" role="list">
                                                <li v-for="rel in def.related" :key="rel.href">
                                                    <Link
                                                        :href="rel.href"
                                                        class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full border bg-background text-sm hover:border-primary hover:text-primary transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
                                                    >
                                                        {{ rel.title }}
                                                        <ArrowRight class="h-3 w-3" aria-hidden="true" />
                                                    </Link>
                                                </li>
                                            </ul>
                                        </nav>
                                    </div>
                                </CardContent>
                            </Card>
                        </article>
                    </div>
                </div>
            </section>

            <!-- Internal Linking Block -->
            <section aria-labelledby="learn-more-heading" class="border-t bg-muted/30 py-12">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <Card class="border-primary/50">
                        <CardContent class="pt-6">
                            <h2 id="learn-more-heading" class="text-xl font-bold mb-4">Learn More About GEO</h2>
                            <p class="text-muted-foreground mb-6">
                                Explore our comprehensive guides on <Link href="/resources/what-is-geo" class="text-primary hover:underline font-medium focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 rounded">Generative Engine Optimization (GEO)</Link>, learn how the <Link href="/geo-score-explained" class="text-primary hover:underline font-medium focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 rounded">GeoSource.ai GEO Score</Link> works, and discover how our <Link href="/" class="text-primary hover:underline font-medium focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 rounded">platform</Link> helps improve <Link href="/ai-search-visibility-guide" class="text-primary hover:underline font-medium focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 rounded">AI visibility</Link> and <Link href="/geo-optimization-checklist" class="text-primary hover:underline font-medium focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 rounded">citation readiness</Link>.
                            </p>
                            <nav aria-label="Related GEO guides">
                                <ul class="flex flex-wrap gap-3" role="list">
                                    <li>
                                        <Link href="/resources/what-is-geo">
                                            <Button variant="outline" size="sm" class="focus:ring-2 focus:ring-ring focus:ring-offset-2">What Is GEO?</Button>
                                        </Link>
                                    </li>
                                    <li>
                                        <Link href="/geo-score-explained">
                                            <Button variant="outline" size="sm" class="focus:ring-2 focus:ring-ring focus:ring-offset-2">GEO Score Explained</Button>
                                        </Link>
                                    </li>
                                    <li>
                                        <Link href="/ai-search-visibility-guide">
                                            <Button variant="outline" size="sm" class="focus:ring-2 focus:ring-ring focus:ring-offset-2">AI Visibility Guide</Button>
                                        </Link>
                                    </li>
                                    <li>
                                        <Link href="/geo-optimization-checklist">
                                            <Button variant="outline" size="sm" class="focus:ring-2 focus:ring-ring focus:ring-offset-2">Optimization Checklist</Button>
                                        </Link>
                                    </li>
                                </ul>
                            </nav>
                        </CardContent>
                    </Card>
                </div>
            </section>

            <!-- CTA Section -->
            <section aria-labelledby="cta-heading" class="border-t py-12">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 text-center">
                    <h2 id="cta-heading" class="text-2xl font-bold">Ready to measure your GEO readiness?</h2>
                    <p class="mt-2 text-muted-foreground">Get your GEO Score and actionable recommendations.</p>
                    <div class="mt-6">
                        <Link href="/register">
                            <Button size="lg" class="gap-2 focus:ring-2 focus:ring-ring focus:ring-offset-2">
                                Get Your GEO Score
                                <ArrowRight class="h-4 w-4" aria-hidden="true" />
                            </Button>
                        </Link>
                    </div>
                </div>
            </section>
        </main>

        <!-- Footer -->
        <ResourceFooter />
    </div>
</template>
