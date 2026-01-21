<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import {
    Globe,
    BookOpen,
    ArrowRight,
    ArrowLeft,
    FileText,
    Lightbulb,
    CheckCircle,
    Menu,
    Mail,
    Code,
    Search,
    Zap,
    Calendar,
} from 'lucide-vue-next';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import ThemeSwitcher from '@/components/ThemeSwitcher.vue';

const publishedDate = new Date('2026-01-20').toLocaleDateString('en-US', { month: 'long', year: 'numeric' });

const keyBenefits = [
    {
        icon: Search,
        title: 'Improved AI Discovery',
        description: 'Provides a clear map of your site\'s most important pages and topics for AI crawlers.',
    },
    {
        icon: CheckCircle,
        title: 'Citation Readiness',
        description: 'Signals which content is authoritative and ready to be cited by AI systems.',
    },
    {
        icon: Zap,
        title: 'Reduced Semantic Noise',
        description: 'Eliminates confusion by clearly defining your site\'s purpose and key offerings.',
    },
    {
        icon: Code,
        title: 'Machine-Readable Structure',
        description: 'Provides structured, plain-text content that AI systems can easily parse and understand.',
    },
];

const comparisonData = [
    { aspect: 'Purpose', robotsTxt: 'Tells crawlers what NOT to index', llmsTxt: 'Tells AI what your site IS about' },
    { aspect: 'Audience', robotsTxt: 'Traditional search engine bots', llmsTxt: 'LLM crawlers and AI systems' },
    { aspect: 'Format', robotsTxt: 'Directive-based (Allow/Disallow)', llmsTxt: 'Descriptive markdown/plain text' },
    { aspect: 'Content', robotsTxt: 'Access rules and restrictions', llmsTxt: 'Site description, pages, and context' },
];

const whatToInclude = [
    'Company/site description and core offering',
    'Key pages with URLs and brief descriptions',
    'Main topics and areas of expertise',
    'Definitions of industry-specific terms',
    'Contact information and support channels',
    'Sitemap reference',
    'Usage guidelines for AI systems',
];

const relatedArticles = [
    { slug: 'what-is-geo', title: 'What Is Generative Engine Optimization (GEO)?' },
    { slug: 'how-llms-cite-sources', title: 'How Large Language Models Choose Which Sources to Cite' },
    { slug: 'geo-content-framework', title: 'The GeoSource.ai GEO Content Framework' },
];

const jsonLd = {
    '@context': 'https://schema.org',
    '@type': 'Article',
    headline: 'Why llms.txt Matters for Generative Engine Optimization (GEO)',
    description: 'Learn how llms.txt files help AI systems understand, discover, and cite your website content. A comprehensive guide to implementing llms.txt for better AI visibility.',
    url: 'https://geosource.ai/resources/why-llms-txt-matters',
    datePublished: '2026-01-20',
    dateModified: '2026-01-20',
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
        '@id': 'https://geosource.ai/resources/why-llms-txt-matters',
    },
    about: {
        '@type': 'DefinedTerm',
        name: 'llms.txt',
        description: 'A plain-text file that provides AI systems with structured information about a website\'s content, purpose, and key pages.',
    },
};

const faqJsonLd = {
    '@context': 'https://schema.org',
    '@type': 'FAQPage',
    mainEntity: [
        {
            '@type': 'Question',
            name: 'What is llms.txt?',
            acceptedAnswer: {
                '@type': 'Answer',
                text: 'llms.txt is a plain-text file placed at your website\'s root (e.g., example.com/llms.txt) that provides AI systems with structured information about your site\'s content, purpose, and key pages. It\'s like a robots.txt but designed specifically for AI crawlers and large language models.',
            },
        },
        {
            '@type': 'Question',
            name: 'Why does llms.txt matter for GEO?',
            acceptedAnswer: {
                '@type': 'Answer',
                text: 'llms.txt matters for GEO because it directly supports AI discovery by providing a clear map of your site, enables citation readiness by signaling authoritative content, reduces semantic noise by clearly defining your site\'s purpose, and provides machine-readable structure that AI systems can easily parse.',
            },
        },
        {
            '@type': 'Question',
            name: 'What should I include in my llms.txt file?',
            acceptedAnswer: {
                '@type': 'Answer',
                text: 'Your llms.txt file should include: company/site description, key pages with URLs and descriptions, main topics and expertise areas, definitions of industry terms, contact information, sitemap reference, and usage guidelines for AI systems.',
            },
        },
        {
            '@type': 'Question',
            name: 'How is llms.txt different from robots.txt?',
            acceptedAnswer: {
                '@type': 'Answer',
                text: 'While robots.txt tells crawlers what NOT to access, llms.txt tells AI what your site IS about. robots.txt is directive-based with Allow/Disallow rules for traditional bots, while llms.txt is descriptive markdown/plain text designed for LLM crawlers and AI systems.',
            },
        },
    ],
};
</script>

