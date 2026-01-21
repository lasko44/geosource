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
    Award,
    Layers,
    Code,
    MessageSquare,
    UserCheck,
    Quote,
    Bot,
    Clock,
    Type,
    Image,
    Mail,
    Calendar,
} from 'lucide-vue-next';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import ThemeSwitcher from '@/components/ThemeSwitcher.vue';

const publishedDate = new Date('2026-01-18').toLocaleDateString('en-US', { month: 'long', year: 'numeric' });

// Free Tier Pillars (100 points max)
const freePillars = [
    {
        name: 'Clear Definitions',
        points: 20,
        icon: BookOpen,
        description: 'Presence of explicit "X is..." definitions early in your content.',
        examples: ['Early definition placement', 'Entity name in definition', 'Clear definitional patterns'],
    },
    {
        name: 'Structured Knowledge',
        points: 20,
        icon: Layers,
        description: 'How well your content is organized with proper heading hierarchy and lists.',
        examples: ['Single H1 heading', 'Multiple H2 subheadings', 'Bullet/numbered lists'],
    },
    {
        name: 'Topic Authority',
        points: 25,
        icon: Award,
        description: 'Depth of coverage and expertise indicators in your content.',
        examples: ['800-1500+ word count', 'Internal links', 'Examples and evidence'],
    },
    {
        name: 'Machine-Readable Formatting',
        points: 15,
        icon: Code,
        description: 'Technical optimization including schema markup and semantic HTML.',
        examples: ['JSON-LD structured data', 'Semantic HTML elements', 'Alt text on images', 'llms.txt file'],
    },
    {
        name: 'High-Confidence Answerability',
        points: 20,
        icon: MessageSquare,
        description: 'Declarative statements that AI can confidently quote and cite.',
        examples: ['Declarative sentences', 'Quotable snippets (50-150 chars)', 'Direct answers without preamble'],
    },
];

// Pro Tier Pillars (+35 points)
const proPillars = [
    {
        name: 'E-E-A-T Signals',
        points: 15,
        icon: UserCheck,
        description: 'Experience, Expertise, Authoritativeness, and Trustworthiness indicators.',
        examples: ['Author attribution', 'Author bio with credentials', 'Reviews/testimonials', 'Contact information'],
    },
    {
        name: 'Citations & Sources',
        points: 12,
        icon: Quote,
        description: 'External authoritative links and proper citation practices.',
        examples: ['Links to .gov/.edu sources', 'Inline citations', 'Statistics with sources', 'Reference sections'],
    },
    {
        name: 'AI Crawler Access',
        points: 8,
        icon: Bot,
        description: 'Technical accessibility for AI crawlers and systems.',
        examples: ['robots.txt allows AI bots', 'No noindex/nosnippet', 'Sitemap reference'],
    },
];

// Agency Tier Pillars (+40 points)
const agencyPillars = [
    {
        name: 'Content Freshness',
        points: 10,
        icon: Clock,
        description: 'Recency signals and regular content updates.',
        examples: ['Visible publish date', 'Last updated date', 'Current year references', 'datePublished in schema'],
    },
    {
        name: 'Readability',
        points: 10,
        icon: Type,
        description: 'Clear, accessible writing that AI can easily parse.',
        examples: ['8th-9th grade reading level', '15-20 word sentences', '50-100 word paragraphs', 'Simple vocabulary'],
    },
    {
        name: 'Question Coverage',
        points: 10,
        icon: HelpCircle,
        description: 'Direct answers to common questions users ask AI.',
        examples: ['FAQ sections', 'Question-format headings', 'FAQPage schema', 'What/How/Why coverage'],
    },
    {
        name: 'Multimedia Content',
        points: 10,
        icon: Image,
        description: 'Visual elements that enhance content understanding.',
        examples: ['Images with alt text', 'Figure captions', 'Tables for data', 'Visual variety'],
    },
];

