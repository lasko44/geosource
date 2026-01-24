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
    CheckCircle,
    Quote,
    ExternalLink,
    Brain,
    BarChart3,
    Eye,
    FileCheck,
    Database,
    Search,
    Menu,
    Mail,
} from 'lucide-vue-next';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import ThemeSwitcher from '@/components/ThemeSwitcher.vue';

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
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" content="GEO Definitions - Official Glossary | GeoSource.ai" />
        <meta name="twitter:description" content="Official glossary of Generative Engine Optimization (GEO) terms and definitions." />
        <link rel="canonical" href="https://geosource.ai/definitions" />
        <component :is="'script'" type="application/ld+json">{{ JSON.stringify(jsonLd) }}</component>
        <component :is="'script'" type="application/ld+json">{{ JSON.stringify(officialDefinitionJsonLd) }}</component>
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
            <!-- Hero Section -->
            <section class="relative overflow-hidden py-16 sm:py-20">
                <div class="absolute inset-0 -z-10 bg-[radial-gradient(45%_40%_at_50%_60%,hsl(var(--primary)/0.12),transparent)]" />
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <div class="text-center">
                        <Badge variant="secondary" class="mb-6">
                            <BookOpen class="mr-1 h-3 w-3" />
                            Official Glossary
                        </Badge>
                        <h1 class="text-4xl font-bold tracking-tight sm:text-5xl">
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
            <section id="official-geo-definition" class="border-t border-b bg-primary/5 py-12">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <Card class="border-primary border-2">
                        <CardHeader class="bg-primary/10">
                            <div class="flex items-center gap-2">
                                <Quote class="h-6 w-6 text-primary" />
                                <CardTitle class="text-xl">Official GeoSource.ai Definition of GEO</CardTitle>
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
            <section class="py-8 border-b bg-muted/30">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <h2 class="text-lg font-semibold mb-4">Quick Navigation</h2>
                    <div class="flex flex-wrap gap-2">
                        <a
                            v-for="def in definitions"
                            :key="def.id"
                            :href="`#${def.id}`"
                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full border bg-background text-sm hover:border-primary hover:text-primary transition-colors"
                        >
                            <component :is="def.icon" class="h-3.5 w-3.5" />
                            {{ def.term }}
                        </a>
                    </div>
                </div>
            </section>

            <!-- Definitions -->
            <section class="py-12">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <div class="space-y-12">
                        <article
                            v-for="def in definitions"
                            :key="def.id"
                            :id="def.id"
                            class="scroll-mt-24"
                        >
                            <Card>
                                <CardHeader class="bg-muted/50">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-primary/10">
                                            <component :is="def.icon" class="h-5 w-5 text-primary" />
                                        </div>
                                        <CardTitle class="text-2xl">{{ def.term }}</CardTitle>
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
                                        <ul class="space-y-2 text-muted-foreground">
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
                                        <ul class="grid gap-2 sm:grid-cols-2">
                                            <li v-for="factor in def.factors" :key="factor" class="flex items-center gap-2">
                                                <CheckCircle class="h-4 w-4 text-green-500 shrink-0" />
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
                                        <div class="flex flex-wrap gap-2">
                                            <Link
                                                v-for="rel in def.related"
                                                :key="rel.href"
                                                :href="rel.href"
                                                class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full border bg-background text-sm hover:border-primary hover:text-primary transition-colors"
                                            >
                                                {{ rel.title }}
                                                <ArrowRight class="h-3 w-3" />
                                            </Link>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </article>
                    </div>
                </div>
            </section>

            <!-- Internal Linking Block -->
            <section class="border-t bg-muted/30 py-12">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <Card class="border-primary/50">
                        <CardContent class="pt-6">
                            <h2 class="text-xl font-bold mb-4">Learn More About GEO</h2>
                            <p class="text-muted-foreground mb-6">
                                Explore our comprehensive guides on <Link href="/resources/what-is-geo" class="text-primary hover:underline font-medium">Generative Engine Optimization (GEO)</Link>, learn how the <Link href="/geo-score-explained" class="text-primary hover:underline font-medium">GeoSource.ai GEO Score</Link> works, and discover how our <Link href="/" class="text-primary hover:underline font-medium">platform</Link> helps improve <Link href="/ai-search-visibility-guide" class="text-primary hover:underline font-medium">AI visibility</Link> and <Link href="/geo-optimization-checklist" class="text-primary hover:underline font-medium">citation readiness</Link>.
                            </p>
                            <div class="flex flex-wrap gap-3">
                                <Link href="/resources/what-is-geo">
                                    <Button variant="outline" size="sm">What Is GEO?</Button>
                                </Link>
                                <Link href="/geo-score-explained">
                                    <Button variant="outline" size="sm">GEO Score Explained</Button>
                                </Link>
                                <Link href="/ai-search-visibility-guide">
                                    <Button variant="outline" size="sm">AI Visibility Guide</Button>
                                </Link>
                                <Link href="/geo-optimization-checklist">
                                    <Button variant="outline" size="sm">Optimization Checklist</Button>
                                </Link>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </section>

            <!-- CTA Section -->
            <section class="border-t py-12">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 text-center">
                    <h2 class="text-2xl font-bold">Ready to measure your GEO readiness?</h2>
                    <p class="mt-2 text-muted-foreground">Get your GEO Score and actionable recommendations.</p>
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