<template>
    <Head title="Why llms.txt Matters for Generative Engine Optimization (GEO) - GeoSource.ai">
        <meta name="description" content="Learn how llms.txt files help AI systems understand, discover, and cite your website content. A comprehensive guide to implementing llms.txt for better AI visibility." />
        <meta property="og:title" content="Why llms.txt Matters for Generative Engine Optimization (GEO)" />
        <meta property="og:description" content="Learn how llms.txt files help AI systems understand, discover, and cite your website content." />
        <meta property="og:type" content="article" />
        <meta property="og:url" content="https://geosource.ai/resources/why-llms-txt-matters" />
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" content="Why llms.txt Matters for Generative Engine Optimization (GEO)" />
        <meta name="twitter:description" content="Learn how llms.txt files help AI systems understand, discover, and cite your website content." />
        <link rel="canonical" href="https://geosource.ai/resources/why-llms-txt-matters" />
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
                        <span class="text-foreground">Why llms.txt Matters</span>
                    </nav>
                </div>
            </div>

            <!-- Article -->
            <article class="py-12">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <!-- Header -->
                    <header class="mb-12">
                        <Badge variant="secondary" class="mb-4">
                            <FileText class="mr-1 h-3 w-3" />
                            Technical Guide
                        </Badge>
                        <h1 class="text-3xl font-bold tracking-tight sm:text-4xl lg:text-5xl">
                            Why llms.txt Matters for Generative Engine Optimization (GEO)
                        </h1>
                        <p class="mt-4 text-lg text-muted-foreground">
                            How a simple text file can dramatically improve your site's AI discoverability and citation potential.
                        </p>
                        <div class="mt-4 flex items-center gap-2 text-sm text-muted-foreground">
                            <Calendar class="h-4 w-4" />
                            <span>{{ publishedDate }}</span>
                        </div>
                    </header>

                    <!-- Definition Section -->
                    <section class="mb-12" aria-labelledby="definition">
                        <h2 id="definition" class="text-2xl font-bold mb-6">What is llms.txt?</h2>
                        <Card class="border-primary/50 bg-primary/5">
                            <CardContent class="pt-6">
                                <p class="text-lg leading-relaxed">
                                    <dfn id="llms-txt-definition" class="font-semibold not-italic"><strong>llms.txt</strong></dfn> is a plain-text file placed at your website's root (e.g., <code class="rounded bg-muted px-1.5 py-0.5 text-sm">example.com/llms.txt</code>) that provides AI systems with structured information about your site's content, purpose, and key pages.
                                </p>
                            </CardContent>
                        </Card>
                        <div class="mt-6 space-y-4 text-muted-foreground">
                            <p>
                                Think of it as a <strong class="text-foreground">robots.txt for AI systems</strong> — but instead of telling crawlers what to avoid, it tells them what your site is about and which content is most valuable.
                            </p>
                            <p>
                                While traditional SEO focuses on optimizing for search engine algorithms, <strong class="text-foreground">llms.txt speaks directly to AI crawlers</strong> in a format they can easily parse and understand.
                            </p>
                        </div>
                    </section>

                    <Separator class="my-12" />

                    <!-- Why It Matters Section -->
                    <section class="mb-12" aria-labelledby="why-it-matters">
                        <h2 id="why-it-matters" class="text-2xl font-bold mb-6">Why llms.txt Matters for GEO</h2>
                        <p class="text-muted-foreground mb-8">
                            In the age of generative AI search, having a well-structured llms.txt file provides several key advantages:
                        </p>

                        <div class="grid gap-6 sm:grid-cols-2">
                            <Card v-for="benefit in keyBenefits" :key="benefit.title" class="h-full">
                                <CardContent class="pt-6">
                                    <div class="flex items-start gap-4">
                                        <div class="rounded-lg bg-primary/10 p-2">
                                            <component :is="benefit.icon" class="h-5 w-5 text-primary" />
                                        </div>
                                        <div>
                                            <h3 class="font-semibold">{{ benefit.title }}</h3>
                                            <p class="mt-1 text-sm text-muted-foreground">{{ benefit.description }}</p>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </section>

                    <Separator class="my-12" />

                    <!-- Comparison Section -->
                    <section class="mb-12" aria-labelledby="comparison">
                        <h2 id="comparison" class="text-2xl font-bold mb-6">llms.txt vs robots.txt</h2>
                        <p class="text-muted-foreground mb-6">
                            Understanding the difference between these two files is crucial:
                        </p>

                        <div class="overflow-x-auto">
                            <table class="w-full border-collapse">
                                <thead>
                                    <tr class="border-b">
                                        <th class="py-3 px-4 text-left font-semibold">Aspect</th>
                                        <th class="py-3 px-4 text-left font-semibold">robots.txt</th>
                                        <th class="py-3 px-4 text-left font-semibold text-primary">llms.txt</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="row in comparisonData" :key="row.aspect" class="border-b">
                                        <td class="py-3 px-4 text-muted-foreground">{{ row.aspect }}</td>
                                        <td class="py-3 px-4">{{ row.robotsTxt }}</td>
                                        <td class="py-3 px-4 text-primary font-medium">{{ row.llmsTxt }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <Card class="mt-8 border-amber-500/50 bg-amber-500/5">
                            <CardContent class="pt-6">
                                <div class="flex items-start gap-3">
                                    <Lightbulb class="h-6 w-6 text-amber-500 shrink-0 mt-0.5" />
                                    <div>
                                        <p class="font-medium text-foreground">Key Insight</p>
                                        <p class="text-muted-foreground mt-1">
                                            <strong class="text-foreground">robots.txt controls access; llms.txt provides context.</strong> Both are important, but llms.txt directly supports how AI systems understand and cite your content.
                                        </p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </section>

                    <Separator class="my-12" />

                    <!-- What to Include Section -->
                    <section class="mb-12" aria-labelledby="what-to-include">
                        <h2 id="what-to-include" class="text-2xl font-bold mb-6">What to Include in Your llms.txt</h2>
                        <p class="text-muted-foreground mb-6">
                            An effective llms.txt file should contain:
                        </p>

                        <Card>
                            <CardContent class="pt-6">
                                <ul class="space-y-3">
                                    <li v-for="(item, index) in whatToInclude" :key="index" class="flex items-start gap-3">
                                        <CheckCircle class="h-5 w-5 text-green-500 shrink-0 mt-0.5" />
                                        <span>{{ item }}</span>
                                    </li>
                                </ul>
                            </CardContent>
                        </Card>
                    </section>

                    <Separator class="my-12" />

                    <!-- Example Section -->
                    <section class="mb-12" aria-labelledby="example">
                        <h2 id="example" class="text-2xl font-bold mb-6">Example llms.txt Structure</h2>
                        <p class="text-muted-foreground mb-6">
                            Here's a basic structure for an effective llms.txt file:
                        </p>

                        <Card class="bg-muted/50">
                            <CardContent class="pt-6">
                                <pre class="text-sm overflow-x-auto"><code># Company Name - llms.txt
# Brief description of what your site is about

## About
A 2-3 sentence description of your company/site
and its core offering or expertise.

## Main Pages
- URL: https://example.com/
  Description: Homepage description

- URL: https://example.com/products
  Description: Products page description

## Topics We Cover
- Topic 1
- Topic 2
- Topic 3

## Contact
- Email: contact@example.com
- Website: https://example.com

## Sitemap
https://example.com/sitemap.xml</code></pre>
                            </CardContent>
                        </Card>

                        <p class="mt-6 text-muted-foreground">
                            View <Link href="/llms.txt" class="text-primary hover:underline" target="_blank">GeoSource.ai's llms.txt</Link> for a real-world example.
                        </p>
                    </section>

                    <Separator class="my-12" />

                    <!-- Best Practices Section -->
                    <section class="mb-12" aria-labelledby="best-practices">
                        <h2 id="best-practices" class="text-2xl font-bold mb-6">Best Practices</h2>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <Card>
                                <CardContent class="pt-6">
                                    <h3 class="font-semibold text-green-600 mb-2">Do</h3>
                                    <ul class="space-y-2 text-sm text-muted-foreground">
                                        <li>• Keep it concise and scannable</li>
                                        <li>• Use clear, descriptive language</li>
                                        <li>• Update it when content changes</li>
                                        <li>• Include your most important pages</li>
                                        <li>• Reference your sitemap</li>
                                    </ul>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardContent class="pt-6">
                                    <h3 class="font-semibold text-red-600 mb-2">Don't</h3>
                                    <ul class="space-y-2 text-sm text-muted-foreground">
                                        <li>• Include sensitive information</li>
                                        <li>• Make it excessively long</li>
                                        <li>• Use complex formatting</li>
                                        <li>• List every page on your site</li>
                                        <li>• Let it become outdated</li>
                                    </ul>
                                </CardContent>
                            </Card>
                        </div>
                    </section>

                    <Separator class="my-12" />

                    <!-- Summary Section -->
                    <section class="mb-12" aria-labelledby="summary">
                        <h2 id="summary" class="text-2xl font-bold mb-6">The Bottom Line</h2>
                        <Card class="border-primary bg-primary/5">
                            <CardContent class="pt-6">
                                <p class="text-xl font-medium text-center">
                                    llms.txt is your direct communication channel to AI systems — use it to tell them exactly what your site is about and why it's worth citing.
                                </p>
                            </CardContent>
                        </Card>
                    </section>

                    <Separator class="my-12" />

                    <!-- Related Resources -->
                    <section aria-labelledby="related">
                        <h2 id="related" class="text-2xl font-bold mb-6">Related Resources</h2>
                        <div class="grid gap-4 sm:grid-cols-3">
                            <Link
                                v-for="article in relatedArticles"
                                :key="article.slug"
                                :href="`/resources/${article.slug}`"
                                class="group"
                            >
                                <Card class="h-full transition-colors hover:border-primary/50">
                                    <CardContent class="pt-6">
                                        <p class="font-medium group-hover:text-primary transition-colors">
                                            {{ article.title }}
                                        </p>
                                        <span class="inline-flex items-center text-sm text-primary mt-2">
                                            Read more
                                            <ArrowRight class="ml-1 h-3 w-3 transition-transform group-hover:translate-x-1" />
                                        </span>
                                    </CardContent>
                                </Card>
                            </Link>
                        </div>
                    </section>

                    <!-- Navigation -->
                    <div class="mt-12 flex items-center justify-between border-t pt-8">
                        <Link href="/resources" class="inline-flex items-center text-muted-foreground hover:text-foreground">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Back to Resources
                        </Link>
                        <Link href="/resources/why-ssr-matters-for-geo" class="inline-flex items-center text-primary hover:underline">
                            Next: Why SSR Matters for GEO
                            <ArrowRight class="ml-2 h-4 w-4" />
                        </Link>
                    </div>
                </div>
            </article>

            <!-- CTA Section -->
            <section class="border-t bg-muted/30 py-12">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 text-center">
                    <h2 class="text-2xl font-bold">Ready to optimize for AI?</h2>
                    <p class="mt-2 text-muted-foreground">Get your GEO Score and start improving your AI visibility.</p>
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
