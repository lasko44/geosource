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
    Server,
    Lightbulb,
    CheckCircle,
    Menu,
    Mail,
    Code,
    Eye,
    Zap,
    XCircle,
    AlertTriangle,
} from 'lucide-vue-next';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import ThemeSwitcher from '@/components/ThemeSwitcher.vue';

const howLlmsAccessContent = [
    {
        step: 1,
        title: 'HTTP Request',
        description: 'LLM crawlers send a standard HTTP request to your URL, just like a browser would.',
    },
    {
        step: 2,
        title: 'HTML Response',
        description: 'Your server responds with HTML. This is where SSR vs CSR makes a crucial difference.',
    },
    {
        step: 3,
        title: 'Content Extraction',
        description: 'The crawler extracts text content from the HTML. It does NOT execute JavaScript.',
    },
    {
        step: 4,
        title: 'Knowledge Processing',
        description: 'Extracted content is processed, embedded, and added to the LLM\'s knowledge base.',
    },
];

const csrProblems = [
    {
        icon: XCircle,
        title: 'Empty Initial HTML',
        description: 'CSR apps return minimal HTML with a JavaScript bundle. Without JS execution, AI sees nothing.',
    },
    {
        icon: AlertTriangle,
        title: 'No Content for AI',
        description: 'All your carefully crafted content is trapped in JavaScript that AI crawlers can\'t run.',
    },
    {
        icon: Eye,
        title: 'Invisible to AI Search',
        description: 'If AI can\'t see your content, it can\'t understand, index, or cite it.',
    },
];

const ssrBenefits = [
    {
        icon: CheckCircle,
        title: 'Immediate Content Availability',
        description: 'Full HTML content is available on first request — no JavaScript execution required.',
    },
    {
        icon: Zap,
        title: 'Complete AI Visibility',
        description: 'AI crawlers can read, understand, and index all your content immediately.',
    },
    {
        icon: Server,
        title: 'Better Citation Potential',
        description: 'Content that AI can access is content that AI can cite in responses.',
    },
];

const comparisonData = [
    { aspect: 'Initial HTML', csr: 'Empty shell + JS bundle', ssr: 'Full page content' },
    { aspect: 'AI Crawler sees', csr: 'Loading spinner or blank', ssr: 'Complete content' },
    { aspect: 'JS Required', csr: 'Yes, for any content', ssr: 'No, content in HTML' },
    { aspect: 'GEO Ready', csr: 'No', ssr: 'Yes' },
    { aspect: 'Citation Potential', csr: 'Very Low', ssr: 'High' },
];

const ssrOptions = [
    { framework: 'Next.js', language: 'React', description: 'Industry-standard SSR for React applications' },
    { framework: 'Nuxt.js', language: 'Vue', description: 'The intuitive Vue framework with built-in SSR' },
    { framework: 'SvelteKit', language: 'Svelte', description: 'Elegant SSR for Svelte applications' },
    { framework: 'Astro', language: 'Multi', description: 'Content-focused with partial hydration' },
    { framework: 'Inertia.js', language: 'Multi', description: 'SSR for Laravel, Rails with Vue/React' },
];

const relatedArticles = [
    { slug: 'what-is-geo', title: 'What Is Generative Engine Optimization (GEO)?' },
    { slug: 'how-ai-search-works', title: 'How AI Search Engines Actually Work' },
    { slug: 'why-llms-txt-matters', title: 'Why llms.txt Matters for GEO' },
];

const jsonLd = {
    '@context': 'https://schema.org',
    '@type': 'Article',
    headline: 'Why Server-Side Rendering (SSR) Matters for GEO and AI Visibility',
    description: 'Learn why Server-Side Rendering is essential for Generative Engine Optimization. Understand how LLMs access content and why CSR sites are invisible to AI search.',
    url: 'https://geosource.ai/resources/why-ssr-matters-for-geo',
    datePublished: '2025-01-20',
    dateModified: '2025-01-20',
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
        '@id': 'https://geosource.ai/resources/why-ssr-matters-for-geo',
    },
    about: [
        {
            '@type': 'DefinedTerm',
            name: 'Server-Side Rendering',
            alternateName: 'SSR',
            description: 'A web development technique where HTML pages are generated on the server for each request, delivering complete content to browsers and crawlers.',
        },
        {
            '@type': 'DefinedTerm',
            name: 'Client-Side Rendering',
            alternateName: 'CSR',
            description: 'A web development technique where pages are rendered in the browser using JavaScript, resulting in empty initial HTML.',
        },
    ],
};

