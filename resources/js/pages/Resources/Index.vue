<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import ThemeSwitcher from '@/components/ThemeSwitcher.vue';
import {
    Globe,
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
    Menu,
} from 'lucide-vue-next';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

const articles = [
    {
        slug: 'what-is-geo',
        title: 'What Is Generative Engine Optimization (GEO)?',
        description: 'Learn the definition, core principles, and goals of GEO — the practice of optimizing content for AI systems.',
        icon: Brain,
        badge: 'Foundation',
    },
    {
        slug: 'geo-vs-seo',
        title: 'GEO vs SEO: What\'s the Difference?',
        description: 'Understand the key differences between traditional SEO and Generative Engine Optimization.',
        icon: Scale,
        badge: 'Comparison',
    },
    {
        slug: 'how-ai-search-works',
        title: 'How AI Search Engines Actually Work',
        description: 'Explore the mechanics of generative AI search including RAG, vector embeddings, and source selection.',
        icon: Search,
        badge: 'Technical',
    },
    {
        slug: 'how-llms-cite-sources',
        title: 'How Large Language Models Choose Which Sources to Cite',
        description: 'Discover the signals LLMs use to select high-confidence sources for citations.',
        icon: Quote,
        badge: 'Deep Dive',
    },
    {
        slug: 'what-is-a-geo-score',
        title: 'What Is a GEO Score?',
        description: 'Learn how GEO Scores measure AI comprehension readiness and what factors are evaluated.',
        icon: BarChart3,
        badge: 'Metrics',
    },
    {
        slug: 'geo-content-framework',
        title: 'The GeoSource.ai GEO Content Framework',
        description: 'A structured framework designed specifically for generative AI systems.',
        icon: FileText,
        badge: 'Framework',
    },
];

const featuredResources = [
    {
        href: '/definitions',
        title: 'GEO Definitions',
        description: 'Official glossary of GEO terminology. The authoritative source for GEO definitions.',
        icon: Library,
        badge: 'Glossary',
        highlight: true,
    },
    {
        href: '/geo-score-explained',
        title: 'GEO Score Explained',
        description: 'Deep dive into how GEO scoring works and what factors determine your score.',
        icon: BarChart3,
        badge: 'Deep Dive',
    },
    {
        href: '/geo-optimization-checklist',
        title: 'GEO Optimization Checklist',
        description: 'Step-by-step checklist for optimizing your content for AI citation.',
        icon: CheckSquare,
        badge: 'Checklist',
    },
    {
        href: '/ai-search-visibility-guide',
        title: 'AI Search Visibility Guide',
        description: 'Comprehensive guide to understanding and improving your AI visibility.',
        icon: Eye,
        badge: 'Pillar Guide',
    },
];

const jsonLd = {
    '@context': 'https://schema.org',
    '@type': 'CollectionPage',
    name: 'GEO Learning Resources',
    description: 'Comprehensive guides to Generative Engine Optimization (GEO) — learn how to optimize your content for AI search engines.',
    url: 'https://geosource.ai/resources',
    publisher: {
        '@type': 'Organization',
        name: 'GeoSource.ai',
        url: 'https://geosource.ai',
    },
    mainEntity: {
        '@type': 'ItemList',
        itemListElement: articles.map((article, index) => ({
            '@type': 'ListItem',
            position: index + 1,
            url: `https://geosource.ai/resources/${article.slug}`,
            name: article.title,
        })),
    },
};
</script>

