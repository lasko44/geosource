<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import {
    BookOpen,
    ArrowRight,
    ArrowLeft,
    FileText,
    Lightbulb,
    CheckCircle,
    Code,
    Search,
    Zap,
    Calendar,
} from 'lucide-vue-next';
import SkipNav from '@/components/resources/SkipNav.vue';
import ResourceHeader from '@/components/resources/ResourceHeader.vue';
import ResourceFooter from '@/components/resources/ResourceFooter.vue';
import ResourceBreadcrumb from '@/components/resources/ResourceBreadcrumb.vue';

const publishedDate = '2026-01-20';
const publishedDateFormatted = new Date(publishedDate).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });

const breadcrumbItems = [
    { label: 'Resources', href: '/resources' },
    { label: 'Why llms.txt Matters' },
];

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
        <meta property="og:site_name" content="GeoSource.ai" />
        <meta property="article:published_time" content="2026-01-20T00:00:00Z" />
        <meta property="article:modified_time" content="2026-01-20T00:00:00Z" />
        <meta property="article:author" content="GeoSource.ai" />
        <meta property="article:section" content="Technical" />
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:site" content="@geosourceai" />
        <meta name="twitter:title" content="Why llms.txt Matters for Generative Engine Optimization (GEO)" />
        <meta name="twitter:description" content="Learn how llms.txt files help AI systems understand, discover, and cite your website content." />
        <meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1" />
        <link rel="canonical" href="https://geosource.ai/resources/why-llms-txt-matters" />
        <component :is="'script'" type="application/ld+json">{{ JSON.stringify(jsonLd) }}</component>
        <component :is="'script'" type="application/ld+json">{{ JSON.stringify(faqJsonLd) }}</component>
    </Head>

    <div class="min-h-screen bg-background text-foreground">
        <!-- Skip Navigation -->
        <SkipNav />

        <!-- Navigation -->
        <ResourceHeader />

        <main id="main-content" role="main">
            <!-- Breadcrumb -->
            <ResourceBreadcrumb :items="breadcrumbItems" />

            <!-- Article -->
            <article class="py-12">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <!-- Header -->
                    <header class="mb-12">
                        <Badge variant="secondary" class="mb-4">
                            <FileText class="mr-1 h-3 w-3" aria-hidden="true" />
                            Technical Guide
                        </Badge>
                        <h1 class="text-3xl font-bold tracking-tight sm:text-4xl lg:text-5xl">
                            Why llms.txt Matters for Generative Engine Optimization (GEO)
                        </h1>
                        <p class="mt-4 text-lg text-muted-foreground">
                            How a simple text file can dramatically improve your site's AI discoverability and citation potential.
                        </p>
                        <div class="mt-4 flex items-center gap-2 text-sm text-muted-foreground">
                            <Calendar class="h-4 w-4" aria-hidden="true" />
                            <time :datetime="publishedDate">{{ publishedDateFormatted }}</time>
                        </div>
                    </header>

                    <!-- Definition Section -->
                    <section class="mb-12" aria-labelledby="definition-heading">
                        <h2 id="definition-heading" class="text-2xl font-bold mb-6">What is llms.txt?</h2>
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
                    <section class="mb-12" aria-labelledby="why-it-matters-heading">
                        <h2 id="why-it-matters-heading" class="text-2xl font-bold mb-6">Why llms.txt Matters for GEO</h2>
                        <p class="text-muted-foreground mb-8">
                            In the age of generative AI search, having a well-structured llms.txt file provides several key advantages:
                        </p>

                        <div class="grid gap-6 sm:grid-cols-2">
                            <Card v-for="benefit in keyBenefits" :key="benefit.title" class="h-full">
                                <CardContent class="pt-6">
                                    <div class="flex items-start gap-4">
                                        <div class="rounded-lg bg-primary/10 p-2">
                                            <component :is="benefit.icon" class="h-5 w-5 text-primary" aria-hidden="true" />
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
                    <section class="mb-12" aria-labelledby="comparison-heading">
                        <h2 id="comparison-heading" class="text-2xl font-bold mb-6">llms.txt vs robots.txt</h2>
                        <p class="text-muted-foreground mb-6">
                            Understanding the difference between these two files is crucial:
                        </p>

                        <div class="overflow-x-auto" role="region" aria-label="Comparison table" tabindex="0">
                            <table class="w-full border-collapse">
                                <caption class="sr-only">Comparison between robots.txt and llms.txt files</caption>
                                <thead>
                                    <tr class="border-b">
                                        <th scope="col" class="py-3 px-4 text-left font-semibold">Aspect</th>
                                        <th scope="col" class="py-3 px-4 text-left font-semibold">robots.txt</th>
                                        <th scope="col" class="py-3 px-4 text-left font-semibold text-primary">llms.txt</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="row in comparisonData" :key="row.aspect" class="border-b">
                                        <th scope="row" class="py-3 px-4 text-muted-foreground font-normal text-left">{{ row.aspect }}</th>
                                        <td class="py-3 px-4">{{ row.robotsTxt }}</td>
                                        <td class="py-3 px-4 text-primary font-medium">{{ row.llmsTxt }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <Card class="mt-8 border-amber-500/50 bg-amber-500/5">
                            <CardContent class="pt-6">
                                <div class="flex items-start gap-3">
                                    <Lightbulb class="h-6 w-6 text-amber-500 shrink-0 mt-0.5" aria-hidden="true" />
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
                    <section class="mb-12" aria-labelledby="what-to-include-heading">
                        <h2 id="what-to-include-heading" class="text-2xl font-bold mb-6">What to Include in Your llms.txt</h2>
                        <p class="text-muted-foreground mb-6">
                            An effective llms.txt file should contain:
                        </p>

                        <Card>
                            <CardContent class="pt-6">
                                <ul class="space-y-3">
                                    <li v-for="(item, index) in whatToInclude" :key="index" class="flex items-start gap-3">
                                        <CheckCircle class="h-5 w-5 text-green-500 shrink-0 mt-0.5" aria-hidden="true" />
                                        <span>{{ item }}</span>
                                    </li>
                                </ul>
                            </CardContent>
                        </Card>
                    </section>

                    <Separator class="my-12" />

                    <!-- Example Section -->
                    <section class="mb-12" aria-labelledby="example-heading">
                        <h2 id="example-heading" class="text-2xl font-bold mb-6">Example llms.txt Structure</h2>
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
                            View <Link href="/llms.txt" class="text-primary hover:underline focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 rounded" target="_blank">GeoSource.ai's llms.txt</Link> for a real-world example.
                        </p>
                    </section>

                    <Separator class="my-12" />

                    <!-- Best Practices Section -->
                    <section class="mb-12" aria-labelledby="best-practices-heading">
                        <h2 id="best-practices-heading" class="text-2xl font-bold mb-6">Best Practices</h2>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <Card>
                                <CardContent class="pt-6">
                                    <h3 class="font-semibold text-green-600 mb-2">Do</h3>
                                    <ul class="space-y-2 text-sm text-muted-foreground">
                                        <li>Keep it concise and scannable</li>
                                        <li>Use clear, descriptive language</li>
                                        <li>Update it when content changes</li>
                                        <li>Include your most important pages</li>
                                        <li>Reference your sitemap</li>
                                    </ul>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardContent class="pt-6">
                                    <h3 class="font-semibold text-red-600 mb-2">Don't</h3>
                                    <ul class="space-y-2 text-sm text-muted-foreground">
                                        <li>Include sensitive information</li>
                                        <li>Make it excessively long</li>
                                        <li>Use complex formatting</li>
                                        <li>List every page on your site</li>
                                        <li>Let it become outdated</li>
                                    </ul>
                                </CardContent>
                            </Card>
                        </div>
                    </section>

                    <Separator class="my-12" />

                    <!-- Summary Section -->
                    <section class="mb-12" aria-labelledby="summary-heading">
                        <h2 id="summary-heading" class="text-2xl font-bold mb-6">The Bottom Line</h2>
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
                    <section aria-labelledby="related-heading">
                        <h2 id="related-heading" class="text-2xl font-bold mb-6">Related Resources</h2>
                        <ul class="grid gap-4 sm:grid-cols-3" role="list" aria-label="Related articles">
                            <li v-for="article in relatedArticles" :key="article.slug">
                                <Link
                                    :href="`/resources/${article.slug}`"
                                    class="group block h-full focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 rounded-lg"
                                    :aria-label="`Read article: ${article.title}`"
                                >
                                    <Card class="h-full transition-colors hover:border-primary/50">
                                        <CardContent class="pt-6">
                                            <p class="font-medium group-hover:text-primary transition-colors">
                                                {{ article.title }}
                                            </p>
                                            <span class="inline-flex items-center text-sm text-primary mt-2">
                                                Read more
                                                <ArrowRight class="ml-1 h-3 w-3 transition-transform group-hover:translate-x-1" aria-hidden="true" />
                                            </span>
                                        </CardContent>
                                    </Card>
                                </Link>
                            </li>
                        </ul>
                    </section>

                    <!-- Navigation -->
                    <nav aria-label="Article navigation" class="mt-12 flex items-center justify-between border-t pt-8">
                        <Link href="/resources" class="inline-flex items-center text-muted-foreground hover:text-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 rounded">
                            <ArrowLeft class="mr-2 h-4 w-4" aria-hidden="true" />
                            Back to Resources
                        </Link>
                        <Link href="/resources/why-ssr-matters-for-geo" class="inline-flex items-center text-primary hover:underline focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 rounded">
                            Next: Why SSR Matters for GEO
                            <ArrowRight class="ml-2 h-4 w-4" aria-hidden="true" />
                        </Link>
                    </nav>
                </div>
            </article>

            <!-- CTA Section -->
            <section class="border-t bg-muted/30 py-12" aria-labelledby="cta-heading">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 text-center">
                    <h2 id="cta-heading" class="text-2xl font-bold">Ready to optimize for AI?</h2>
                    <p class="mt-2 text-muted-foreground">Get your GEO Score and start improving your AI visibility.</p>
                    <div class="mt-6">
                        <Link href="/register">
                            <Button size="lg" class="gap-2">
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