const faqJsonLd = {
    '@context': 'https://schema.org',
    '@type': 'FAQPage',
    mainEntity: [
        {
            '@type': 'Question',
            name: 'What is Server-Side Rendering (SSR)?',
            acceptedAnswer: {
                '@type': 'Answer',
                text: 'Server-Side Rendering (SSR) is a web development technique where HTML pages are generated on the server for each request. This means the complete page content is delivered to browsers (and crawlers) in the initial HTML response, before any JavaScript runs.',
            },
        },
        {
            '@type': 'Question',
            name: 'Why does SSR matter for GEO?',
            acceptedAnswer: {
                '@type': 'Answer',
                text: 'SSR matters for GEO because LLM crawlers (like GPTBot, ClaudeBot, etc.) do not execute JavaScript. They request your page, receive the HTML response, and extract text content from that HTML. With SSR, your full content is in the HTML. With Client-Side Rendering (CSR), your HTML is empty until JavaScript runs — which these crawlers don\'t do.',
            },
        },
        {
            '@type': 'Question',
            name: 'What happens to CSR sites with AI search?',
            acceptedAnswer: {
                '@type': 'Answer',
                text: 'Client-Side Rendered sites return empty HTML shells with JavaScript bundles. Since AI crawlers don\'t execute JavaScript, they see nothing — no content to understand, index, or cite. This makes CSR sites effectively invisible to AI search systems.',
            },
        },
        {
            '@type': 'Question',
            name: 'What frameworks support SSR?',
            acceptedAnswer: {
                '@type': 'Answer',
                text: 'Popular SSR frameworks include Next.js (React), Nuxt.js (Vue), SvelteKit (Svelte), Astro (multi-framework), and Inertia.js (Laravel/Rails with Vue/React). Each provides built-in SSR capabilities for their respective ecosystems.',
            },
        },
    ],
};
</script>