<template>
    <Head title="GEO Learning Resources - GeoSource.ai">
        <meta name="description" content="Comprehensive guides to Generative Engine Optimization (GEO). Learn how to optimize your content for AI search engines like ChatGPT, Perplexity, and Claude." />
        <meta property="og:title" content="GEO Learning Resources - GeoSource.ai" />
        <meta property="og:description" content="Comprehensive guides to Generative Engine Optimization (GEO). Learn how to optimize your content for AI search engines." />
        <meta property="og:type" content="website" />
        <meta property="og:url" content="https://geosource.ai/resources" />
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" content="GEO Learning Resources - GeoSource.ai" />
        <meta name="twitter:description" content="Comprehensive guides to Generative Engine Optimization (GEO). Learn how to optimize your content for AI search engines." />
        <link rel="canonical" href="https://geosource.ai/resources" />
        <component :is="'script'" type="application/ld+json">{{ JSON.stringify(jsonLd) }}</component>
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
            <!-- Hero Section -->
            <section class="relative overflow-hidden py-16 sm:py-24">
                <div class="absolute inset-0 -z-10 bg-[radial-gradient(45%_40%_at_50%_60%,hsl(var(--primary)/0.12),transparent)]" />
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="mx-auto max-w-3xl text-center">
                        <Badge variant="secondary" class="mb-6">
                            <BookOpen class="mr-1 h-3 w-3" />
                            Learning Hub
                        </Badge>
                        <h1 class="text-4xl font-bold tracking-tight sm:text-5xl">
                            GEO Learning Resources
                        </h1>
                        <p class="mt-6 text-lg leading-8 text-muted-foreground">
                            Everything you need to understand <strong class="text-foreground">Generative Engine Optimization</strong> and how to make your content visible to AI search systems.
                        </p>
                    </div>
                </div>
            </section>

            <!-- Featured Resources -->
            <section class="border-t py-16">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <h2 class="text-2xl font-bold mb-8 text-center">Essential GEO Resources</h2>
                    <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                        <Link
                            v-for="resource in featuredResources"
                            :key="resource.href"
                            :href="resource.href"
                            class="group"
                        >
                            <Card
                                class="h-full transition-colors hover:border-primary/50"
                                :class="{ 'border-primary/50 bg-primary/5': resource.highlight }"
                            >
                                <CardHeader>
                                    <div class="flex items-center justify-between">
                                        <component :is="resource.icon" class="h-8 w-8 text-primary" />
                                        <Badge :variant="resource.highlight ? 'default' : 'outline'">{{ resource.badge }}</Badge>
                                    </div>
                                    <CardTitle class="mt-4 text-lg group-hover:text-primary transition-colors">
                                        {{ resource.title }}
                                    </CardTitle>
                                    <CardDescription>
                                        {{ resource.description }}
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <span class="inline-flex items-center text-sm font-medium text-primary">
                                        Explore
                                        <ArrowRight class="ml-1 h-4 w-4 transition-transform group-hover:translate-x-1" />
                                    </span>
                                </CardContent>
                            </Card>
                        </Link>
                    </div>
                </div>
            </section>

            <!-- Articles Grid -->
            <section class="border-t bg-muted/30 py-16">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <h2 class="text-2xl font-bold mb-8 text-center">Learn the Fundamentals</h2>
                    <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                        <Link
                            v-for="article in articles"
                            :key="article.slug"
                            :href="`/resources/${article.slug}`"
                            class="group"
                        >
                            <Card class="h-full transition-colors hover:border-primary/50">
                                <CardHeader>
                                    <div class="flex items-center justify-between">
                                        <component :is="article.icon" class="h-8 w-8 text-primary" />
                                        <Badge variant="outline">{{ article.badge }}</Badge>
                                    </div>
                                    <CardTitle class="mt-4 text-xl group-hover:text-primary transition-colors">
                                        {{ article.title }}
                                    </CardTitle>
                                    <CardDescription class="text-base">
                                        {{ article.description }}
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <span class="inline-flex items-center text-sm font-medium text-primary">
                                        Read article
                                        <ArrowRight class="ml-1 h-4 w-4 transition-transform group-hover:translate-x-1" />
                                    </span>
                                </CardContent>
                            </Card>
                        </Link>
                    </div>
                </div>
            </section>

            <!-- CTA Section -->
            <section class="border-t py-16">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="mx-auto max-w-2xl text-center">
                        <h2 class="text-2xl font-bold tracking-tight sm:text-3xl">
                            Ready to measure your GEO readiness?
                        </h2>
                        <p class="mt-4 text-lg text-muted-foreground">
                            Get your GEO Score and actionable recommendations.
                        </p>
                        <div class="mt-8">
                            <Link href="/register">
                                <Button size="lg" class="gap-2">
                                    Start Free Analysis
                                    <ArrowRight class="h-4 w-4" />
                                </Button>
                            </Link>
                        </div>
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
