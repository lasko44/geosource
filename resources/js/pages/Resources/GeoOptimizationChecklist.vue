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
    CheckSquare,
    FileText,
    Database,
    Target,
    Code,
    Link as LinkIcon,
    HelpCircle,
    Quote,
    Layers,
    CheckCircle,
    Circle,
    Menu,
} from 'lucide-vue-next';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import ThemeSwitcher from '@/components/ThemeSwitcher.vue';

const checklistCategories = [
    {
        title: 'Content Structure',
        icon: Layers,
        description: 'Organize content for machine comprehension',
        items: [
            { task: 'Start each page with a clear definition of the main topic', priority: 'High' },
            { task: 'Use hierarchical headings (H1 → H2 → H3) in logical order', priority: 'High' },
            { task: 'Break content into scannable sections with descriptive headings', priority: 'High' },
            { task: 'Use bullet points and numbered lists for multi-item content', priority: 'Medium' },
            { task: 'Add tables for comparisons and structured data', priority: 'Medium' },
            { task: 'Keep paragraphs short and focused (3-4 sentences max)', priority: 'Low' },
        ],
    },
    {
        title: 'Definitions & Clarity',
        icon: Quote,
        description: 'Make your content citation-ready',
        items: [
            { task: 'Include explicit definitions using "X is defined as..." patterns', priority: 'High' },
            { task: 'Define technical terms inline or in a glossary section', priority: 'High' },
            { task: 'Use declarative statements instead of vague language', priority: 'High' },
            { task: 'Avoid marketing fluff and promotional language', priority: 'Medium' },
            { task: 'State facts confidently without hedging words', priority: 'Medium' },
            { task: 'Use consistent terminology throughout the page', priority: 'High' },
        ],
    },
    {
        title: 'FAQ Coverage',
        icon: HelpCircle,
        description: 'Answer questions AI systems are asked',
        items: [
            { task: 'Include an FAQ section on key pages', priority: 'High' },
            { task: 'Use actual questions as headings (H2 or H3)', priority: 'High' },
            { task: 'Provide direct, complete answers immediately after questions', priority: 'High' },
            { task: 'Cover common "what is", "how to", and "why" questions', priority: 'Medium' },
            { task: 'Add FAQPage schema markup to FAQ sections', priority: 'High' },
            { task: 'Keep answers concise but comprehensive', priority: 'Medium' },
        ],
    },
    {
        title: 'Technical Optimization',
        icon: Code,
        description: 'Implement machine-readable markup',
        items: [
            { task: 'Add JSON-LD Article schema to content pages', priority: 'High' },
            { task: 'Implement Organization schema on your homepage', priority: 'High' },
            { task: 'Add DefinedTerm schema for key definitions', priority: 'Medium' },
            { task: 'Use semantic HTML elements (<article>, <section>, <dfn>)', priority: 'High' },
            { task: 'Include proper meta descriptions on all pages', priority: 'High' },
            { task: 'Add canonical URLs to prevent duplicate content', priority: 'Medium' },
            { task: 'Implement Open Graph and Twitter Card meta tags', priority: 'Low' },
        ],
    },
    {
        title: 'Internal Linking',
        icon: LinkIcon,
        description: 'Build semantic relationships between content',
        items: [
            { task: 'Link to your definitions/glossary page from key terms', priority: 'High' },
            { task: 'Add "Related Resources" sections to all articles', priority: 'High' },
            { task: 'Use descriptive anchor text (not "click here")', priority: 'Medium' },
            { task: 'Create topic clusters with pillar pages and supporting content', priority: 'Medium' },
            { task: 'Link new content to existing authoritative pages', priority: 'Medium' },
            { task: 'Add breadcrumb navigation for hierarchy clarity', priority: 'Low' },
        ],
    },
    {
        title: 'Topic Authority',
        icon: Target,
        description: 'Establish expertise in your domain',
        items: [
            { task: 'Focus each page on a single, specific topic', priority: 'High' },
            { task: 'Cover topics comprehensively (depth over breadth)', priority: 'High' },
            { task: 'Create a content hub for your main topic area', priority: 'Medium' },
            { task: 'Maintain consistent messaging across all pages', priority: 'Medium' },
            { task: 'Update content regularly to maintain accuracy', priority: 'Low' },
            { task: 'Cite authoritative sources when making claims', priority: 'Low' },
        ],
    },
];

const jsonLd = {
    '@context': 'https://schema.org',
    '@type': 'HowTo',
    name: 'GEO Optimization Checklist',
    description: 'A step-by-step checklist for optimizing your website for generative AI systems and improving your GEO Score.',
    url: 'https://geosource.ai/geo-optimization-checklist',
    step: checklistCategories.flatMap((category, catIndex) =>
        category.items.map((item, itemIndex) => ({
            '@type': 'HowToStep',
            position: catIndex * 10 + itemIndex + 1,
            name: item.task,
            itemListElement: {
                '@type': 'HowToDirection',
                text: item.task,
            },
        }))
    ),
};