<template>
    <Head title="Why Server-Side Rendering (SSR) Matters for GEO and AI Visibility - GeoSource.ai">
        <meta name="description" content="Learn why Server-Side Rendering is essential for Generative Engine Optimization. Understand how LLMs access content and why CSR sites are invisible to AI search." />
        <meta property="og:title" content="Why Server-Side Rendering (SSR) Matters for GEO and AI Visibility" />
        <meta property="og:description" content="Learn why SSR is essential for GEO and understand how LLMs access content." />
        <meta property="og:type" content="article" />
        <meta property="og:url" content="https://geosource.ai/resources/why-ssr-matters-for-geo" />
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" content="Why Server-Side Rendering (SSR) Matters for GEO and AI Visibility" />
        <meta name="twitter:description" content="Learn why SSR is essential for GEO and understand how LLMs access content." />
        <link rel="canonical" href="https://geosource.ai/resources/why-ssr-matters-for-geo" />
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
                        <span class="text-foreground">Why SSR Matters for GEO</span>
                    </nav>
                </div>
            </div>

            <!-- Article -->
            <article class="py-12">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <!-- Header -->
                    <header class="mb-12">
                        <Badge variant="secondary" class="mb-4">
                            <Server class="mr-1 h-3 w-3" />
                            Technical Guide
                        </Badge>
                        <h1 class="text-3xl font-bold tracking-tight sm:text-4xl lg:text-5xl">
                            Why Server-Side Rendering (SSR) Matters for GEO and AI Visibility
                        </h1>
                        <p class="mt-4 text-lg text-muted-foreground">
                            Understanding why your rendering strategy is critical for AI discoverability and citation.
                        </p>
                    </header>

                    <!-- Definition Section -->
                    <section class="mb-12" aria-labelledby="definition">
                        <h2 id="definition" class="text-2xl font-bold mb-6">What is Server-Side Rendering?</h2>
                        <Card class="border-primary/50 bg-primary/5">
                            <CardContent class="pt-6">
                                <p class="text-lg leading-relaxed">
                                    <dfn id="ssr-definition" class="font-semibold not-italic"><strong>Server-Side Rendering (SSR)</strong></dfn> is a web development technique where HTML pages are generated on the server for each request. This means the complete page content is delivered to browsers (and crawlers) in the initial HTML response, before any JavaScript runs.
                                </p>
                            </CardContent>
                        </Card>
                        <div class="mt-6 space-y-4 text-muted-foreground">
                            <p>
                                In contrast, <strong class="text-foreground">Client-Side Rendering (CSR)</strong> sends an empty HTML shell and a JavaScript bundle. The browser must execute the JavaScript to render any content.
                            </p>
                            <p class="text-lg font-medium text-foreground">
                                This distinction is critical for GEO because AI crawlers don't execute JavaScript.
                            </p>
                        </div>
                    </section>

                    <Separator class="my-12" />

                    <!-- How LLMs Access Content Section -->
                    <section class="mb-12" aria-labelledby="how-llms-access">
                        <h2 id="how-llms-access" class="text-2xl font-bold mb-6">How LLMs Access Your Content</h2>
                        <p class="text-muted-foreground mb-8">
                            Understanding the AI crawling process reveals why SSR is essential:
                        </p>

                        <div class="space-y-4">
                            <div v-for="step in howLlmsAccessContent" :key="step.step" class="flex items-start gap-4">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary text-primary-foreground text-sm font-bold">
                                    {{ step.step }}
                                </span>
                                <div class="pt-1">
                                    <h3 class="font-semibold">{{ step.title }}</h3>
                                    <p class="text-muted-foreground mt-1">{{ step.description }}</p>
                                </div>
                            </div>
                        </div>

                        <Card class="mt-8 border-red-500/50 bg-red-500/5">
                            <CardContent class="pt-6">
                                <div class="flex items-start gap-3">
                                    <AlertTriangle class="h-6 w-6 text-red-500 shrink-0 mt-0.5" />
                                    <div>
                                        <p class="font-medium text-foreground">Critical Point</p>
                                        <p class="text-muted-foreground mt-1">
                                            <strong class="text-foreground">LLM crawlers (GPTBot, ClaudeBot, PerplexityBot, etc.) do NOT execute JavaScript.</strong> They only see what's in the initial HTML response.
                                        </p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </section>

                    <Separator class="my-12" />

                    <!-- The CSR Problem Section -->
                    <section class="mb-12" aria-labelledby="csr-problem">
                        <h2 id="csr-problem" class="text-2xl font-bold mb-6">The Client-Side Rendering Problem</h2>
                        <p class="text-muted-foreground mb-8">
                            Here's what happens when an AI crawler visits a CSR site:
                        </p>

                        <div class="grid gap-6 sm:grid-cols-3">
                            <Card v-for="problem in csrProblems" :key="problem.title" class="h-full border-red-500/30">
                                <CardContent class="pt-6">
                                    <div class="flex flex-col items-start gap-3">
                                        <div class="rounded-lg bg-red-500/10 p-2">
                                            <component :is="problem.icon" class="h-5 w-5 text-red-500" />
                                        </div>
                                        <div>
                                            <h3 class="font-semibold">{{ problem.title }}</h3>
                                            <p class="mt-1 text-sm text-muted-foreground">{{ problem.description }}</p>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>

                        <Card class="mt-8 bg-muted/50">
                            <CardContent class="pt-6">
                                <p class="text-sm font-mono text-center text-muted-foreground">
                                    What AI sees with CSR:
                                </p>
                                <pre class="mt-4 text-sm overflow-x-auto bg-background rounded p-4"><code>&lt;!DOCTYPE html&gt;
&lt;html&gt;
&lt;head&gt;&lt;title&gt;My App&lt;/title&gt;&lt;/head&gt;
&lt;body&gt;
  &lt;div id="app"&gt;&lt;/div&gt;
  &lt;script src="/bundle.js"&gt;&lt;/script&gt;
&lt;/body&gt;
&lt;/html&gt;

<span class="text-red-500">// AI sees: NOTHING useful</span></code></pre>
                            </CardContent>
                        </Card>
                    </section>

                    <Separator class="my-12" />

                    <!-- SSR Benefits Section -->
                    <section class="mb-12" aria-labelledby="ssr-benefits">
                        <h2 id="ssr-benefits" class="text-2xl font-bold mb-6">The SSR Advantage for GEO</h2>
                        <p class="text-muted-foreground mb-8">
                            Server-Side Rendering solves the visibility problem:
                        </p>

                        <div class="grid gap-6 sm:grid-cols-3">
                            <Card v-for="benefit in ssrBenefits" :key="benefit.title" class="h-full border-green-500/30">
                                <CardContent class="pt-6">
                                    <div class="flex flex-col items-start gap-3">
                                        <div class="rounded-lg bg-green-500/10 p-2">
                                            <component :is="benefit.icon" class="h-5 w-5 text-green-500" />
                                        </div>
                                        <div>
                                            <h3 class="font-semibold">{{ benefit.title }}</h3>
                                            <p class="mt-1 text-sm text-muted-foreground">{{ benefit.description }}</p>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>

                        <Card class="mt-8 bg-muted/50">
                            <CardContent class="pt-6">
                                <p class="text-sm font-mono text-center text-muted-foreground">
                                    What AI sees with SSR:
                                </p>
                                <pre class="mt-4 text-sm overflow-x-auto bg-background rounded p-4"><code>&lt;!DOCTYPE html&gt;
