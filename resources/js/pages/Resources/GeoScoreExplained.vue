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
    BarChart3,
    CheckCircle,
    XCircle,
    Brain,
    FileText,
    Database,
    Target,
    Search,
    HelpCircle,
    Lightbulb,
    TrendingUp,
    Menu,
} from 'lucide-vue-next';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import ThemeSwitcher from '@/components/ThemeSwitcher.vue';

const scoringFactors = [
    {
        factor: 'Structured Knowledge Clarity',
        weight: 'High',
        description: 'How clearly your content organizes and presents information in a machine-readable format.',
        examples: ['Clear heading hierarchy', 'Logical content sections', 'Consistent formatting'],
    },
    {
        factor: 'Definition Quality',
        weight: 'High',
        description: 'The presence and clarity of explicit definitions for key terms and concepts.',
        examples: ['"X is defined as..." patterns', 'Glossary sections', 'Inline definitions'],
    },
    {
        factor: 'Topic Hierarchy',
        weight: 'Medium',
        description: 'How well your content establishes relationships between concepts and subtopics.',
        examples: ['Parent-child topic relationships', 'Breadcrumb navigation', 'Internal linking'],
    },
    {
        factor: 'Entity Consistency',
        weight: 'Medium',
        description: 'Whether you use the same terminology consistently throughout your content.',
        examples: ['Consistent naming', 'No synonym confusion', 'Clear entity references'],
    },
    {
        factor: 'FAQ Coverage',
        weight: 'Medium',
        description: 'Direct answers to common questions in a question-and-answer format.',
        examples: ['FAQ sections', 'Direct question headings', 'Structured Q&A'],
    },
    {
        factor: 'Machine-Readable Formatting',
        weight: 'High',
        description: 'Technical optimization including schema markup and semantic HTML.',
        examples: ['JSON-LD structured data', 'Semantic HTML tags', 'Proper meta tags'],
    },
];

const gradeBreakdown = [
    { grade: 'A+ (97-100)', color: 'text-green-500', bgColor: 'bg-green-500/10', description: 'Exceptional. Content is fully optimized for AI citation.' },
    { grade: 'A (93-96)', color: 'text-green-500', bgColor: 'bg-green-500/10', description: 'Excellent. Minor improvements possible.' },
    { grade: 'A- (90-92)', color: 'text-green-500', bgColor: 'bg-green-500/10', description: 'Very good. Well-structured with clear definitions.' },
    { grade: 'B+ (87-89)', color: 'text-blue-500', bgColor: 'bg-blue-500/10', description: 'Good. Some optimization gaps to address.' },
    { grade: 'B (83-86)', color: 'text-blue-500', bgColor: 'bg-blue-500/10', description: 'Above average. Needs structural improvements.' },
    { grade: 'B- (80-82)', color: 'text-blue-500', bgColor: 'bg-blue-500/10', description: 'Decent foundation. Multiple areas need work.' },
    { grade: 'C+ (77-79)', color: 'text-amber-500', bgColor: 'bg-amber-500/10', description: 'Average. Significant optimization needed.' },
    { grade: 'C (73-76)', color: 'text-amber-500', bgColor: 'bg-amber-500/10', description: 'Below average. Missing key GEO elements.' },
    { grade: 'C- (70-72)', color: 'text-amber-500', bgColor: 'bg-amber-500/10', description: 'Poor. Unlikely to be cited by AI.' },
    { grade: 'D/F (0-69)', color: 'text-red-500', bgColor: 'bg-red-500/10', description: 'Failing. Content is not AI-readable.' },
];

const geoVsSeoComparison = [
    { aspect: 'What it measures', seo: 'Search engine rankings', geo: 'AI comprehension readiness' },
    { aspect: 'Success metric', seo: 'Traffic and clicks', geo: 'Citations and references' },
    { aspect: 'Optimization focus', seo: 'Keywords and backlinks', geo: 'Clarity and structure' },
    { aspect: 'Target system', seo: 'Google, Bing crawlers', geo: 'LLMs like ChatGPT, Claude' },
    { aspect: 'Content goal', seo: 'Rank on SERPs', geo: 'Be cited in AI answers' },
];

