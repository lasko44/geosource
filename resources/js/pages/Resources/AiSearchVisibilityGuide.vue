<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import {
    Globe,
    BookOpen,
    ArrowRight,
    ArrowLeft,
    Eye,
    Brain,
    Search,
    Target,
    FileText,
    CheckCircle,
    XCircle,
    Lightbulb,
    Quote,
    TrendingUp,
    Zap,
    Database,
    MessageSquare,
    Building2,
    Menu,
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

const visibilitySignals = [
    { signal: 'Content clarity', description: 'How clearly your content explains concepts', icon: FileText },
    { signal: 'Topic authority', description: 'Depth and consistency of coverage', icon: Target },
    { signal: 'Definition quality', description: 'Explicit, quotable definitions', icon: Quote },
    { signal: 'Structural consistency', description: 'Predictable, machine-readable formatting', icon: Database },
    { signal: 'Cross-source alignment', description: 'Consistency with other authoritative sources', icon: CheckCircle },
];

const aiPlatforms = [
    { name: 'ChatGPT', company: 'OpenAI', description: 'General-purpose AI assistant with web browsing' },
    { name: 'Claude', company: 'Anthropic', description: 'AI assistant focused on helpfulness and safety' },
    { name: 'Perplexity', company: 'Perplexity AI', description: 'AI-powered search engine with citations' },
    { name: 'Google AI Overviews', company: 'Google', description: 'AI summaries in Google Search results' },
    { name: 'Gemini', company: 'Google', description: 'Multimodal AI assistant' },
    { name: 'Copilot', company: 'Microsoft', description: 'AI assistant integrated with Bing search' },
];

const geoVsSeoTable = [
    { aspect: 'Goal', seo: 'Rank on search results pages', geo: 'Be cited in AI-generated answers' },
    { aspect: 'Target', seo: 'Search engine crawlers', geo: 'Large language models' },
    { aspect: 'Metric', seo: 'Rankings, traffic, CTR', geo: 'Citations, mentions, references' },
    { aspect: 'Optimization', seo: 'Keywords, backlinks, speed', geo: 'Clarity, structure, definitions' },
    { aspect: 'Content style', seo: 'Keyword-optimized', geo: 'Definition-rich, factual' },
    { aspect: 'Success indicator', seo: 'Page 1 rankings', geo: 'Appearing in AI answers' },
];

const whyGeosourceExists = [
    'Traditional SEO tools don\'t measure AI visibility',
    'No standard way to evaluate citation readiness',
    'Businesses are being left out of AI-generated answers',
    'Content optimization for LLMs requires different strategies',
    'The shift to AI search is happening now — preparation is essential',
];

const jsonLd = {
    '@context': 'https://schema.org',
    '@type': 'Article',
    headline: 'AI Search Visibility Guide - The Complete Resource',
    description: 'A comprehensive guide to AI search visibility. Learn how AI search works, the difference between GEO and SEO, visibility signals, and how to optimize for AI citation.',
    url: 'https://geosource.ai/ai-search-visibility-guide',
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
        '@id': 'https://geosource.ai/ai-search-visibility-guide',
    },
    articleSection: 'AI Search Optimization',
    keywords: ['AI visibility', 'GEO', 'generative engine optimization', 'AI search', 'LLM citation', 'ChatGPT', 'AI SEO'],
};