const gradeBreakdown = [
    { grade: 'A+ (90%+)', color: 'text-green-500', bgColor: 'bg-green-500/10', description: 'Exceptional. Content is fully optimized for AI citation.' },
    { grade: 'A (85-89%)', color: 'text-green-500', bgColor: 'bg-green-500/10', description: 'Excellent. Minor improvements possible.' },
    { grade: 'A- (80-84%)', color: 'text-green-500', bgColor: 'bg-green-500/10', description: 'Very good. Well-structured with clear definitions.' },
    { grade: 'B+ (75-79%)', color: 'text-blue-500', bgColor: 'bg-blue-500/10', description: 'Good. Some optimization gaps to address.' },
    { grade: 'B (70-74%)', color: 'text-blue-500', bgColor: 'bg-blue-500/10', description: 'Above average. Needs structural improvements.' },
    { grade: 'B- (65-69%)', color: 'text-blue-500', bgColor: 'bg-blue-500/10', description: 'Decent foundation. Multiple areas need work.' },
    { grade: 'C+ (60-64%)', color: 'text-amber-500', bgColor: 'bg-amber-500/10', description: 'Average. Significant optimization needed.' },
    { grade: 'C (55-59%)', color: 'text-amber-500', bgColor: 'bg-amber-500/10', description: 'Below average. Missing key GEO elements.' },
    { grade: 'C- (50-54%)', color: 'text-amber-500', bgColor: 'bg-amber-500/10', description: 'Poor. Unlikely to be cited by AI.' },
    { grade: 'D+ (45-49%)', color: 'text-orange-500', bgColor: 'bg-orange-500/10', description: 'Weak. Major improvements needed.' },
    { grade: 'D (40-44%)', color: 'text-orange-500', bgColor: 'bg-orange-500/10', description: 'Very weak. Content barely AI-readable.' },
    { grade: 'F (<40%)', color: 'text-red-500', bgColor: 'bg-red-500/10', description: 'Failing. Content is not optimized for AI.' },
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
    datePublished: '2026-01-18',
    dateModified: '2026-01-18',
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
                text: 'A GEO Score is a quantitative measurement of how well a website or webpage is optimized for generative AI understanding and citation. Unlike SEO scores, it measures citation readiness rather than search rankings. The maximum score depends on your plan tier: Free (100 points), Pro (135 points), Agency (175 points).',
            },
        },
        {
            '@type': 'Question',
            name: 'What factors determine a GEO Score?',
            acceptedAnswer: {
                '@type': 'Answer',
                text: 'GEO Scores are determined by up to 12 pillars across three tiers. Free tier includes: Clear Definitions (20 pts), Structured Knowledge (20 pts), Topic Authority (25 pts), Machine-Readable Formatting (15 pts), and High-Confidence Answerability (20 pts). Pro adds E-E-A-T, Citations, and AI Accessibility. Agency adds Freshness, Readability, Question Coverage, and Multimedia.',
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
                text: 'An A+ grade (90%+) indicates exceptional AI comprehension readiness. A grades (80-89%) show excellent optimization. B grades (65-79%) indicate good foundation with room for improvement. C grades (50-64%) suggest significant optimization is needed. Below 50% means content needs substantial work to be cited by AI systems.',
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
                        <div class="mt-4 flex items-center gap-2 text-sm text-muted-foreground">
                            <Calendar class="h-4 w-4" />
                            <span>{{ publishedDate }}</span>
                        </div>
                    </header>

                    <!-- Definition -->
                    <section class="mb-12" aria-labelledby="definition">
                        <h2 id="definition" class="text-2xl font-bold mb-6">What Is a GEO Score?</h2>
                        <Card class="border-primary/50 bg-primary/5">
                            <CardContent class="pt-6">
                                <p class="text-lg leading-relaxed">
                                    A <dfn id="geo-score-definition" class="font-semibold not-italic"><strong>GEO Score</strong></dfn> is a quantitative measurement of how well a website or webpage is optimized for <Link href="/resources/what-is-geo" class="text-primary hover:underline">Generative Engine Optimization (GEO)</Link> — the practice of making content visible and citable by AI systems.
                                </p>
                                <p class="mt-4 text-muted-foreground">
                                    Unlike SEO scores, a GEO Score does not measure rankings or traffic. It measures <strong class="text-foreground">citation readiness</strong> — how likely AI systems like ChatGPT, Claude, and Perplexity are to understand, trust, and reference your content.
                                </p>
                                <p class="mt-4 text-muted-foreground">
                                    The <Link href="/" class="text-primary hover:underline">GeoSource.ai platform</Link> calculates your GEO Score across up to 12 pillars, with the maximum score depending on your plan tier: <strong class="text-foreground">Free (100 pts)</strong>, <strong class="text-foreground">Pro (135 pts)</strong>, or <strong class="text-foreground">Agency (175 pts)</strong>.
                                </p>
                            </CardContent>
                        </Card>
                    </section>

                    <Separator class="my-12" />

                    <!-- Scoring Pillars -->
                    <section class="mb-12" aria-labelledby="pillars">
                        <h2 id="pillars" class="text-2xl font-bold mb-6">GEO Score Pillars</h2>
                        <p class="text-muted-foreground mb-8">
                            Your GEO Score is calculated based on up to 12 pillars, organized by plan tier. Each pillar measures a specific aspect of AI optimization.
                        </p>

                        <!-- Free Tier -->
                        <div class="mb-10">
                            <div class="flex items-center gap-3 mb-4">
                                <h3 class="text-xl font-semibold">Free Tier</h3>
                                <Badge variant="secondary">100 points max</Badge>
                            </div>
                            <p class="text-muted-foreground mb-4 text-sm">Core pillars available to all users.</p>
                            <div class="space-y-4">
                                <Card v-for="pillar in freePillars" :key="pillar.name">
                                    <CardHeader class="pb-2">
                                        <div class="flex items-center justify-between">
                                            <CardTitle class="text-lg flex items-center gap-2">
                                                <component :is="pillar.icon" class="h-5 w-5 text-primary" />
                                                {{ pillar.name }}
                                            </CardTitle>
                                            <Badge variant="default">{{ pillar.points }} pts</Badge>
                                        </div>
                                    </CardHeader>
                                    <CardContent>
                                        <p class="text-muted-foreground mb-3">{{ pillar.description }}</p>
                                        <div class="flex flex-wrap gap-2">
                                            <span
                                                v-for="example in pillar.examples"
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
                        </div>

                        <!-- Pro Tier -->
                        <div class="mb-10">
                            <div class="flex items-center gap-3 mb-4">
                                <h3 class="text-xl font-semibold">Pro Tier</h3>
                                <Badge class="bg-blue-500 hover:bg-blue-600">+35 points</Badge>
                            </div>
                            <p class="text-muted-foreground mb-4 text-sm">Advanced pillars for Pro subscribers (135 points max total).</p>
                            <div class="space-y-4">
                                <Card v-for="pillar in proPillars" :key="pillar.name" class="border-blue-500/30">
                                    <CardHeader class="pb-2">
                                        <div class="flex items-center justify-between">
                                            <CardTitle class="text-lg flex items-center gap-2">
                                                <component :is="pillar.icon" class="h-5 w-5 text-blue-500" />
                                                {{ pillar.name }}
                                            </CardTitle>
                                            <Badge class="bg-blue-500 hover:bg-blue-600">{{ pillar.points }} pts</Badge>
                                        </div>
                                    </CardHeader>
                                    <CardContent>
                                        <p class="text-muted-foreground mb-3">{{ pillar.description }}</p>
                                        <div class="flex flex-wrap gap-2">
                                            <span
                                                v-for="example in pillar.examples"
                                                :key="example"
                                                class="inline-flex items-center gap-1 px-2 py-1 rounded bg-blue-500/10 text-sm"
                                            >
                                                <CheckCircle class="h-3 w-3 text-blue-500" />
                                                {{ example }}
                                            </span>
                                        </div>
                                    </CardContent>
                                </Card>
                            </div>
                        </div>

                        <!-- Agency Tier -->
                        <div class="mb-10">
                            <div class="flex items-center gap-3 mb-4">
                                <h3 class="text-xl font-semibold">Agency Tier</h3>
                                <Badge class="bg-purple-500 hover:bg-purple-600">+40 points</Badge>
                            </div>
                            <p class="text-muted-foreground mb-4 text-sm">Enterprise pillars for Agency subscribers (175 points max total).</p>
                            <div class="space-y-4">
                                <Card v-for="pillar in agencyPillars" :key="pillar.name" class="border-purple-500/30">
                                    <CardHeader class="pb-2">
                                        <div class="flex items-center justify-between">
                                            <CardTitle class="text-lg flex items-center gap-2">
                                                <component :is="pillar.icon" class="h-5 w-5 text-purple-500" />
                                                {{ pillar.name }}
                                            </CardTitle>
                                            <Badge class="bg-purple-500 hover:bg-purple-600">{{ pillar.points }} pts</Badge>
                                        </div>
                                    </CardHeader>
                                    <CardContent>
                                        <p class="text-muted-foreground mb-3">{{ pillar.description }}</p>
                                        <div class="flex flex-wrap gap-2">
                                            <span
                                                v-for="example in pillar.examples"
                                                :key="example"
                                                class="inline-flex items-center gap-1 px-2 py-1 rounded bg-purple-500/10 text-sm"
                                            >
                                                <CheckCircle class="h-3 w-3 text-purple-500" />
                                                {{ example }}
                                            </span>
                                        </div>
                                    </CardContent>
                                </Card>
                            </div>
                        </div>

                        <!-- Summary Table -->
                        <Card class="mt-8">
                            <CardHeader>
                                <CardTitle>Scoring Summary by Plan</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div class="overflow-x-auto">
                                    <table class="w-full border-collapse text-sm">
                                        <thead>
                                            <tr class="border-b">
                                                <th class="py-2 px-3 text-left font-semibold">Plan</th>
                                                <th class="py-2 px-3 text-left font-semibold">Pillars</th>
                                                <th class="py-2 px-3 text-left font-semibold">Max Score</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr class="border-b">
                                                <td class="py-2 px-3"><Badge variant="secondary">Free</Badge></td>
                                                <td class="py-2 px-3">5 pillars (Definitions, Structure, Authority, Machine-Readable, Answerability)</td>
                                                <td class="py-2 px-3 font-mono font-bold">100 pts</td>
                                            </tr>
                                            <tr class="border-b">
                                                <td class="py-2 px-3"><Badge class="bg-blue-500">Pro</Badge></td>
                                                <td class="py-2 px-3">8 pillars (+E-E-A-T, Citations, AI Accessibility)</td>
                                                <td class="py-2 px-3 font-mono font-bold">135 pts</td>
                                            </tr>
                                            <tr>
                                                <td class="py-2 px-3"><Badge class="bg-purple-500">Agency</Badge></td>
                                                <td class="py-2 px-3">12 pillars (+Freshness, Readability, Question Coverage, Multimedia)</td>
                                                <td class="py-2 px-3 font-mono font-bold">175 pts</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </CardContent>
                        </Card>
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

                        <p class="text-muted-foreground mb-6">
                            Each pillar has specific optimization strategies. Here are the key areas to focus on:
                        </p>

                        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            <Card>
                                <CardHeader class="pb-2">
                                    <CardTitle class="text-base flex items-center gap-2">
                                        <BookOpen class="h-4 w-4 text-primary" />
                                        Clear Definitions
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <p class="text-sm text-muted-foreground">
                                        Start with explicit "X is..." definitions in the first paragraph.
                                    </p>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader class="pb-2">
                                    <CardTitle class="text-base flex items-center gap-2">
                                        <Layers class="h-4 w-4 text-primary" />
                                        Structure
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <p class="text-sm text-muted-foreground">
                                        Use one H1, multiple H2s, and bullet/numbered lists.
                                    </p>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader class="pb-2">
                                    <CardTitle class="text-base flex items-center gap-2">
                                        <Code class="h-4 w-4 text-primary" />
                                        Machine-Readable
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <p class="text-sm text-muted-foreground">
                                        Add JSON-LD schema and <Link href="/resources/why-llms-txt-matters" class="text-primary hover:underline">llms.txt</Link>.
                                    </p>
                                </CardContent>
                            </Card>

                            <Link href="/resources/e-e-a-t-and-geo" class="block">
                                <Card class="h-full border-blue-500/30 hover:border-blue-500/50 transition-colors">
                                    <CardHeader class="pb-2">
                                        <CardTitle class="text-base flex items-center gap-2">
                                            <UserCheck class="h-4 w-4 text-blue-500" />
                                            E-E-A-T Signals
                                            <Badge class="bg-blue-500 text-xs">Pro</Badge>
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <p class="text-sm text-muted-foreground">
                                            Add author bios, credentials, and trust indicators.
                                        </p>
                                    </CardContent>
                                </Card>
                            </Link>

                            <Link href="/resources/ai-citations-and-geo" class="block">
                                <Card class="h-full border-blue-500/30 hover:border-blue-500/50 transition-colors">
                                    <CardHeader class="pb-2">
                                        <CardTitle class="text-base flex items-center gap-2">
                                            <Quote class="h-4 w-4 text-blue-500" />
                                            Citations
                                            <Badge class="bg-blue-500 text-xs">Pro</Badge>
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <p class="text-sm text-muted-foreground">
                                            Link to authoritative sources with inline citations.
                                        </p>
                                    </CardContent>
                                </Card>
                            </Link>

                            <Link href="/resources/ai-accessibility-for-geo" class="block">
                                <Card class="h-full border-blue-500/30 hover:border-blue-500/50 transition-colors">
                                    <CardHeader class="pb-2">
                                        <CardTitle class="text-base flex items-center gap-2">
                                            <Bot class="h-4 w-4 text-blue-500" />
                                            AI Access
                                            <Badge class="bg-blue-500 text-xs">Pro</Badge>
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <p class="text-sm text-muted-foreground">
                                            Allow AI crawlers in robots.txt.
                                        </p>
                                    </CardContent>
                                </Card>
                            </Link>

                            <Link href="/resources/content-freshness-for-geo" class="block">
                                <Card class="h-full border-purple-500/30 hover:border-purple-500/50 transition-colors">
                                    <CardHeader class="pb-2">
                                        <CardTitle class="text-base flex items-center gap-2">
                                            <Clock class="h-4 w-4 text-purple-500" />
                                            Freshness
                                            <Badge class="bg-purple-500 text-xs">Agency</Badge>
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <p class="text-sm text-muted-foreground">
                                            Show publish and update dates.
                                        </p>
                                    </CardContent>
                                </Card>
                            </Link>

                            <Link href="/resources/readability-and-geo" class="block">
                                <Card class="h-full border-purple-500/30 hover:border-purple-500/50 transition-colors">
                                    <CardHeader class="pb-2">
                                        <CardTitle class="text-base flex items-center gap-2">
                                            <Type class="h-4 w-4 text-purple-500" />
                                            Readability
                                            <Badge class="bg-purple-500 text-xs">Agency</Badge>
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <p class="text-sm text-muted-foreground">
                                            Write at 8th-9th grade level with short sentences.
                                        </p>
                                    </CardContent>
                                </Card>
                            </Link>

                            <Link href="/resources/question-coverage-for-geo" class="block">
                                <Card class="h-full border-purple-500/30 hover:border-purple-500/50 transition-colors">
                                    <CardHeader class="pb-2">
                                        <CardTitle class="text-base flex items-center gap-2">
                                            <HelpCircle class="h-4 w-4 text-purple-500" />
                                            Questions
                                            <Badge class="bg-purple-500 text-xs">Agency</Badge>
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <p class="text-sm text-muted-foreground">
                                            Add FAQ sections with FAQPage schema.
                                        </p>
                                    </CardContent>
                                </Card>
                            </Link>
                        </div>

                        <div class="mt-8 flex flex-wrap gap-4">
                            <Link href="/geo-optimization-checklist">
                                <Button variant="outline" class="gap-2">
                                    View Complete Optimization Checklist
                                    <ArrowRight class="h-4 w-4" />
                                </Button>
                            </Link>
                            <Link href="/resources/multimedia-and-geo">
                                <Button variant="ghost" class="gap-2">
                                    Learn About Multimedia & GEO
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