const jsonLd = {
    '@context': 'https://schema.org',
    '@type': 'Article',
    headline: 'GEO Score Explained - How GeoSource.ai Measures AI Readiness',
    description: 'A comprehensive guide to understanding GEO Scores. Learn what factors determine your score, how grades are calculated, and why GEO differs from SEO.',
    url: 'https://geosource.ai/geo-score-explained',
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
        '@id': 'https://geosource.ai/geo-score-explained',
    },
    about: {
        '@type': 'DefinedTerm',
        name: 'GEO Score',
        description: 'A quantitative measurement of how well a website or webpage is optimized for generative AI understanding and citation.',
    },
};

const faqJsonLd = {
    '@context': 'https://schema.org',
    '@type': 'FAQPage',
    mainEntity: [
        {
            '@type': 'Question',
            name: 'What is a GEO Score?',
            acceptedAnswer: {
                '@type': 'Answer',
                text: 'A GEO Score is a quantitative measurement (0-100) of how well a website or webpage is optimized for generative AI understanding and citation. Unlike SEO scores, it measures citation readiness rather than search rankings.',
            },
        },
        {
            '@type': 'Question',
            name: 'What factors determine a GEO Score?',
            acceptedAnswer: {
                '@type': 'Answer',
                text: 'GEO Scores are determined by six main factors: Structured Knowledge Clarity, Definition Quality, Topic Hierarchy, Entity Consistency, FAQ Coverage, and Machine-Readable Formatting.',
            },
        },
        {
            '@type': 'Question',
            name: 'How is GEO different from SEO?',
            acceptedAnswer: {
                '@type': 'Answer',
                text: 'SEO optimizes for search engine rankings and measures traffic/clicks. GEO optimizes for AI comprehension and measures citation readiness. SEO targets search crawlers while GEO targets large language models like ChatGPT and Claude.',
            },
        },
        {
            '@type': 'Question',
            name: 'What is a good GEO Score?',
            acceptedAnswer: {
                '@type': 'Answer',
                text: 'An A grade (90-100) indicates excellent AI comprehension readiness. B grades (80-89) show good foundation with room for improvement. C grades (70-79) indicate significant optimization is needed. Below 70 means content is unlikely to be cited by AI systems.',
            },
        },
    ],
};
</script>

