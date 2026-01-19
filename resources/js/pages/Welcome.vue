<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import ThemeSwitcher from '@/components/ThemeSwitcher.vue';
import { dashboard, login, register } from '@/routes';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Search,
    Brain,
    Target,
    FileText,
    CheckCircle,
    XCircle,
    Users,
    BarChart3,
    Zap,
    Globe,
    ArrowRight,
    Sparkles,
    BookOpen,
    Database,
    Link as LinkIcon,
    MessageSquare,
    Building2,
    TrendingUp,
    Menu,
} from 'lucide-vue-next';

withDefaults(
    defineProps<{
        canRegister: boolean;
    }>(),
    {
        canRegister: true,
    },
);

const geoFocusItems = [
    { icon: BookOpen, text: 'Clear definitions' },
    { icon: Database, text: 'Structured knowledge' },
    { icon: Target, text: 'Topic authority' },
    { icon: FileText, text: 'Machine-readable formatting' },
    { icon: CheckCircle, text: 'High-confidence answerability' },
];

const evaluationItems = [
    'Definition clarity',
    'Topic structure and hierarchy',
    'FAQ and answer density',
    'Schema and structured data',
    'Internal concept linking',
    'Topical consistency',
    'AI answerability likelihood',
];

const exampleInsights = [
    { status: false, text: 'Missing clear definitions' },
    { status: false, text: 'No FAQ sections detected' },
    { status: true, text: 'Strong topical focus' },
    { status: false, text: 'No structured schema' },
    { status: true, text: 'Clean content hierarchy' },
];

const idealFor = [
    { icon: FileText, text: 'Content creators and publishers' },
    { icon: LinkIcon, text: 'Affiliate site owners' },
    { icon: Building2, text: 'SaaS companies' },
    { icon: Search, text: 'SEO consultants' },
    { icon: Users, text: 'Marketing agencies' },
    { icon: BookOpen, text: 'Knowledge-based businesses' },
];

const howItWorksSteps = [
    {
        step: 1,
        title: 'Submit your website',
        description: 'Enter your domain and start a scan.',
    },
    {
        step: 2,
        title: 'We analyze your content',
        description: 'Pages are crawled, cleaned, embedded, and evaluated.',
    },
    {
        step: 3,
        title: 'We score GEO readiness',
        description: 'Each page and the site overall receive a GEO score.',
    },
    {
        step: 4,
        title: 'You receive clear recommendations',
        description: 'Know exactly what to fix and why it matters.',
    },
    {
        step: 5,
        title: 'Re-scan and track improvement',
        description: 'Measure progress as your GEO score increases.',
    },
];

const combinedBenefits = [
    'Traditional search traffic',
    'AI visibility',
    'Long-term discoverability',
    'Authority across platforms',
];

const aiEraFeatures = [
    'Generative AI retrieval',
    'Large language model parsing',
    'Answer-based search systems',
    'Citation-driven visibility',
];
</script>