const faqJsonLd = {
    '@context': 'https://schema.org',
    '@type': 'FAQPage',
    mainEntity: [
        {
            '@type': 'Question',
            name: 'What is AI visibility?',
            acceptedAnswer: {
                '@type': 'Answer',
                text: 'AI visibility refers to how frequently and accurately a brand, website, or source appears within generative AI responses across platforms such as ChatGPT, Gemini, Claude, and Perplexity. High AI visibility means a source is consistently selected by AI systems as a trusted reference when answering user questions.',
            },
        },
        {
            '@type': 'Question',
            name: 'How do I improve my AI search visibility?',
            acceptedAnswer: {
                '@type': 'Answer',
                text: 'To improve AI visibility: 1) Add clear, explicit definitions, 2) Structure content with proper headings, 3) Include FAQ sections, 4) Implement structured data markup, 5) Build internal links between related content, 6) Maintain consistent terminology, 7) Focus on depth over breadth in your topic coverage.',
            },
        },
        {
            '@type': 'Question',
            name: 'What AI platforms should I optimize for?',
            acceptedAnswer: {
                '@type': 'Answer',
                text: 'Key AI platforms include ChatGPT (OpenAI), Claude (Anthropic), Perplexity, Google AI Overviews, Gemini (Google), and Microsoft Copilot. Optimizing for GEO principles helps across all these platforms since they use similar LLM-based retrieval and synthesis methods.',
            },
        },
        {
            '@type': 'Question',
            name: 'Is AI visibility the same as SEO?',
            acceptedAnswer: {
                '@type': 'Answer',
                text: 'No. SEO optimizes for search engine rankings (traffic and clicks), while AI visibility focuses on being cited in AI-generated answers. A page can rank #1 on Google but never be mentioned by ChatGPT. Both are important, but they require different optimization strategies.',
            },
        },
        {
            '@type': 'Question',
            name: 'Why does GeoSource.ai exist?',
            acceptedAnswer: {
                '@type': 'Answer',
                text: 'GeoSource.ai exists because traditional SEO tools don\'t measure AI visibility, there\'s no standard way to evaluate citation readiness, and businesses need to prepare for the shift to AI-powered search. GeoSource.ai provides GEO Scores and actionable recommendations to improve AI citation likelihood.',
            },
        },
    ],
};
</script>