<template>
    <Head title="GEO Score Explained - How GeoSource.ai Measures AI Readiness">
        <meta name="description" content="A comprehensive guide to understanding GEO Scores. Learn what factors determine your score, how grades are calculated, and why GEO differs from SEO." />
        <meta property="og:title" content="GEO Score Explained - How GeoSource.ai Measures AI Readiness" />
        <meta property="og:description" content="Learn what factors determine your GEO Score and how to improve your AI citation readiness." />
        <meta property="og:type" content="article" />
        <meta property="og:url" content="https://geosource.ai/geo-score-explained" />
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" content="GEO Score Explained - How GeoSource.ai Measures AI Readiness" />
        <meta name="twitter:description" content="Learn what factors determine your GEO Score and how to improve your AI citation readiness." />
        <link rel="canonical" href="https://geosource.ai/geo-score-explained" />
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
                        <span class="text-foreground">GEO Score Explained</span>
                    </nav>
                </div>
            </div>

            <!-- Article -->
            <article class="py-12">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <!-- Header -->
                    <header class="mb-12">
                        <Badge variant="secondary" class="mb-4">
                            <BarChart3 class="mr-1 h-3 w-3" />
                            Deep Dive
                        </Badge>
                        <h1 class="text-3xl font-bold tracking-tight sm:text-4xl lg:text-5xl">
                            GEO Score Explained
                        </h1>
                        <p class="mt-4 text-lg text-muted-foreground">
                            How GeoSource.ai measures your website's AI comprehension readiness.
                        </p>
                    </header>

                    <!-- Definition -->
                    <section class="mb-12" aria-labelledby="definition">
                        <h2 id="definition" class="text-2xl font-bold mb-6">What Is a GEO Score?</h2>
                        <Card class="border-primary/50 bg-primary/5">
                            <CardContent class="pt-6">
                                <p class="text-lg leading-relaxed">
                                    A <dfn id="geo-score-definition" class="font-semibold not-italic"><strong>GEO Score</strong></dfn> is a quantitative measurement (0-100) of how well a website or webpage is optimized for generative AI understanding and citation.
                                </p>
                                <p class="mt-4 text-muted-foreground">
                                    Unlike SEO scores, a GEO Score does not measure rankings or traffic. It measures <strong class="text-foreground">citation readiness</strong> — how likely AI systems are to understand, trust, and reference your content.
                                </p>
                            </CardContent>
                        </Card>
                    </section>

                    <Separator class="my-12" />

                    <!-- Scoring Factors -->
                    <section class="mb-12" aria-labelledby="factors">
                        <h2 id="factors" class="text-2xl font-bold mb-6">GEO Score Factors</h2>
                        <p class="text-muted-foreground mb-8">
                            Your GEO Score is calculated based on six key factors:
                        </p>

                        <div class="space-y-4">
                            <Card v-for="item in scoringFactors" :key="item.factor">
                                <CardHeader class="pb-2">
                                    <div class="flex items-center justify-between">
                                        <CardTitle class="text-lg">{{ item.factor }}</CardTitle>
                                        <Badge :variant="item.weight === 'High' ? 'default' : 'secondary'">
                                            {{ item.weight }} Weight
                                        </Badge>
                                    </div>
                                </CardHeader>
                                <CardContent>
                                    <p class="text-muted-foreground mb-3">{{ item.description }}</p>
                                    <div class="flex flex-wrap gap-2">
                                        <span
                                            v-for="example in item.examples"
                                            :key="example"
                                            class="inline-flex items-center gap-1 px-2 py-1 rounded bg-muted text-sm"
                                        >
                                            <CheckCircle class="h-3 w-3 text-green-500" />
                                            {{ example }}
                                        </span>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </section>

                    <Separator class="my-12" />

                    <!-- Grade Breakdown -->
                    <section class="mb-12" aria-labelledby="grades">
                        <h2 id="grades" class="text-2xl font-bold mb-6">Grade Breakdown</h2>
                        <p class="text-muted-foreground mb-8">
                            GEO Scores are converted to letter grades for easy interpretation:
                        </p>

                        <div class="grid gap-2">
                            <div
                                v-for="item in gradeBreakdown"
                                :key="item.grade"
                                class="flex items-center gap-4 p-3 rounded-lg border"
                                :class="item.bgColor"
                            >
                                <span class="font-mono font-bold w-24" :class="item.color">{{ item.grade }}</span>
                                <span class="text-sm">{{ item.description }}</span>
                            </div>
                        </div>
                    </section>

                    <Separator class="my-12" />

                    <!-- GEO vs SEO -->
                    <section class="mb-12" aria-labelledby="geo-vs-seo">
                        <h2 id="geo-vs-seo" class="text-2xl font-bold mb-6">Why GEO Score ≠ SEO Score</h2>

                        <Card class="mb-6 border-amber-500/50 bg-amber-500/5">
                            <CardContent class="pt-6">
                                <div class="flex items-start gap-3">
                                    <Lightbulb class="h-6 w-6 text-amber-500 shrink-0 mt-0.5" />
                                    <div>
                                        <p class="font-medium text-foreground">Important Distinction</p>
                                        <p class="text-muted-foreground mt-1">
                                            A page can have excellent SEO (ranking #1 on Google) but a poor GEO Score (never cited by ChatGPT). These are fundamentally different metrics measuring different things.
                                        </p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <div class="overflow-x-auto">
                            <table class="w-full border-collapse">
                                <thead>
                                    <tr class="border-b">
                                        <th class="py-3 px-4 text-left font-semibold">Aspect</th>
                                        <th class="py-3 px-4 text-left font-semibold">SEO Score</th>
                                        <th class="py-3 px-4 text-left font-semibold text-primary">GEO Score</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="row in geoVsSeoComparison" :key="row.aspect" class="border-b">
                                        <td class="py-3 px-4 font-medium">{{ row.aspect }}</td>
                                        <td class="py-3 px-4 text-muted-foreground">{{ row.seo }}</td>
                                        <td class="py-3 px-4 text-primary font-medium">{{ row.geo }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <Separator class="my-12" />

                    <!-- How to Improve -->
                    <section class="mb-12" aria-labelledby="improve">
                        <h2 id="improve" class="text-2xl font-bold mb-6">How to Improve Your GEO Score</h2>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <Card>
                                <CardHeader>
                                    <CardTitle class="text-lg flex items-center gap-2">
                                        <FileText class="h-5 w-5 text-primary" />
                                        Add Clear Definitions
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <p class="text-sm text-muted-foreground">
                                        Start key sections with explicit definitions using patterns like "X is defined as..."
                                    </p>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader>
                                    <CardTitle class="text-lg flex items-center gap-2">
                                        <Database class="h-5 w-5 text-primary" />
                                        Structure Your Content
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <p class="text-sm text-muted-foreground">
                                        Use clear heading hierarchies, bullet points, and consistent formatting throughout.
                                    </p>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader>
                                    <CardTitle class="text-lg flex items-center gap-2">
                                        <HelpCircle class="h-5 w-5 text-primary" />
                                        Include FAQ Sections
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <p class="text-sm text-muted-foreground">
                                        Add direct answers to common questions in a structured Q&A format.
                                    </p>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader>
                                    <CardTitle class="text-lg flex items-center gap-2">
                                        <Target class="h-5 w-5 text-primary" />
                                        Add Schema Markup
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <p class="text-sm text-muted-foreground">
                                        Implement JSON-LD structured data for articles, FAQs, and definitions.
                                    </p>
                                </CardContent>
                            </Card>
                        </div>

                        <div class="mt-8">
                            <Link href="/geo-optimization-checklist">
                                <Button variant="outline" class="gap-2">
                                    View Complete Optimization Checklist
                                    <ArrowRight class="h-4 w-4" />
                                </Button>
                            </Link>
                        </div>
                    </section>

                    <Separator class="my-12" />

                    <!-- Internal Linking Block -->
                    <section class="mb-12">
                        <Card class="border-primary/50">
                            <CardContent class="pt-6">
                                <h3 class="text-lg font-bold mb-4">Related Resources</h3>
                                <p class="text-muted-foreground mb-4">
                                    Learn more about <Link href="/resources/what-is-geo" class="text-primary hover:underline font-medium">Generative Engine Optimization (GEO)</Link>, explore the <Link href="/definitions" class="text-primary hover:underline font-medium">official GEO definitions</Link>, and use our <Link href="/geo-optimization-checklist" class="text-primary hover:underline font-medium">optimization checklist</Link> to improve your score.
                                </p>
                                <div class="flex flex-wrap gap-2">
                                    <Link href="/resources/what-is-geo">
                                        <Button variant="outline" size="sm">What Is GEO?</Button>
                                    </Link>
                                    <Link href="/definitions">
                                        <Button variant="outline" size="sm">GEO Definitions</Button>
                                    </Link>
                                    <Link href="/geo-optimization-checklist">
                                        <Button variant="outline" size="sm">Optimization Checklist</Button>
                                    </Link>
                                </div>
                            </CardContent>
                        </Card>
                    </section>

                    <!-- Navigation -->
                    <div class="flex items-center justify-between border-t pt-8">
                        <Link href="/resources" class="inline-flex items-center text-muted-foreground hover:text-foreground">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Back to Resources
                        </Link>
                        <Link href="/geo-optimization-checklist" class="inline-flex items-center text-primary hover:underline">
                            Next: Optimization Checklist
                            <ArrowRight class="ml-2 h-4 w-4" />
                        </Link>
                    </div>
                </div>
            </article>

            <!-- CTA Section -->
            <section class="border-t bg-muted/30 py-12">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 text-center">
                    <h2 class="text-2xl font-bold">Ready to get your GEO Score?</h2>
                    <p class="mt-2 text-muted-foreground">Discover exactly how AI systems see your content.</p>
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