<template>
    <Head title="GeoSource.ai - The GEO Scoring Platform for the AI Search Era">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>

    <div class="min-h-screen bg-background text-foreground">
        <!-- Navigation -->
        <header class="sticky top-0 z-50 border-b bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60">
            <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-2">
                    <Globe class="h-8 w-8 text-primary" />
                    <span class="text-xl font-bold">GeoSource.ai</span>
                </div>

                <!-- Desktop Navigation -->
                <nav class="hidden items-center gap-2 sm:flex">
                    <Link href="/resources">
                        <Button variant="ghost">Resources</Button>
                    </Link>
                    <Link
                        v-if="$page.props.auth.user"
                        :href="dashboard()"
                    >
                        <Button variant="outline">Dashboard</Button>
                    </Link>
                    <template v-else>
                        <Link :href="login()">
                            <Button variant="ghost">Log in</Button>
                        </Link>
                        <Link v-if="canRegister" :href="register()">
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
                                <Link :href="dashboard()" class="w-full">
                                    Dashboard
                                </Link>
                            </DropdownMenuItem>
                            <template v-else>
                                <DropdownMenuItem as-child>
                                    <Link :href="login()" class="w-full">
                                        Log in
                                    </Link>
                                </DropdownMenuItem>
                                <DropdownMenuItem v-if="canRegister" as-child>
                                    <Link :href="register()" class="w-full font-medium text-primary">
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
            <section class="relative overflow-hidden py-20 sm:py-32">
                <div class="absolute inset-0 -z-10 bg-[radial-gradient(45%_40%_at_50%_60%,hsl(var(--primary)/0.12),transparent)]" />
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="mx-auto max-w-3xl text-center">
                        <Badge variant="secondary" class="mb-6">
                            <Sparkles class="mr-1 h-3 w-3" />
                            AI-Powered Analysis
                        </Badge>
                        <h1 class="text-4xl font-bold tracking-tight sm:text-6xl">
                            The GEO Scoring Platform for the AI Search Era
                        </h1>
                        <p class="mt-6 text-lg leading-8 text-muted-foreground">
                            GeoSource.ai helps you understand how visible, trustworthy, and cite-ready your website is for generative AI systems like ChatGPT, Perplexity, and Claude.
                        </p>
                        <p class="mt-4 text-base text-muted-foreground">
                            As search shifts from links to answers, <strong class="text-foreground">Generative Engine Optimization (GEO)</strong> determines whether AI systems reference your site — or ignore it.
                        </p>
                        <p class="mt-4 text-lg font-medium text-foreground">
                            GeoSource.ai shows you exactly where you stand and how to improve.
                        </p>
                        <div class="mt-10 flex items-center justify-center gap-4">
                            <Link :href="register()">
                                <Button size="lg" class="gap-2">
                                    Start Free Analysis
                                    <ArrowRight class="h-4 w-4" />
                                </Button>
                            </Link>
                            <Link href="/resources">
                                <Button size="lg" variant="outline">Learn More</Button>
                            </Link>
                        </div>
                    </div>
                </div>
            </section>

            <!-- What is GEO Section -->
            <section class="border-t bg-muted/30 py-20" aria-labelledby="what-is-geo">
                <article class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="mx-auto max-w-3xl">
                        <h2 id="what-is-geo" class="text-center text-3xl font-bold tracking-tight sm:text-4xl">
                            What Is GEO (Generative Engine Optimization)?
                        </h2>
                        <p class="mt-6 text-center text-lg text-muted-foreground">
                            <dfn id="geo-definition"><strong>Generative Engine Optimization (GEO)</strong></dfn> is the practice of structuring website content so it can be accurately retrieved, understood, and cited by AI-powered search engines.
                        </p>
                        <p class="mt-4 text-center text-base text-muted-foreground">
                            Unlike traditional SEO, GEO focuses on:
                        </p>
                        <ul class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3" role="list">
                            <li v-for="item in geoFocusItems" :key="item.text">
                                <Card class="text-center h-full">
                                    <CardContent class="flex flex-col items-center pt-6">
                                        <component :is="item.icon" class="h-8 w-8 text-primary" aria-hidden="true" />
                                        <p class="mt-3 font-medium">{{ item.text }}</p>
                                    </CardContent>
                                </Card>
                            </li>
                        </ul>
                        <aside class="mt-12 rounded-lg border bg-card p-8 text-center">
                            <p class="text-lg text-muted-foreground">
                                AI systems do not rank pages.
                            </p>
                            <p class="mt-2 text-2xl font-bold text-primary">
                                They select sources.
                            </p>
                            <p class="mt-4 text-base text-muted-foreground">
                                GeoSource.ai helps your site become one of them.
                            </p>
                        </aside>
                    </div>
                </article>
            </section>

            <!-- Why GEO Matters Section -->
            <section class="border-t py-20">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="mx-auto max-w-3xl">
                        <h2 class="text-center text-3xl font-bold tracking-tight sm:text-4xl">
                            Why GEO Matters Now
                        </h2>
                        <p class="mt-6 text-center text-lg font-medium text-foreground">
                            Search behavior is changing fast.
                        </p>
                        <div class="mt-8 grid gap-6 md:grid-cols-2">
                            <Card>
                                <CardHeader>
                                    <CardTitle class="text-lg">The Shift</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <ul class="space-y-3 text-muted-foreground">
                                        <li class="flex items-start gap-2">
                                            <Brain class="mt-0.5 h-5 w-5 shrink-0 text-primary" />
                                            <span>Users ask AI instead of Google</span>
                                        </li>
                                        <li class="flex items-start gap-2">
                                            <MessageSquare class="mt-0.5 h-5 w-5 shrink-0 text-primary" />
                                            <span>AI summarizes instead of listing links</span>
                                        </li>
                                        <li class="flex items-start gap-2">
                                            <Target class="mt-0.5 h-5 w-5 shrink-0 text-primary" />
                                            <span>Only a few trusted sources are referenced</span>
                                        </li>
                                    </ul>
                                </CardContent>
                            </Card>
                            <Card class="border-destructive/50">
                                <CardHeader>
                                    <CardTitle class="text-lg">If your site is not optimized for GEO:</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <ul class="space-y-3 text-muted-foreground">
                                        <li class="flex items-start gap-2">
                                            <XCircle class="mt-0.5 h-5 w-5 shrink-0 text-destructive" />
                                            <span>Your content may exist but never be cited</span>
                                        </li>
                                        <li class="flex items-start gap-2">
                                            <XCircle class="mt-0.5 h-5 w-5 shrink-0 text-destructive" />
                                            <span>Your expertise may be invisible to AI</span>
                                        </li>
                                        <li class="flex items-start gap-2">
                                            <XCircle class="mt-0.5 h-5 w-5 shrink-0 text-destructive" />
                                            <span>Your traffic may decline even with strong SEO rankings</span>
                                        </li>
                                    </ul>
                                </CardContent>
                            </Card>
                        </div>
                        <div class="mt-12 rounded-lg border bg-card p-8 text-center">
                            <p class="text-lg text-muted-foreground">SEO brings clicks.</p>
                            <p class="mt-2 text-2xl font-bold text-primary">GEO earns citations.</p>
                            <p class="mt-4 text-base font-medium text-foreground">Modern websites need both.</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- What GeoSource.ai Does Section -->
            <section class="border-t bg-muted/30 py-20">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="mx-auto max-w-3xl">
                        <h2 class="text-center text-3xl font-bold tracking-tight sm:text-4xl">
                            What GeoSource.ai Does
                        </h2>
                        <p class="mt-6 text-center text-lg text-muted-foreground">
                            GeoSource.ai analyzes your website the same way AI systems do.
                        </p>
                        <p class="mt-4 text-center text-base text-muted-foreground">
                            We scan your pages, extract structured knowledge, and evaluate how easily AI models can retrieve and trust your content.
                        </p>
                        <div class="mt-8 flex justify-center">
                            <Card class="w-full max-w-md border-primary/50 bg-primary/5">
                                <CardContent class="pt-6 text-center">
                                    <BarChart3 class="mx-auto h-12 w-12 text-primary" />
                                    <p class="mt-4 text-lg font-medium">
                                        You receive a clear <dfn class="text-primary not-italic">GEO Score (0–100)</dfn> with actionable insights.
                                    </p>
                                </CardContent>
                            </Card>
                        </div>
                        <div class="mt-12">
                            <h3 class="text-center text-xl font-semibold">GeoSource.ai evaluates:</h3>
                            <div class="mt-6 flex flex-wrap justify-center gap-2">
                                <Badge v-for="item in evaluationItems" :key="item" variant="outline" class="px-4 py-2 text-sm">
                                    {{ item }}
                                </Badge>
                            </div>
                        </div>
                        <div class="mt-12 rounded-lg border bg-card p-8 text-center">
                            <p class="text-lg text-muted-foreground">No guesswork.</p>
                            <p class="text-lg text-muted-foreground">No vague recommendations.</p>
                            <p class="mt-4 text-xl font-bold text-primary">Just measurable GEO readiness.</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- GEO Score Explained Section -->
            <section class="border-t py-20">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="mx-auto max-w-3xl">
                        <h2 class="text-center text-3xl font-bold tracking-tight sm:text-4xl">
                            Your GEO Score Explained
                        </h2>
                        <p class="mt-6 text-center text-lg text-muted-foreground">
                            Your GEO Score represents how likely an AI system is to:
                        </p>
                        <div class="mt-8 grid gap-4 sm:grid-cols-2">
                            <Card>
                                <CardContent class="flex items-center gap-4 pt-6">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary/10">
                                        <Brain class="h-5 w-5 text-primary" />
                                    </div>
                                    <p class="font-medium">Understand your content</p>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardContent class="flex items-center gap-4 pt-6">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary/10">
                                        <Search class="h-5 w-5 text-primary" />
                                    </div>
                                    <p class="font-medium">Retrieve it accurately</p>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardContent class="flex items-center gap-4 pt-6">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary/10">
                                        <CheckCircle class="h-5 w-5 text-primary" />
                                    </div>
                                    <p class="font-medium">Use it as a trusted source</p>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardContent class="flex items-center gap-4 pt-6">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary/10">
                                        <FileText class="h-5 w-5 text-primary" />
                                    </div>
                                    <p class="font-medium">Cite or reference your site in generated answers</p>
                                </CardContent>
                            </Card>
                        </div>
                        <p class="mt-8 text-center text-lg font-medium text-foreground">
                            A higher GEO score means your site communicates knowledge clearly — not just keywords.
                        </p>
                    </div>
                </div>
            </section>

            <!-- Example GEO Insights Section -->
            <section class="border-t bg-muted/30 py-20">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="mx-auto max-w-3xl">
                        <h2 class="text-center text-3xl font-bold tracking-tight sm:text-4xl">
                            Example GEO Insights
                        </h2>
                        <p class="mt-6 text-center text-lg text-muted-foreground">
                            GeoSource.ai shows you exactly what AI systems see.
                        </p>
                        <Card class="mt-8">
                            <CardContent class="pt-6">
                                <ul class="space-y-4">
                                    <li
                                        v-for="insight in exampleInsights"
                                        :key="insight.text"
                                        class="flex items-center gap-3"
                                    >
                                        <CheckCircle v-if="insight.status" class="h-5 w-5 text-green-500" />
                                        <XCircle v-else class="h-5 w-5 text-destructive" />
                                        <span :class="insight.status ? 'text-foreground' : 'text-muted-foreground'">
                                            {{ insight.text }}
                                        </span>
                                    </li>
                                </ul>
                            </CardContent>
                        </Card>
                        <p class="mt-8 text-center text-base text-muted-foreground">
                            Each issue includes a recommended fix you can implement immediately.
                        </p>
                    </div>
                </div>
            </section>

            <!-- Built For Section -->
            <section class="border-t py-20">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="mx-auto max-w-3xl">
                        <h2 class="text-center text-3xl font-bold tracking-tight sm:text-4xl">
                            Built for Website Owners, SEOs, and Agencies
                        </h2>
                        <p class="mt-6 text-center text-lg text-muted-foreground">
                            GeoSource.ai is designed for anyone who depends on visibility online.
                        </p>
                        <h3 class="mt-8 text-center text-xl font-semibold">Ideal for:</h3>
                        <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            <Card v-for="item in idealFor" :key="item.text">
                                <CardContent class="flex items-center gap-3 pt-6">
                                    <component :is="item.icon" class="h-5 w-5 text-primary" />
                                    <span class="text-sm font-medium">{{ item.text }}</span>
                                </CardContent>
                            </Card>
                        </div>
                        <p class="mt-8 text-center text-lg font-medium text-foreground">
                            If your site teaches, explains, or informs — GEO matters.
                        </p>
                    </div>
                </div>
            </section>

            <!-- How It Works Section -->
            <section class="border-t bg-muted/30 py-20" aria-labelledby="how-it-works">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="mx-auto max-w-3xl">
                        <h2 id="how-it-works" class="text-center text-3xl font-bold tracking-tight sm:text-4xl">
                            How GeoSource.ai Works
                        </h2>
                        <ol class="mt-12 space-y-6" role="list">
                            <li
                                v-for="step in howItWorksSteps"
                                :key="step.step"
                                class="flex gap-4"
                            >
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary text-primary-foreground font-bold" aria-hidden="true">
                                    {{ step.step }}
                                </div>
                                <div>
                                    <h3 class="font-semibold text-foreground">{{ step.title }}</h3>
                                    <p class="text-muted-foreground">{{ step.description }}</p>
                                </div>
                            </li>
                        </ol>
                    </div>
                </div>
            </section>

            <!-- GEO + SEO Section -->
            <section class="border-t py-20">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="mx-auto max-w-3xl">
                        <h2 class="text-center text-3xl font-bold tracking-tight sm:text-4xl">
                            GEO + SEO: Better Together
                        </h2>
                        <p class="mt-6 text-center text-lg text-muted-foreground">
                            GeoSource.ai does not replace SEO. <strong class="text-foreground">It complements it.</strong>
                        </p>
                        <div class="mt-8 grid gap-6 sm:grid-cols-2">
                            <Card>
                                <CardHeader>
                                    <CardTitle class="flex items-center gap-2 text-lg">
                                        <Search class="h-5 w-5" />
                                        SEO
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <p class="text-muted-foreground">Optimizes for rankings</p>
                                </CardContent>
                            </Card>
                            <Card class="border-primary/50">
                                <CardHeader>
                                    <CardTitle class="flex items-center gap-2 text-lg">
                                        <Brain class="h-5 w-5 text-primary" />
                                        GEO
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <p class="text-muted-foreground">Optimizes for answers</p>
                                </CardContent>
                            </Card>
                        </div>
                        <Card class="mt-8">
                            <CardHeader>
                                <CardTitle class="text-lg">Sites that combine both gain:</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div v-for="benefit in combinedBenefits" :key="benefit" class="flex items-center gap-2">
                                        <CheckCircle class="h-5 w-5 text-green-500" />
                                        <span>{{ benefit }}</span>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                        <p class="mt-8 text-center text-lg font-medium text-primary">
                            This is the future of search optimization.
                        </p>
                    </div>
                </div>
            </section>

            <!-- Designed for AI Era Section -->
            <section class="border-t bg-muted/30 py-20">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="mx-auto max-w-3xl">
                        <h2 class="text-center text-3xl font-bold tracking-tight sm:text-4xl">
                            Designed for the AI Era
                        </h2>
                        <p class="mt-6 text-center text-lg text-muted-foreground">
                            GeoSource.ai is built specifically for:
                        </p>
                        <div class="mt-8 grid gap-4 sm:grid-cols-2">
                            <Card v-for="feature in aiEraFeatures" :key="feature">
                                <CardContent class="flex items-center gap-3 pt-6">
                                    <Zap class="h-5 w-5 text-primary" />
                                    <span class="font-medium">{{ feature }}</span>
                                </CardContent>
                            </Card>
                        </div>
                        <p class="mt-8 text-center text-base text-muted-foreground">
                            We focus on <strong class="text-foreground">clarity, structure, and trust</strong> — the same signals AI systems depend on.
                        </p>
                    </div>
                </div>
            </section>

            <!-- CTA Section -->
            <section class="border-t py-20">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="mx-auto max-w-3xl text-center">
                        <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">
                            Start Measuring Your GEO Readiness
                        </h2>
                        <p class="mt-6 text-lg text-muted-foreground">
                            AI search is already here.
                        </p>
                        <p class="mt-4 text-lg text-foreground">
                            The question is not <em>if</em> GEO matters —<br />
                            it's whether your site is prepared.
                        </p>
                        <div class="mt-10">
                            <Link :href="register()">
                                <Button size="lg" class="gap-2">
                                    Get Your GEO Score
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
