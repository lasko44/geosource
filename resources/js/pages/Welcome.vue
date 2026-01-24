<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
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
    Mail,
    Shield,
    Eye,
    Bell,
    ChevronDown,
    ChevronUp,
} from 'lucide-vue-next';
import { ref } from 'vue';

withDefaults(
    defineProps<{
        canRegister: boolean;
    }>(),
    {
        canRegister: true,
    },
);

// FAQ accordion state
const openFaqIndex = ref<number | null>(null);

const toggleFaq = (index: number) => {
    openFaqIndex.value = openFaqIndex.value === index ? null : index;
};

const geoScoreFactors = [
    { icon: BookOpen, text: 'Definition clarity' },
    { icon: MessageSquare, text: 'Answerability' },
    { icon: Target, text: 'Topic authority' },
    { icon: FileText, text: 'Machine readability' },
    { icon: Shield, text: 'E-E-A-T signals' },
    { icon: LinkIcon, text: 'Citation quality' },
];

const scanFeatures = [
    'GEO score from 0-100',
    'Letter grades (A+ to C-)',
    'AI-simulated content analysis',
    'Clear improvement recommendations',
    'Re-scan to track progress',
];

const aiUnderstandingPoints = [
    { icon: Brain, text: 'Understandable', description: 'AI can parse and comprehend your content' },
    { icon: Shield, text: 'Trustworthy', description: 'Your content signals authority and credibility' },
    { icon: FileText, text: 'Structured for AI citation', description: 'Formatted for easy extraction and reference' },
];

const citationPlatforms = [
    { name: 'ChatGPT', color: 'bg-green-500' },
    { name: 'Perplexity', color: 'bg-teal-500' },
    { name: 'Claude', color: 'bg-orange-500' },
];

const planFeatures = [
    { plan: 'Free', description: 'Free plan available' },
    { plan: 'Pro', description: 'Pro for founders and creators' },
    { plan: 'Agency', description: 'Agency tools for scale and reporting' },
];

const faqItems = [
    {
        question: 'What is Generative Engine Optimization (GEO)?',
        answer: 'Generative Engine Optimization (GEO) is the process of optimizing website content so AI systems like ChatGPT, Perplexity, Claude, and Gemini can clearly understand, trust, and reference it when generating answers. Unlike SEO, GEO focuses on structure, definitions, answerability, authority, and machine readability.',
    },
    {
        question: 'How is GEO different from SEO?',
        answer: 'SEO focuses on ranking pages in search engine results. GEO focuses on whether AI models can confidently summarize, cite, or reference your content inside generated responses. A page can rank well in Google but still be invisible to AI systems.',
    },
    {
        question: 'What is a GEO score?',
        answer: 'A GEO score is a numerical rating from 0 to 100 that measures how optimized a webpage is for AI search engines. GeoSource.ai calculates this score using 12 AI evaluation pillars, including definition clarity, topic authority, citation quality, and answerability.',
    },
    {
        question: 'Which AI platforms does GeoSource.ai support?',
        answer: 'GeoSource.ai analyzes content for visibility in major AI platforms, including ChatGPT (OpenAI), Perplexity, Claude, and Gemini. The analysis simulates how large language models evaluate content.',
    },
    {
        question: 'Can GeoSource.ai help my site appear in AI answers?',
        answer: 'Yes. GeoSource.ai identifies the specific issues that prevent AI systems from using your content and provides actionable recommendations to improve AI readability and trust. While no tool can guarantee citations, GEO optimization significantly increases your likelihood of being referenced.',
    },
    {
        question: 'Does GEO replace SEO?',
        answer: 'No. GEO complements SEO. SEO helps users find your page. GEO helps AI systems use your page. Modern search strategies require both.',
    },
    {
        question: 'Who is GeoSource.ai for?',
        answer: 'GeoSource.ai is designed for SaaS founders, content creators, SEO professionals, digital agencies, publishers preparing for AI search, and anyone who wants visibility inside AI-generated results.',
    },
];
</script>