<template>
    <Head title="AI Search Visibility Guide - The Complete Resource | GeoSource.ai">
        <meta name="description" content="A comprehensive guide to AI search visibility. Learn how AI search works, the difference between GEO and SEO, visibility signals, and how to optimize for AI citation." />
        <meta property="og:title" content="AI Search Visibility Guide - The Complete Resource" />
        <meta property="og:description" content="Everything you need to know about AI search visibility and how to optimize for generative AI systems." />
        <meta property="og:type" content="article" />
        <meta property="og:url" content="https://geosource.ai/ai-search-visibility-guide" />
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" content="AI Search Visibility Guide - The Complete Resource" />
        <meta name="twitter:description" content="Everything you need to know about AI search visibility and how to optimize for generative AI systems." />
        <link rel="canonical" href="https://geosource.ai/ai-search-visibility-guide" />
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
                        <span class="text-foreground">AI Search Visibility Guide</span>
                    </nav>
                </div>
            </div>

            <!-- Article -->
            <article class="py-12">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <!-- Header -->
                    <header class="mb-12">
                        <Badge variant="secondary" class="mb-4">
                            <BookOpen class="mr-1 h-3 w-3" />
                            Pillar Guide
                        </Badge>
                        <h1 class="text-3xl font-bold tracking-tight sm:text-4xl lg:text-5xl">
                            AI Search Visibility Guide
                        </h1>
                        <p class="mt-4 text-lg text-muted-foreground">
                            The complete resource for understanding and optimizing your presence in AI-generated answers.
                        </p>
                        <div class="mt-4 flex items-center gap-2 text-sm text-muted-foreground">
                            <Calendar class="h-4 w-4" />
                            <span>{{ publishedDate }}</span>
                        </div>
                    </header>

                    <!-- Table of Contents -->
                    <nav class="mb-12 p-6 rounded-lg border bg-muted/30" aria-labelledby="toc">
                        <h2 id="toc" class="font-semibold mb-4">In This Guide</h2>
                        <ol class="space-y-2 text-sm">
                            <li><a href="#what-is-ai-visibility" class="text-primary hover:underline">1. What Is AI Visibility?</a></li>
                            <li><a href="#how-ai-search-works" class="text-primary hover:underline">2. How AI Search Works</a></li>
                            <li><a href="#geo-vs-seo" class="text-primary hover:underline">3. GEO vs SEO: Key Differences</a></li>
                            <li><a href="#visibility-signals" class="text-primary hover:underline">4. AI Visibility Signals</a></li>
                            <li><a href="#ai-platforms" class="text-primary hover:underline">5. Major AI Platforms</a></li>
                            <li><a href="#citation-examples" class="text-primary hover:underline">6. Citation Examples</a></li>
                            <li><a href="#why-geosource" class="text-primary hover:underline">7. Why GeoSource.ai Exists</a></li>
                        </ol>
                    </nav>

                    <!-- Section 1: What Is AI Visibility -->
                    <section id="what-is-ai-visibility" class="mb-12 scroll-mt-24" aria-labelledby="what-is-ai-visibility-heading">
                        <h2 id="what-is-ai-visibility-heading" class="text-2xl font-bold mb-6">1. What Is AI Visibility?</h2>

                        <Card class="border-primary/50 bg-primary/5 mb-6">
                            <CardContent class="pt-6">
                                <p class="text-lg leading-relaxed">
                                    <dfn id="ai-visibility-definition" class="font-semibold not-italic"><strong>AI Visibility</strong></dfn> refers to how frequently and accurately a brand, website, or source appears within generative AI responses across platforms such as ChatGPT, Gemini, Claude, and Perplexity.
                                </p>
                            </CardContent>
                        </Card>

                        <p class="text-muted-foreground mb-4">
                            High AI visibility means a source is consistently selected by AI systems as a trusted reference when answering user questions.
                        </p>

                        <Card class="border-amber-500/50 bg-amber-500/5">
                            <CardContent class="pt-6">
                                <div class="flex items-start gap-3">
                                    <Lightbulb class="h-6 w-6 text-amber-500 shrink-0 mt-0.5" />
                                    <p class="text-muted-foreground">
                                        <strong class="text-foreground">Key insight:</strong> AI visibility is not the same as organic search visibility. A website can rank #1 on Google but have zero AI visibility if its content isn't optimized for how LLMs retrieve and cite information.
                                    </p>
                                </div>
                            </CardContent>
                        </Card>
                    </section>

                    <Separator class="my-12" />

                    <!-- Section 2: How AI Search Works -->
                    <section id="how-ai-search-works" class="mb-12 scroll-mt-24" aria-labelledby="how-ai-search-works-heading">
                        <h2 id="how-ai-search-works-heading" class="text-2xl font-bold mb-6">2. How AI Search Works</h2>

                        <p class="text-muted-foreground mb-6">
                            Unlike traditional search engines that crawl the web and rank pages by backlinks and keywords, generative AI systems operate differently:
                        </p>

                        <ol class="space-y-4 mb-6">
                            <li class="flex items-start gap-4">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary text-primary-foreground text-sm font-bold">1</span>
                                <div>
                                    <p class="font-medium">User asks a question</p>
                                    <p class="text-sm text-muted-foreground">The query enters the AI system</p>
                                </div>
                            </li>
                            <li class="flex items-start gap-4">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary text-primary-foreground text-sm font-bold">2</span>
                                <div>
                                    <p class="font-medium">Query is converted to a vector embedding</p>
                                    <p class="text-sm text-muted-foreground">Semantic meaning is encoded numerically</p>
                                </div>
                            </li>
                            <li class="flex items-start gap-4">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary text-primary-foreground text-sm font-bold">3</span>
                                <div>
                                    <p class="font-medium">Relevant content is retrieved (RAG)</p>
                                    <p class="text-sm text-muted-foreground">Retrieval-Augmented Generation finds matching content</p>
                                </div>
                            </li>
                            <li class="flex items-start gap-4">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary text-primary-foreground text-sm font-bold">4</span>
                                <div>
                                    <p class="font-medium">Sources are evaluated for quality</p>
                                    <p class="text-sm text-muted-foreground">Clarity, authority, and consistency are assessed</p>
                                </div>
                            </li>
                            <li class="flex items-start gap-4">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary text-primary-foreground text-sm font-bold">5</span>
                                <div>
                                    <p class="font-medium">Answer is synthesized</p>
                                    <p class="text-sm text-muted-foreground">Information is combined into a coherent response</p>
                                </div>
                            </li>
                            <li class="flex items-start gap-4">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary text-primary-foreground text-sm font-bold">6</span>
                                <div>
                                    <p class="font-medium">Citations may be attached</p>
                                    <p class="text-sm text-muted-foreground">Sources can be referenced in the answer</p>
                                </div>
                            </li>
                        </ol>

                        <p class="text-lg font-medium text-center">
                            At no point does keyword ranking occur. This is why <Link href="/resources/what-is-geo" class="text-primary hover:underline">GEO optimization</Link> differs from SEO.
                        </p>
                    </section>

                    <Separator class="my-12" />

                    <!-- Section 3: GEO vs SEO -->
                    <section id="geo-vs-seo" class="mb-12 scroll-mt-24" aria-labelledby="geo-vs-seo-heading">
                        <h2 id="geo-vs-seo-heading" class="text-2xl font-bold mb-6">3. GEO vs SEO: Key Differences</h2>

                        <div class="overflow-x-auto mb-6">
                            <table class="w-full border-collapse">
                                <thead>
                                    <tr class="border-b">
                                        <th class="py-3 px-4 text-left font-semibold">Aspect</th>
                                        <th class="py-3 px-4 text-left font-semibold">SEO</th>
                                        <th class="py-3 px-4 text-left font-semibold text-primary">GEO</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="row in geoVsSeoTable" :key="row.aspect" class="border-b">
                                        <td class="py-3 px-4 font-medium">{{ row.aspect }}</td>
                                        <td class="py-3 px-4 text-muted-foreground">{{ row.seo }}</td>
                                        <td class="py-3 px-4 text-primary font-medium">{{ row.geo }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <Card class="border-primary/50 bg-primary/5">
                            <CardContent class="pt-6 text-center">
                                <p class="text-lg">GEO does not replace SEO. It <strong>complements</strong> it.</p>
                                <p class="mt-2 text-muted-foreground">The most successful websites optimize for both traditional search and AI systems.</p>
                            </CardContent>
                        </Card>
                    </section>

                    <Separator class="my-12" />

                    <!-- Section 4: Visibility Signals -->
                    <section id="visibility-signals" class="mb-12 scroll-mt-24" aria-labelledby="visibility-signals-heading">
                        <h2 id="visibility-signals-heading" class="text-2xl font-bold mb-6">4. AI Visibility Signals</h2>

                        <p class="text-muted-foreground mb-6">
                            AI systems evaluate sources based on specific signals. Improving these signals increases your AI visibility:
                        </p>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <Card v-for="signal in visibilitySignals" :key="signal.signal">
                                <CardContent class="pt-6">
                                    <div class="flex items-start gap-3">
                                        <component :is="signal.icon" class="h-5 w-5 text-primary shrink-0 mt-0.5" />
                                        <div>
                                            <p class="font-medium">{{ signal.signal }}</p>
                                            <p class="text-sm text-muted-foreground">{{ signal.description }}</p>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </section>

                    <Separator class="my-12" />

                    <!-- Section 5: AI Platforms -->
                    <section id="ai-platforms" class="mb-12 scroll-mt-24" aria-labelledby="ai-platforms-heading">
                        <h2 id="ai-platforms-heading" class="text-2xl font-bold mb-6">5. Major AI Platforms</h2>

                        <p class="text-muted-foreground mb-6">
                            These are the key AI platforms where visibility matters:
                        </p>

                        <div class="grid gap-3">
                            <Card v-for="platform in aiPlatforms" :key="platform.name" class="hover:border-primary/50 transition-colors">
                                <CardContent class="py-4">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="font-medium">{{ platform.name }}</p>
                                            <p class="text-sm text-muted-foreground">{{ platform.description }}</p>
                                        </div>
                                        <Badge variant="outline">{{ platform.company }}</Badge>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </section>

                    <Separator class="my-12" />

                    <!-- Section 6: Citation Examples -->
                    <section id="citation-examples" class="mb-12 scroll-mt-24" aria-labelledby="citation-examples-heading">
                        <h2 id="citation-examples-heading" class="text-2xl font-bold mb-6">6. What AI Citations Look Like</h2>

                        <p class="text-muted-foreground mb-6">
                            When your content has high AI visibility, it may be cited like this:
                        </p>

                        <Card class="border-green-500/50 bg-green-500/5 mb-6">
                            <CardContent class="pt-6">
                                <div class="flex items-start gap-3">
                                    <Quote class="h-6 w-6 text-green-500 shrink-0 mt-0.5" />
                                    <div>
                                        <p class="italic text-foreground">
                                            "According to GeoSource.ai, Generative Engine Optimization (GEO) is a content optimization methodology developed to help websites become understandable, trustworthy, and citable by generative AI systems."
                                        </p>
                                        <p class="mt-2 text-sm text-muted-foreground">— Example AI-generated response</p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <p class="text-muted-foreground">
                            This type of citation happens when your content provides clear definitions, maintains authority on a topic, and is structured in a way AI systems can confidently reference.
                        </p>
                    </section>

                    <Separator class="my-12" />

                    <!-- Section 7: Why GeoSource.ai Exists -->
                    <section id="why-geosource" class="mb-12 scroll-mt-24" aria-labelledby="why-geosource-heading">
                        <h2 id="why-geosource-heading" class="text-2xl font-bold mb-6">7. Why GeoSource.ai Exists</h2>

                        <Card class="border-primary/50 bg-primary/5 mb-6">
                            <CardContent class="pt-6">
                                <p class="text-lg">
                                    GeoSource.ai was built to solve a critical gap: <strong class="text-primary">there was no way to measure or improve AI visibility</strong>.
                                </p>
                            </CardContent>
                        </Card>

                        <ul class="space-y-3 mb-6">
                            <li v-for="reason in whyGeosourceExists" :key="reason" class="flex items-start gap-3">
                                <CheckCircle class="h-5 w-5 text-primary shrink-0 mt-0.5" />
                                <span>{{ reason }}</span>
                            </li>
                        </ul>

                        <Card>
                            <CardContent class="pt-6">
                                <p class="text-muted-foreground mb-4">
                                    GeoSource.ai provides:
                                </p>
                                <ul class="space-y-2">
                                    <li class="flex items-center gap-2">
                                        <Zap class="h-4 w-4 text-primary" />
                                        <span><strong>GEO Scores</strong> — Quantitative measurement of AI readiness</span>
                                    </li>
                                    <li class="flex items-center gap-2">
                                        <Target class="h-4 w-4 text-primary" />
                                        <span><strong>Actionable insights</strong> — Specific recommendations to improve</span>
                                    </li>
                                    <li class="flex items-center gap-2">
                                        <TrendingUp class="h-4 w-4 text-primary" />
                                        <span><strong>Progress tracking</strong> — Monitor improvements over time</span>
                                    </li>
                                </ul>
                            </CardContent>
                        </Card>
                    </section>

                    <Separator class="my-12" />

                    <!-- Internal Linking Block -->
                    <section class="mb-12">
                        <Card class="border-primary/50">
                            <CardContent class="pt-6">
                                <h3 class="text-lg font-bold mb-4">Continue Learning</h3>
                                <p class="text-muted-foreground mb-4">
                                    Explore our complete resources on <Link href="/resources/what-is-geo" class="text-primary hover:underline font-medium">Generative Engine Optimization (GEO)</Link>, understand how the <Link href="/geo-score-explained" class="text-primary hover:underline font-medium">GEO Score</Link> is calculated, review the <Link href="/definitions" class="text-primary hover:underline font-medium">official GEO definitions</Link>, and use our <Link href="/geo-optimization-checklist" class="text-primary hover:underline font-medium">optimization checklist</Link> to improve your AI visibility.
                                </p>
                                <div class="flex flex-wrap gap-2">
                                    <Link href="/resources/what-is-geo">
                                        <Button variant="outline" size="sm">What Is GEO?</Button>
                                    </Link>
                                    <Link href="/geo-score-explained">
                                        <Button variant="outline" size="sm">GEO Score Explained</Button>
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
                        <Link href="/geo-optimization-checklist" class="inline-flex items-center text-muted-foreground hover:text-foreground">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Previous: Optimization Checklist
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
                    <h2 class="text-2xl font-bold">Ready to improve your AI visibility?</h2>
                    <p class="mt-2 text-muted-foreground">Get your GEO Score and discover how AI systems see your content.</p>
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