&lt;html&gt;
&lt;head&gt;&lt;title&gt;Why SSR Matters | GeoSource.ai&lt;/title&gt;&lt;/head&gt;
&lt;body&gt;
  &lt;article&gt;
    &lt;h1&gt;Why Server-Side Rendering Matters for GEO&lt;/h1&gt;
    &lt;p&gt;Server-Side Rendering is a technique where...&lt;/p&gt;
    &lt;h2&gt;How LLMs Access Your Content&lt;/h2&gt;
    &lt;p&gt;Understanding the AI crawling process...&lt;/p&gt;
    ...full content here...
  &lt;/article&gt;
&lt;/body&gt;
&lt;/html&gt;

<span class="text-green-500">// AI sees: EVERYTHING</span></code></pre>
                            </CardContent>
                        </Card>
                    </section>

                    <Separator class="my-12" />

                    <!-- Comparison Section -->
                    <section class="mb-12" aria-labelledby="comparison">
                        <h2 id="comparison" class="text-2xl font-bold mb-6">CSR vs SSR for GEO</h2>

                        <div class="overflow-x-auto">
                            <table class="w-full border-collapse">
                                <thead>
                                    <tr class="border-b">
                                        <th class="py-3 px-4 text-left font-semibold">Aspect</th>
                                        <th class="py-3 px-4 text-left font-semibold text-red-500">CSR</th>
                                        <th class="py-3 px-4 text-left font-semibold text-green-500">SSR</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="row in comparisonData" :key="row.aspect" class="border-b">
                                        <td class="py-3 px-4 text-muted-foreground">{{ row.aspect }}</td>
                                        <td class="py-3 px-4 text-red-500">{{ row.csr }}</td>
                                        <td class="py-3 px-4 text-green-500 font-medium">{{ row.ssr }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <Separator class="my-12" />

                    <!-- Framework Options Section -->
                    <section class="mb-12" aria-labelledby="frameworks">
                        <h2 id="frameworks" class="text-2xl font-bold mb-6">SSR Frameworks for Your Stack</h2>
                        <p class="text-muted-foreground mb-6">
                            Popular frameworks that provide SSR capabilities:
                        </p>

                        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            <Card v-for="option in ssrOptions" :key="option.framework" class="h-full">
                                <CardContent class="pt-6">
                                    <div class="flex items-center justify-between mb-2">
                                        <h3 class="font-semibold">{{ option.framework }}</h3>
                                        <Badge variant="outline">{{ option.language }}</Badge>
                                    </div>
                                    <p class="text-sm text-muted-foreground">{{ option.description }}</p>
                                </CardContent>
                            </Card>
                        </div>

                        <Card class="mt-8 border-amber-500/50 bg-amber-500/5">
                            <CardContent class="pt-6">
                                <div class="flex items-start gap-3">
                                    <Lightbulb class="h-6 w-6 text-amber-500 shrink-0 mt-0.5" />
                                    <div>
                                        <p class="font-medium text-foreground">GeoSource.ai Uses Inertia.js</p>
                                        <p class="text-muted-foreground mt-1">
                                            This very page is rendered using Laravel with Inertia.js and Vue, demonstrating SSR in action. The full content is available in the initial HTML response.
                                        </p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </section>

                    <Separator class="my-12" />

                    <!-- Summary Section -->
                    <section class="mb-12" aria-labelledby="summary">
                        <h2 id="summary" class="text-2xl font-bold mb-6">The Bottom Line</h2>
                        <Card class="border-primary bg-primary/5">
                            <CardContent class="pt-6">
                                <p class="text-xl font-medium text-center">
                                    If AI can't see your content, AI can't cite your content. SSR ensures your pages are visible to AI search from the first request.
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
                        <Link href="/resources/why-llms-txt-matters" class="inline-flex items-center text-muted-foreground hover:text-foreground">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Prev: Why llms.txt Matters
                        </Link>
                        <Link href="/resources" class="inline-flex items-center text-primary hover:underline">
                            All Resources
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