<template>
    <Head title="GEO Scoring Platform for AI Search Visibility | GeoSource.ai">
        <meta name="description" content="GeoSource.ai helps you measure and improve how visible your content is inside AI search engines like ChatGPT, Perplexity, Claude, and Gemini. Scan any URL. Get a GEO score. Fix what AI systems don't understand." />
        <meta property="og:title" content="Generative Engine Optimization (GEO) Scoring Platform for AI Search Visibility" />
        <meta property="og:description" content="Measure and improve your website's visibility to AI search systems. Get your GEO Score and actionable optimization recommendations." />
        <meta property="og:type" content="website" />
        <meta property="og:url" content="https://geosource.ai/" />
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:site" content="@geosource_ai" />
        <meta name="twitter:creator" content="@geosource_ai" />
        <meta name="twitter:title" content="GeoSource.ai - GEO Scoring Platform for AI Search Visibility" />
        <meta name="twitter:description" content="Measure and improve your website's visibility to AI search systems." />
        <link rel="canonical" href="https://geosource.ai/" />
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>

    <div class="min-h-screen bg-background text-foreground">
        <!-- Skip to main content link for accessibility -->
        <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-50 focus:px-4 focus:py-2 focus:bg-primary focus:text-primary-foreground focus:rounded">
            Skip to main content
        </a>

        <!-- Navigation -->
        <header class="sticky top-0 z-50 border-b bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60" role="banner">
            <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-2">
                    <Globe class="h-8 w-8 text-primary" aria-hidden="true" />
                    <span class="text-xl font-bold">GeoSource.ai</span>
                </div>

                <!-- Desktop Navigation -->
                <nav class="hidden items-center gap-2 sm:flex" aria-label="Main navigation">
                    <Link href="/pricing">
                        <Button variant="ghost">Pricing</Button>
                    </Link>
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
                            <Button variant="ghost" size="icon" aria-label="Open menu">
                                <Menu class="h-5 w-5" aria-hidden="true" />
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

        <main id="main-content" role="main">
            <!-- Hero Section -->
            <section class="relative overflow-hidden py-20 sm:py-32" aria-labelledby="hero-heading">
                <div class="absolute inset-0 -z-10 bg-[radial-gradient(45%_40%_at_50%_60%,hsl(var(--primary)/0.12),transparent)]" aria-hidden="true" />
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="mx-auto max-w-3xl text-center">
                        <Badge variant="secondary" class="mb-6">
                            <Sparkles class="mr-1 h-3 w-3" aria-hidden="true" />
                            AI-Powered Analysis
                        </Badge>
                        <h1 id="hero-heading" class="text-4xl font-bold tracking-tight sm:text-6xl">
                            <span class="sr-only">Generative Engine Optimization (GEO) Scoring Platform for AI Search Visibility: </span>
                            Optimize Your Website for AI Search
                        </h1>
                        <p class="mt-6 text-lg leading-8 text-muted-foreground">
                            GeoSource.ai helps you measure and improve how visible your content is inside AI search engines like ChatGPT, Perplexity, Claude, and Gemini.
                        </p>
                        <p class="mt-4 text-base font-medium text-foreground">
                            Scan any URL. Get a GEO score. Fix what AI systems don't understand.
                        </p>
                        <div class="mt-10 flex flex-col items-center gap-4">
                            <Link :href="register()">
                                <Button size="lg" class="gap-2">
                                    Start Free GEO Scan
                                    <ArrowRight class="h-4 w-4" aria-hidden="true" />
                                </Button>
                            </Link>
                            <p class="text-sm text-muted-foreground">No credit card required</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- SEO vs GEO Section -->
            <section class="border-t bg-muted/30 py-20" aria-labelledby="seo-geo-heading">
                <article class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="mx-auto max-w-3xl">
                        <h2 id="seo-geo-heading" class="text-center text-3xl font-bold tracking-tight sm:text-4xl">
                            SEO Shows Rankings. GEO Shows AI Visibility.
                        </h2>
                        <p class="mt-6 text-center text-lg text-muted-foreground">
                            AI search doesn't rank pages — it selects answers.
                        </p>
                        <p class="mt-4 text-center text-base text-muted-foreground">
                            GeoSource.ai analyzes your content the same way AI models do and shows whether your site is:
                        </p>
                        <ul class="mt-8 grid gap-4 sm:grid-cols-3" role="list" aria-label="AI visibility criteria">
                            <li v-for="point in aiUnderstandingPoints" :key="point.text">
                                <Card class="text-center h-full">
                                    <CardContent class="flex flex-col items-center pt-6">
                                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-primary/10">
                                            <component :is="point.icon" class="h-6 w-6 text-primary" aria-hidden="true" />
                                        </div>
                                        <h3 class="mt-3 font-semibold">{{ point.text }}</h3>
                                        <p class="mt-2 text-sm text-muted-foreground">{{ point.description }}</p>
                                    </CardContent>
                                </Card>
                            </li>
                        </ul>
                        <aside class="mt-12 rounded-lg border bg-card p-8 text-center" role="note">
                            <p class="text-lg text-muted-foreground">
                                If AI can't confidently summarize your content,
                            </p>
                            <p class="mt-2 text-2xl font-bold text-primary">
                                it won't reference it.
                            </p>
                        </aside>
                    </div>
                </article>
            </section>

            <!-- What GeoSource.ai Does Section -->
            <section class="border-t py-20" aria-labelledby="scanning-heading">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="mx-auto max-w-3xl">
                        <h2 id="scanning-heading" class="text-center text-3xl font-bold tracking-tight sm:text-4xl">
                            GEO Scanning & AI Content Analysis
                        </h2>
                        <h3 class="mt-8 text-center text-xl font-semibold">
                            Scan Any Page for AI Readiness
                        </h3>
                        <p class="mt-4 text-center text-lg text-muted-foreground">
                            Enter a URL and instantly see how well your content performs inside AI systems.
                        </p>
                        <Card class="mt-8 border-primary/50 bg-primary/5">
                            <CardContent class="pt-6">
                                <ul class="space-y-3" role="list" aria-label="GEO scanning features">
                                    <li v-for="feature in scanFeatures" :key="feature" class="flex items-center gap-3">
                                        <CheckCircle class="h-5 w-5 text-green-500 shrink-0" aria-hidden="true" />
                                        <span>{{ feature }}</span>
                                    </li>
                                </ul>
                            </CardContent>
                        </Card>
                        <h3 class="mt-8 text-center text-lg font-semibold">
                            Track Improvements Over Time
                        </h3>
                        <p class="mt-4 text-center text-base text-muted-foreground">
                            Built specifically for generative search. Re-scan pages after making improvements to measure your progress.
                        </p>
                    </div>
                </div>
            </section>

            <!-- GEO Score Explained Section -->
            <section class="border-t bg-muted/30 py-20" aria-labelledby="score-heading">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="mx-auto max-w-3xl">
                        <h2 id="score-heading" class="text-center text-3xl font-bold tracking-tight sm:text-4xl">
                            How GeoSource.ai Calculates Your GEO Score
                        </h2>
                        <h3 class="mt-8 text-center text-xl font-semibold">
                            12 AI Ranking Factors Explained
                        </h3>
                        <p class="mt-4 text-center text-lg text-muted-foreground">
                            GeoSource.ai evaluates content using 12 GEO pillars including:
                        </p>
                        <ul class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3" role="list" aria-label="GEO score factors">
                            <li v-for="factor in geoScoreFactors" :key="factor.text">
                                <Card class="text-center h-full">
                                    <CardContent class="flex flex-col items-center pt-6">
                                        <component :is="factor.icon" class="h-8 w-8 text-primary" aria-hidden="true" />
                                        <p class="mt-3 font-medium">{{ factor.text }}</p>
                                    </CardContent>
                                </Card>
                            </li>
                        </ul>
                        <div class="mt-8 grid gap-6 md:grid-cols-2">
                            <Card>
                                <CardHeader>
                                    <CardTitle class="text-lg">Content Structure and Answerability</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <p class="text-muted-foreground">Evaluates how well your content is organized for AI extraction and direct answer generation.</p>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardHeader>
                                    <CardTitle class="text-lg">E-E-A-T and Citation Signals</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <p class="text-muted-foreground">Measures experience, expertise, authoritativeness, and trustworthiness signals that AI uses to select sources.</p>
                                </CardContent>
                            </Card>
                        </div>
                        <p class="mt-8 text-center text-lg font-medium text-foreground">
                            Each pillar reveals exactly why AI systems struggle to use your content.
                        </p>
                    </div>
                </div>
            </section>

            <!-- Recommendations Section -->
            <section class="border-t py-20" aria-labelledby="recommendations-heading">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="mx-auto max-w-3xl">
                        <h2 id="recommendations-heading" class="text-center text-3xl font-bold tracking-tight sm:text-4xl">
                            Actionable GEO Optimization Recommendations
                        </h2>
                        <h3 class="mt-8 text-center text-xl font-semibold">
                            Fix What AI Doesn't Understand
                        </h3>
                        <p class="mt-4 text-center text-lg text-muted-foreground">
                            Every scan includes actionable recommendations:
                        </p>
                        <div class="mt-8 grid gap-4 sm:grid-cols-3">
                            <Card>
                                <CardContent class="flex flex-col items-center pt-6 text-center">
                                    <Target class="h-8 w-8 text-primary" aria-hidden="true" />
                                    <h4 class="mt-3 font-semibold">Personalized fixes</h4>
                                    <p class="mt-2 text-sm text-muted-foreground">Tailored to your specific content issues</p>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardContent class="flex flex-col items-center pt-6 text-center">
                                    <BarChart3 class="h-8 w-8 text-primary" aria-hidden="true" />
                                    <h4 class="mt-3 font-semibold">Priority levels</h4>
                                    <p class="mt-2 text-sm text-muted-foreground">High, medium, and low impact rankings</p>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardContent class="flex flex-col items-center pt-6 text-center">
                                    <Zap class="h-8 w-8 text-primary" aria-hidden="true" />
                                    <h4 class="mt-3 font-semibold">Clear Steps to Improve AI Visibility</h4>
                                    <p class="mt-2 text-sm text-muted-foreground">Apply improvements immediately</p>
                                </CardContent>
                            </Card>
                        </div>
                        <p class="mt-8 text-center text-lg font-medium text-foreground">
                            No vague SEO advice. Only AI-focused improvements.
                        </p>
                    </div>
                </div>
            </section>

            <!-- AI Citation Tracking Section -->
            <section class="border-t bg-muted/30 py-20" aria-labelledby="citation-heading">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="mx-auto max-w-3xl">
                        <h2 id="citation-heading" class="text-center text-3xl font-bold tracking-tight sm:text-4xl">
                            AI Citation Tracking & Monitoring
                        </h2>
                        <h3 class="mt-8 text-center text-xl font-semibold">
                            See If AI Is Actually Mentioning You
                        </h3>
                        <Badge variant="outline" class="mx-auto mt-4 flex w-fit">
                            Agency Plan
                        </Badge>
                        <div class="mt-8">
                            <h4 class="text-center text-lg font-medium">Track Citations Across ChatGPT, Perplexity, and Claude</h4>
                            <div class="mt-6 flex flex-wrap justify-center gap-4">
                                <div v-for="platform in citationPlatforms" :key="platform.name" class="flex items-center gap-2">
                                    <span :class="[platform.color, 'h-3 w-3 rounded-full']" aria-hidden="true"></span>
                                    <span class="font-medium">{{ platform.name }}</span>
                                </div>
                            </div>
                        </div>
                        <Card class="mt-8">
                            <CardContent class="pt-6">
                                <ul class="space-y-3">
                                    <li class="flex items-start gap-3">
                                        <Bell class="h-5 w-5 text-primary shrink-0 mt-0.5" aria-hidden="true" />
                                        <span>Get alerts when citations appear or disappear</span>
                                    </li>
                                    <li class="flex items-start gap-3">
                                        <Eye class="h-5 w-5 text-primary shrink-0 mt-0.5" aria-hidden="true" />
                                        <span>See the exact AI responses that reference your site</span>
                                    </li>
                                    <li class="flex items-start gap-3">
                                        <TrendingUp class="h-5 w-5 text-primary shrink-0 mt-0.5" aria-hidden="true" />
                                        <span>Monitor competitor AI visibility</span>
                                    </li>
                                </ul>
                            </CardContent>
                        </Card>
                        <p class="mt-8 text-center text-lg font-medium text-primary">
                            Know when your brand becomes part of AI answers.
                        </p>
                    </div>
                </div>
            </section>

            <!-- AI Traffic Analytics Section -->
            <section class="border-t py-20" aria-labelledby="analytics-heading">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="mx-auto max-w-3xl">
                        <h2 id="analytics-heading" class="text-center text-3xl font-bold tracking-tight sm:text-4xl">
                            AI Traffic Analytics Using GA4
                        </h2>
                        <h3 class="mt-8 text-center text-xl font-semibold">
                            Track Real Traffic From AI Platforms
                        </h3>
                        <Badge variant="outline" class="mx-auto mt-4 flex w-fit">
                            Agency Plan
                        </Badge>
                        <Card class="mt-8 border-primary/50 bg-primary/5">
                            <CardContent class="pt-6 text-center">
                                <BarChart3 class="mx-auto h-12 w-12 text-primary" aria-hidden="true" />
                                <h4 class="mt-4 text-lg font-semibold">Measure Traffic From AI Platforms</h4>
                                <p class="mt-4 text-muted-foreground">
                                    Connect GA4 and monitor visits coming from AI tools like ChatGPT and Perplexity.
                                </p>
                                <h4 class="mt-6 text-lg font-semibold">Understand AI Search ROI</h4>
                                <p class="mt-2 text-muted-foreground">
                                    Finally understand whether AI visibility turns into real users.
                                </p>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </section>

            <!-- Plans Section -->
            <section class="border-t bg-muted/30 py-20" aria-labelledby="plans-heading">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="mx-auto max-w-3xl">
                        <h2 id="plans-heading" class="text-center text-3xl font-bold tracking-tight sm:text-4xl">
                            Plans for Founders, Teams, and Agencies
                        </h2>
                        <h3 class="mt-8 text-center text-xl font-semibold">
                            Start Free. Upgrade When You're Ready.
                        </h3>
                        <div class="mt-8 grid gap-4 sm:grid-cols-3">
                            <Card v-for="item in planFeatures" :key="item.plan">
                                <CardContent class="flex flex-col items-center pt-6 text-center">
                                    <h4 class="text-lg font-bold" :class="item.plan === 'Pro' ? 'text-blue-600 dark:text-blue-400' : item.plan === 'Agency' ? 'text-primary' : ''">
                                        {{ item.plan }}
                                    </h4>
                                    <p class="mt-2 text-sm text-muted-foreground">{{ item.description }}</p>
                                </CardContent>
                            </Card>
                        </div>
                        <p class="mt-8 text-center text-lg font-medium text-foreground">
                            Optimize once. Track forever.
                        </p>
                        <div class="mt-8 flex justify-center">
                            <Link href="/pricing">
                                <Button variant="outline" size="lg">
                                    View Plans
                                    <ArrowRight class="ml-2 h-4 w-4" aria-hidden="true" />
                                </Button>
                            </Link>
                        </div>
                    </div>
                </div>
            </section>

            <!-- FAQ Section -->
            <section class="border-t py-20" aria-labelledby="faq-heading">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="mx-auto max-w-3xl">
                        <h2 id="faq-heading" class="text-center text-3xl font-bold tracking-tight sm:text-4xl">
                            Frequently Asked Questions About GEO
                        </h2>
                        <div class="mt-8 space-y-4" role="list" aria-label="Frequently asked questions">
                            <div
                                v-for="(faq, index) in faqItems"
                                :key="index"
                                class="rounded-lg border bg-card"
                                role="listitem"
                            >
                                <button
                                    :id="`faq-button-${index}`"
                                    class="flex w-full items-center justify-between p-6 text-left"
                                    :aria-expanded="openFaqIndex === index"
                                    :aria-controls="`faq-panel-${index}`"
                                    @click="toggleFaq(index)"
                                >
                                    <span class="font-semibold pr-4">{{ faq.question }}</span>
                                    <ChevronDown
                                        v-if="openFaqIndex !== index"
                                        class="h-5 w-5 shrink-0 text-muted-foreground"
                                        aria-hidden="true"
                                    />
                                    <ChevronUp
                                        v-else
                                        class="h-5 w-5 shrink-0 text-muted-foreground"
                                        aria-hidden="true"
                                    />
                                </button>
                                <div
                                    v-show="openFaqIndex === index"
                                    :id="`faq-panel-${index}`"
                                    :aria-labelledby="`faq-button-${index}`"
                                    class="px-6 pb-6"
                                    role="region"
                                >
                                    <p class="text-muted-foreground">{{ faq.answer }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Closing CTA Section -->
            <section class="border-t bg-muted/30 py-20" aria-labelledby="cta-heading">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="mx-auto max-w-3xl text-center">
                        <h2 id="cta-heading" class="text-3xl font-bold tracking-tight sm:text-4xl">
                            AI Search Is Already Here
                        </h2>
                        <p class="mt-6 text-lg text-muted-foreground">
                            If your content isn't optimized for AI systems, it won't appear in AI-generated answers.
                        </p>
                        <p class="mt-4 text-lg font-medium text-foreground">
                            GeoSource.ai helps you stay visible as search evolves.
                        </p>
                        <div class="mt-10">
                            <Link :href="register()">
                                <Button size="lg" class="gap-2">
                                    Start Your Free GEO Scan
                                    <ArrowRight class="h-4 w-4" aria-hidden="true" />
                                </Button>
                            </Link>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <!-- Footer -->
        <footer class="border-t py-12" role="contentinfo">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col items-center gap-6">
                    <div class="flex items-center gap-4">
                        <div class="flex items-center gap-2 rounded-lg bg-primary/10 px-4 py-2">
                            <Mail class="h-5 w-5 text-primary" aria-hidden="true" />
                            <span class="text-sm font-medium">Need help?</span>
                            <a href="mailto:support@geosource.ai" class="text-sm font-semibold text-primary hover:underline">
                                support@geosource.ai
                            </a>
                        </div>
                        <a href="https://x.com/geosource_ai" target="_blank" rel="noopener noreferrer" class="flex items-center gap-2 rounded-lg bg-primary/10 px-4 py-2 text-sm font-semibold text-primary hover:bg-primary/20 transition-colors" aria-label="Follow GeoSource.ai on X (formerly Twitter)">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                            </svg>
                            <span>Follow us</span>
                        </a>
                    </div>
                    <div class="flex w-full flex-col items-center justify-between gap-4 sm:flex-row">
                        <div class="flex items-center gap-2">
                            <Globe class="h-6 w-6 text-primary" aria-hidden="true" />
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