const articleJsonLd = {
    '@context': 'https://schema.org',
    '@type': 'Article',
    headline: 'GEO Optimization Checklist - Step-by-Step Guide',
    description: 'A comprehensive checklist for optimizing your website for generative AI systems. Improve your GEO Score with actionable steps.',
    url: 'https://geosource.ai/geo-optimization-checklist',
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
};

const faqJsonLd = {
    '@context': 'https://schema.org',
    '@type': 'FAQPage',
    mainEntity: [
        {
            '@type': 'Question',
            name: 'How do I optimize my website for AI search?',
            acceptedAnswer: {
                '@type': 'Answer',
                text: 'To optimize for AI search: 1) Add clear definitions using "X is defined as" patterns, 2) Structure content with hierarchical headings, 3) Include FAQ sections with direct answers, 4) Implement JSON-LD schema markup, 5) Build internal links between related content, 6) Focus each page on a single topic with comprehensive coverage.',
            },
        },
        {
            '@type': 'Question',
            name: 'What is the most important GEO optimization?',
            acceptedAnswer: {
                '@type': 'Answer',
                text: 'The most important GEO optimizations are: clear definitions at the start of content, proper heading hierarchy, FAQ sections with schema markup, and consistent terminology throughout. These help AI systems understand and trust your content enough to cite it.',
            },
        },
        {
            '@type': 'Question',
            name: 'How long does GEO optimization take?',
            acceptedAnswer: {
                '@type': 'Answer',
                text: 'Basic GEO optimization can be implemented in a few hours per page. Adding definitions and restructuring headings is quick. Technical implementations like schema markup may require developer assistance. Full site optimization is an ongoing process of continuous improvement.',
            },
        },
    ],
};
</script>

<template>
    <Head title="GEO Optimization Checklist - Step-by-Step Guide | GeoSource.ai">
        <meta name="description" content="A comprehensive checklist for optimizing your website for generative AI systems. Improve your GEO Score with actionable steps for content structure, definitions, FAQ coverage, and technical optimization." />
        <meta property="og:title" content="GEO Optimization Checklist - Step-by-Step Guide" />
        <meta property="og:description" content="Actionable checklist for improving your GEO Score and AI citation readiness." />
        <meta property="og:type" content="article" />
        <meta property="og:url" content="https://geosource.ai/geo-optimization-checklist" />
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" content="GEO Optimization Checklist - Step-by-Step Guide" />
        <meta name="twitter:description" content="Actionable checklist for improving your GEO Score and AI citation readiness." />
        <link rel="canonical" href="https://geosource.ai/geo-optimization-checklist" />
        <component :is="'script'" type="application/ld+json">{{ JSON.stringify(jsonLd) }}</component>
        <component :is="'script'" type="application/ld+json">{{ JSON.stringify(articleJsonLd) }}</component>
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
                        <span class="text-foreground">GEO Optimization Checklist</span>
                    </nav>
                </div>
            </div>

            <!-- Article -->
            <article class="py-12">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <!-- Header -->
                    <header class="mb-12">
                        <Badge variant="secondary" class="mb-4">
                            <CheckSquare class="mr-1 h-3 w-3" />
                            Practical Guide
                        </Badge>
                        <h1 class="text-3xl font-bold tracking-tight sm:text-4xl lg:text-5xl">
                            GEO Optimization Checklist
                        </h1>
                        <p class="mt-4 text-lg text-muted-foreground">
                            A step-by-step guide to optimizing your website for generative AI systems.
                        </p>
                    </header>

                    <!-- Introduction -->
                    <section class="mb-12" aria-labelledby="intro">
                        <Card class="border-primary/50 bg-primary/5">
                            <CardContent class="pt-6">
                                <p class="text-lg">
                                    This checklist covers everything you need to optimize your content for AI citation. Work through each category systematically to improve your <Link href="/geo-score-explained" class="text-primary hover:underline font-medium">GEO Score</Link>.
                                </p>
                            </CardContent>
                        </Card>
                    </section>

                    <!-- Priority Legend -->
                    <section class="mb-8">
                        <div class="flex flex-wrap items-center gap-4 text-sm">
                            <span class="font-medium">Priority:</span>
                            <span class="flex items-center gap-1">
                                <Badge variant="destructive" class="text-xs">High</Badge>
                                <span class="text-muted-foreground">Essential for AI visibility</span>
                            </span>
                            <span class="flex items-center gap-1">
                                <Badge variant="default" class="text-xs">Medium</Badge>
                                <span class="text-muted-foreground">Recommended</span>
                            </span>
                            <span class="flex items-center gap-1">
                                <Badge variant="secondary" class="text-xs">Low</Badge>
                                <span class="text-muted-foreground">Nice to have</span>
                            </span>
                        </div>
                    </section>

                    <!-- Checklist Categories -->
                    <div class="space-y-8">
                        <Card v-for="(category, index) in checklistCategories" :key="category.title">
                            <CardHeader>
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-primary/10">
                                        <component :is="category.icon" class="h-5 w-5 text-primary" />
                                    </div>
                                    <div>
                                        <CardTitle class="text-xl">{{ index + 1 }}. {{ category.title }}</CardTitle>
                                        <CardDescription>{{ category.description }}</CardDescription>
                                    </div>
                                </div>
                            </CardHeader>
                            <CardContent>
                                <ul class="space-y-3">
                                    <li
                                        v-for="item in category.items"
                                        :key="item.task"
                                        class="flex items-start gap-3 p-3 rounded-lg border bg-card hover:bg-muted/50 transition-colors"
                                    >
                                        <Circle class="h-5 w-5 text-muted-foreground shrink-0 mt-0.5" />
                                        <span class="flex-1">{{ item.task }}</span>
                                        <Badge
                                            :variant="item.priority === 'High' ? 'destructive' : item.priority === 'Medium' ? 'default' : 'secondary'"
                                            class="shrink-0 text-xs"
                                        >
                                            {{ item.priority }}
                                        </Badge>
                                    </li>
                                </ul>
                            </CardContent>
                        </Card>
                    </div>

                    <Separator class="my-12" />

                    <!-- Quick Wins -->
                    <section class="mb-12" aria-labelledby="quick-wins">
                        <h2 id="quick-wins" class="text-2xl font-bold mb-6">Quick Wins: Start Here</h2>
                        <Card class="border-green-500/50 bg-green-500/5">
                            <CardContent class="pt-6">
                                <p class="font-medium mb-4">If you can only do 5 things, do these:</p>
                                <ol class="space-y-3">
                                    <li class="flex items-start gap-3">
                                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-green-500 text-white text-sm font-bold">1</span>
                                        <span>Add a clear definition at the start of your main pages</span>
                                    </li>
                                    <li class="flex items-start gap-3">
                                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-green-500 text-white text-sm font-bold">2</span>
                                        <span>Structure content with proper H1 → H2 → H3 hierarchy</span>
                                    </li>
                                    <li class="flex items-start gap-3">
                                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-green-500 text-white text-sm font-bold">3</span>
                                        <span>Add an FAQ section with common questions</span>
                                    </li>
                                    <li class="flex items-start gap-3">
                                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-green-500 text-white text-sm font-bold">4</span>
                                        <span>Implement Article and FAQPage JSON-LD schema</span>
                                    </li>
                                    <li class="flex items-start gap-3">
                                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-green-500 text-white text-sm font-bold">5</span>
                                        <span>Link internally to your definitions page from key terms</span>
                                    </li>
                                </ol>
                            </CardContent>
                        </Card>
                    </section>

                    <Separator class="my-12" />

                    <!-- Internal Linking Block -->
                    <section class="mb-12">
                        <Card class="border-primary/50">
                            <CardContent class="pt-6">
                                <h3 class="text-lg font-bold mb-4">Related Resources</h3>
                                <p class="text-muted-foreground mb-4">
                                    Understand the fundamentals with our guide to <Link href="/resources/what-is-geo" class="text-primary hover:underline font-medium">Generative Engine Optimization (GEO)</Link>, learn how the <Link href="/geo-score-explained" class="text-primary hover:underline font-medium">GEO Score</Link> is calculated, and explore <Link href="/definitions" class="text-primary hover:underline font-medium">official GEO definitions</Link>.
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
                                    <Link href="/ai-search-visibility-guide">
                                        <Button variant="outline" size="sm">AI Visibility Guide</Button>
                                    </Link>
                                </div>
                            </CardContent>
                        </Card>
                    </section>

                    <!-- Navigation -->
                    <div class="flex items-center justify-between border-t pt-8">
                        <Link href="/geo-score-explained" class="inline-flex items-center text-muted-foreground hover:text-foreground">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Previous: GEO Score Explained
                        </Link>
                        <Link href="/ai-search-visibility-guide" class="inline-flex items-center text-primary hover:underline">
                            Next: AI Visibility Guide
                            <ArrowRight class="ml-2 h-4 w-4" />
                        </Link>
                    </div>
                </div>
            </article>

            <!-- CTA Section -->
            <section class="border-t bg-muted/30 py-12">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 text-center">
                    <h2 class="text-2xl font-bold">Ready to measure your progress?</h2>
                    <p class="mt-2 text-muted-foreground">Get your GEO Score and see which optimizations will have the biggest impact.</p>
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
