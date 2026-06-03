<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import {
    ArrowRight,
    ArrowLeft,
    HelpCircle,
    Lightbulb,
    CheckCircle,
    XCircle,
    Calendar,
    Target,
} from 'lucide-vue-next';
import SkipNav from '@/components/resources/SkipNav.vue';
import ResourceHeader from '@/components/resources/ResourceHeader.vue';
import ResourceFooter from '@/components/resources/ResourceFooter.vue';
import ResourceBreadcrumb from '@/components/resources/ResourceBreadcrumb.vue';

const publishedDate = '2026-01-20';
const publishedDateFormatted = new Date(publishedDate).toLocaleDateString('en-US', { month: 'long', year: 'numeric' });

const breadcrumbItems = [
    { label: 'Resources', href: '/resources' },
    { label: 'Question Coverage for GEO' },
];

const answerabilityChecklist = [
    'The answer appears in the first sentence under the relevant heading.',
    'The page focuses on one core question, not ten adjacent ones.',
    'Hedging language ("it depends," "generally") is moved out of the answer sentence.',
    'A clean "X is Y" definition exists for the primary term, near the top.',
    'Adjacent questions live on their own pages, linked from this one.',
];

const answerVsListing = [
    {
        label: 'Listing the question',
        example: '"In this guide we explore what GEO is, how it differs from SEO, why it matters, and how to start." (Then 800 words before the actual definition.)',
        verdict: 'Weak. The assistant has to wade through preamble to find a quotable line.',
        good: false,
    },
    {
        label: 'Answering the question',
        example: '"Generative Engine Optimization is the practice of structuring content so AI assistants quote it when they answer user questions."',
        verdict: 'Strong. Declarative, self-contained, easy to lift as a citation.',
        good: true,
    },
];

const oneQuestionRule = [
    {
        title: 'Wrong: one page, ten questions',
        body: 'A "complete guide" page that tries to answer what, how, why, when, where, who, and how-much in one document. Every section competes for attention, and no single chunk is clearly the best answer to any one query.',
    },
    {
        title: 'Right: ten pages, one question each',
        body: 'A topic cluster where each page owns one question with a direct answer in the opening sentence. The hub links them together. Each page is a clean retrieval target.',
    },
];

const relatedDefinitions = [
    { slug: 'what-is-geo', title: 'What Is Generative Engine Optimization (GEO)?' },
    { slug: 'what-is-a-geo-score', title: 'What Is a GEO Score?' },
];

const relatedResources = [
    { slug: 'e-e-a-t-and-geo', title: 'E-E-A-T and Generative Engine Optimization (GEO)' },
    { slug: 'ai-citations-and-geo', title: 'Citations and GEO: How AI Chooses Sources' },
    { slug: 'readability-and-geo', title: 'Readability and GEO' },
    { slug: 'content-freshness-for-geo', title: 'Content Freshness and GEO' },
    { slug: 'multimedia-and-geo', title: 'Multimedia and GEO' },
];

const jsonLd = {
    '@context': 'https://schema.org',
    '@type': 'Article',
    headline: 'Question Coverage and GEO: Why Answerability Is the Real Signal',
    description: 'Question coverage — counting how many questions a page lists — does not move AI citations. Answerability does. Here is what to do instead.',
    url: 'https://geosource.ai/resources/question-coverage-for-geo',
    datePublished: '2026-01-20',
    dateModified: '2026-06-03',
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
        '@id': 'https://geosource.ai/resources/question-coverage-for-geo',
    },
};

const faqJsonLd = {
    '@context': 'https://schema.org',
    '@type': 'FAQPage',
    mainEntity: [
        {
            '@type': 'Question',
            name: 'Does adding more FAQs to a page improve AI citations?',
            acceptedAnswer: {
                '@type': 'Answer',
                text: 'No. Listing more questions on a page is not what gets cited. What gets cited is a direct, declarative answer placed at the top of the relevant section.',
            },
        },
        {
            '@type': 'Question',
            name: 'What is Answerability?',
            acceptedAnswer: {
                '@type': 'Answer',
                text: 'Answerability is whether a page contains a clear, direct answer to the question it is targeting. It is a structural property of how the answer is written and placed, not a count of questions covered.',
            },
        },
        {
            '@type': 'Question',
            name: 'Are FAQ sections useless then?',
            acceptedAnswer: {
                '@type': 'Answer',
                text: 'No. FAQ sections still help with accessibility, internal linking, and traditional SEO. They are just not the lever that moves AI citation rate.',
            },
        },
    ],
};
</script>

<template>
    <Head title="Question Coverage and GEO - GeoSource.ai">
        <meta name="description" content="Question coverage — counting how many questions a page lists — does not move AI citations. Answerability does. Here is what to do instead." />
        <meta property="og:title" content="Question Coverage and GEO: Why Answerability Is the Real Signal" />
        <meta property="og:description" content="Listing questions does not get you cited. Answering them clearly, at the top, does." />
        <meta property="og:type" content="article" />
        <meta property="og:url" content="https://geosource.ai/resources/question-coverage-for-geo" />
        <meta property="og:site_name" content="GeoSource.ai" />
        <meta property="article:published_time" content="2026-01-20" />
        <meta property="article:modified_time" content="2026-06-03" />
        <meta property="article:author" content="GeoSource.ai" />
        <meta property="article:section" content="Content Strategy" />
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" content="Question Coverage and GEO: Why Answerability Is the Real Signal" />
        <meta name="twitter:description" content="Listing questions does not get you cited. Answering them clearly, at the top, does." />
        <meta name="twitter:site" content="@geosource_ai" />
        <meta name="robots" content="index, follow" />
        <link rel="canonical" href="https://geosource.ai/resources/question-coverage-for-geo" />
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
                            <HelpCircle class="mr-1 h-3 w-3" aria-hidden="true" />
                            GEO Pillar
                        </Badge>
                        <h1 class="text-3xl font-bold tracking-tight sm:text-4xl lg:text-5xl">
                            Question Coverage and GEO: Why Answerability Is the Real Signal
                        </h1>
                        <p class="mt-4 text-lg text-muted-foreground">
                            Listing thirty FAQs at the bottom of a page does not get you cited. Answering one question clearly, at the top, does.
                        </p>
                        <div class="mt-4 flex items-center gap-2 text-sm text-muted-foreground">
                            <Calendar class="h-4 w-4" aria-hidden="true" />
                            <time :datetime="publishedDate">{{ publishedDateFormatted }}</time>
                        </div>
                    </header>

                    <!-- Intro -->
                    <section class="mb-12" aria-labelledby="intro-heading">
                        <h2 id="intro-heading" class="sr-only">Introduction</h2>
                        <p class="text-muted-foreground mb-4">
                            We used to talk about "question coverage" as if it were a quantity metric — pack more FAQs onto the page, hit more variations of the query, expand the surface area. After looking at thousands of pages across our own studies and external work in our <Link href="/research" class="text-primary hover:underline">research library</Link>, that framing did not hold up. Question coverage is a weak, flat predictor of whether an AI assistant cites a page.
                        </p>
                        <p class="text-muted-foreground mb-4">
                            What does move the needle is <strong class="text-foreground">Answerability</strong>: whether the page actually answers the question directly, in plain declarative language, near the top of the relevant section. That is a structural property of the writing, not a count of questions on the page. For the broader picture of what does and does not predict citation, see <Link href="/blog/what-predicts-ai-citations" class="text-primary hover:underline">What predicts AI citations</Link>.
                        </p>
                    </section>

                    <!-- Definition Section -->
                    <section class="mb-12" aria-labelledby="definition">
                        <h2 id="definition" class="text-2xl font-bold mb-6">Answerability, defined</h2>
                        <Card class="border-primary/50 bg-primary/5">
                            <CardContent class="pt-6">
                                <p class="text-lg leading-relaxed">
                                    <dfn class="font-semibold not-italic"><strong>Answerability</strong></dfn> is whether a page contains a clear, direct, declarative answer to the question it is targeting — placed where a retrieval system can find it without wading through preamble.
                                </p>
                            </CardContent>
                        </Card>
                        <p class="mt-6 text-muted-foreground">
                            That is the pillar to optimize. "Question coverage" was the wrong name for it, because it implied volume. The real signal is whether one specific answer is easy to lift as a citation.
                        </p>
                    </section>

                    <Separator class="my-12" />

                    <!-- Why coverage fails -->
                    <section class="mb-12" aria-labelledby="why-coverage-fails">
                        <h2 id="why-coverage-fails" class="text-2xl font-bold mb-6">Why "more questions" does not work</h2>
                        <p class="text-muted-foreground mb-6">
                            When an AI assistant retrieves your page, it does not reward you for the FAQ accordion at the bottom. It is looking for the best chunk to quote. A few things go wrong when you optimize for coverage:
                        </p>
                        <div class="grid gap-4">
                            <Card>
                                <CardContent class="pt-6">
                                    <div class="flex items-start gap-3">
                                        <XCircle class="h-5 w-5 text-rose-500 shrink-0 mt-0.5" aria-hidden="true" />
                                        <div>
                                            <p class="font-medium">More questions on a page means more competing chunks.</p>
                                            <p class="text-sm text-muted-foreground mt-1">The assistant has more text to sift through, and no single passage is clearly the best answer to the query it cares about.</p>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardContent class="pt-6">
                                    <div class="flex items-start gap-3">
                                        <XCircle class="h-5 w-5 text-rose-500 shrink-0 mt-0.5" aria-hidden="true" />
                                        <div>
                                            <p class="font-medium">Adjacent questions dilute the topical signal.</p>
                                            <p class="text-sm text-muted-foreground mt-1">A page that tries to cover "what," "how," and "why" all at once stops being the canonical answer to any of them.</p>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardContent class="pt-6">
                                    <div class="flex items-start gap-3">
                                        <XCircle class="h-5 w-5 text-rose-500 shrink-0 mt-0.5" aria-hidden="true" />
                                        <div>
                                            <p class="font-medium">FAQ blocks at the bottom rarely get retrieved.</p>
                                            <p class="text-sm text-muted-foreground mt-1">Burying a direct answer in a collapsed accordion after 1,500 words of context puts it past the point where most retrieval systems are still paying attention to a single page.</p>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                        <Card class="mt-6 border-amber-500/50 bg-amber-500/5">
                            <CardContent class="pt-6">
                                <div class="flex items-start gap-3">
                                    <Lightbulb class="h-6 w-6 text-amber-500 shrink-0 mt-0.5" aria-hidden="true" />
                                    <p class="text-muted-foreground">
                                        <strong class="text-foreground">FAQ sections are not useless.</strong> They still help with accessibility, internal linking, and traditional SEO snippets. They are just not the lever that moves AI citation rate. Treat them as a secondary feature, not the strategy.
                                    </p>
                                </div>
                            </CardContent>
                        </Card>
                    </section>

                    <Separator class="my-12" />

                    <!-- Answering vs listing -->
                    <section class="mb-12" aria-labelledby="answering-vs-listing">
                        <h2 id="answering-vs-listing" class="text-2xl font-bold mb-6">Answering vs. listing</h2>
                        <p class="text-muted-foreground mb-6">
                            The cleanest way to feel the difference is to read two versions of the same opening line:
                        </p>
                        <div class="grid gap-4">
                            <Card
                                v-for="item in answerVsListing"
                                :key="item.label"
                                :class="item.good ? 'border-green-500/50 bg-green-500/5' : 'border-rose-500/40 bg-rose-500/5'"
                            >
                                <CardContent class="pt-6">
                                    <div class="flex items-start gap-3">
                                        <component
                                            :is="item.good ? CheckCircle : XCircle"
                                            :class="['h-5 w-5 shrink-0 mt-0.5', item.good ? 'text-green-500' : 'text-rose-500']"
                                            aria-hidden="true"
                                        />
                                        <div>
                                            <p class="font-semibold">{{ item.label }}</p>
                                            <p class="mt-2 italic text-muted-foreground">{{ item.example }}</p>
                                            <p class="mt-2 text-sm"><strong class="text-foreground">Verdict:</strong> {{ item.verdict }}</p>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </section>

                    <Separator class="my-12" />

                    <!-- How to write answerable content -->
                    <section class="mb-12" aria-labelledby="how-to">
                        <h2 id="how-to" class="text-2xl font-bold mb-6">How to write for Answerability</h2>
                        <p class="text-muted-foreground mb-6">
                            Five rules, in order of how much they matter:
                        </p>
                        <ol class="space-y-6">
                            <li>
                                <p class="font-semibold">Put the answer in the first sentence under the heading.</p>
                                <p class="text-muted-foreground mt-2">
                                    Not after a paragraph of context. Not after "let's first define our terms." The reader (and the retrieval system) should be able to copy the opening line and have a complete, accurate answer in hand.
                                </p>
                            </li>
                            <li>
                                <p class="font-semibold">Use "X is Y" constructions for definitions.</p>
                                <p class="text-muted-foreground mt-2">
                                    "Answerability is whether a page directly answers the question it targets." That shape — subject, copula, predicate — is the most citation-friendly sentence in English. Assistants quote it because it is self-contained.
                                </p>
                            </li>
                            <li>
                                <p class="font-semibold">One page, one question.</p>
                                <p class="text-muted-foreground mt-2">
                                    If you have ten questions on a topic, that is a topic cluster of ten pages — not one omnibus guide. Each page owns one question and links to the others. The hub page can summarize and route.
                                </p>
                            </li>
                            <li>
                                <p class="font-semibold">Move hedging out of the answer sentence.</p>
                                <p class="text-muted-foreground mt-2">
                                    "It depends," "generally," "in most cases" — keep them, but put them in sentence two or three. The first sentence has to commit. Nuance is fine, but it cannot be the first thing the assistant sees.
                                </p>
                            </li>
                            <li>
                                <p class="font-semibold">Let the page structure mirror the question.</p>
                                <p class="text-muted-foreground mt-2">
                                    If the target query is "How does X work?" the h2 should be "How X works" and the next sentence should describe how X works. Do not bury the answer under a clever heading.
                                </p>
                            </li>
                        </ol>
                    </section>

                    <Separator class="my-12" />

                    <!-- Cluster pattern -->
                    <section class="mb-12" aria-labelledby="one-question-rule">
                        <h2 id="one-question-rule" class="text-2xl font-bold mb-6">One page = one question</h2>
                        <p class="text-muted-foreground mb-6">
                            This is the single biggest shift for teams that have been writing "ultimate guides." It is hard to give up the long-form omnibus, but the data is clear: a focused page outperforms a sprawling one for AI citation.
                        </p>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <Card v-for="item in oneQuestionRule" :key="item.title">
                                <CardContent class="pt-6">
                                    <div class="flex items-start gap-3">
                                        <Target class="h-5 w-5 text-primary shrink-0 mt-0.5" aria-hidden="true" />
                                        <div>
                                            <p class="font-semibold">{{ item.title }}</p>
                                            <p class="text-sm text-muted-foreground mt-2">{{ item.body }}</p>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </section>

                    <Separator class="my-12" />

                    <!-- Checklist -->
                    <section class="mb-12" aria-labelledby="checklist">
                        <h2 id="checklist" class="text-2xl font-bold mb-6">Answerability checklist</h2>
                        <p class="text-muted-foreground mb-6">
                            Run a page through this before you publish:
                        </p>
                        <Card>
                            <CardContent class="pt-6">
                                <ul class="space-y-3">
                                    <li v-for="item in answerabilityChecklist" :key="item" class="flex items-start gap-3">
                                        <CheckCircle class="h-5 w-5 text-green-500 shrink-0 mt-0.5" aria-hidden="true" />
                                        <span>{{ item }}</span>
                                    </li>
                                </ul>
                            </CardContent>
                        </Card>
                    </section>

                    <Separator class="my-12" />

                    <!-- What about FAQ schema -->
                    <section class="mb-12" aria-labelledby="faq-schema">
                        <h2 id="faq-schema" class="text-2xl font-bold mb-6">Where FAQ schema still fits</h2>
                        <p class="text-muted-foreground mb-4">
                            FAQPage schema is a useful tool when the questions on the page are the page — a support article, a pricing FAQ, a product comparison. In those cases the schema describes what is actually there, which is the right reason to use it.
                        </p>
                        <p class="text-muted-foreground mb-4">
                            What does not work is tacking an FAQ block onto every page in the hope that it adds "coverage." If the questions are not naturally part of the content, the schema does not help retrieval and may dilute the topical focus of the page.
                        </p>
                        <p class="text-muted-foreground">
                            Use it when the page is genuinely a Q&A. Skip it when it is bolted on.
                        </p>
                    </section>

                    <Separator class="my-12" />

                    <!-- Key Takeaway -->
                    <section class="mb-12" aria-labelledby="takeaway">
                        <h2 id="takeaway" class="text-2xl font-bold mb-6">The bottom line</h2>
                        <Card class="border-primary bg-primary/5">
                            <CardContent class="pt-6">
                                <p class="text-xl font-medium text-center">
                                    Stop counting questions.<br />
                                    Start answering them — clearly, early, and one at a time.
                                </p>
                            </CardContent>
                        </Card>
                        <p class="mt-6 text-muted-foreground text-center">
                            For the full picture of what does and does not predict AI citation, see our <Link href="/research" class="text-primary hover:underline">research library</Link> and <Link href="/blog/what-predicts-ai-citations" class="text-primary hover:underline">What predicts AI citations</Link>.
                        </p>
                    </section>

                    <Separator class="my-12" />

                    <!-- Related Definitions -->
                    <section class="mb-12" aria-labelledby="related-definitions">
                        <h2 id="related-definitions" class="text-2xl font-bold mb-6">Related Definitions</h2>
                        <ul class="grid gap-4 sm:grid-cols-2" aria-label="Related definition articles">
                            <li v-for="article in relatedDefinitions" :key="article.slug">
                                <Link
                                    :href="`/resources/${article.slug}`"
                                    class="group block h-full"
                                    :aria-label="`Read more about ${article.title}`"
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

                    <Separator class="my-12" />

                    <!-- Related Resources -->
                    <section aria-labelledby="related">
                        <h2 id="related" class="text-2xl font-bold mb-6">Related Resources</h2>
                        <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3" aria-label="Related resource articles">
                            <li v-for="article in relatedResources" :key="article.slug">
                                <Link
                                    :href="`/resources/${article.slug}`"
                                    class="group block h-full"
                                    :aria-label="`Read more about ${article.title}`"
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
                    <nav class="mt-12 flex items-center justify-between border-t pt-8" aria-label="Article navigation">
                        <Link href="/resources/readability-and-geo" class="inline-flex items-center text-muted-foreground hover:text-foreground">
                            <ArrowLeft class="mr-2 h-4 w-4" aria-hidden="true" />
                            Previous: Readability and GEO
                        </Link>
                        <Link href="/resources/multimedia-and-geo" class="inline-flex items-center text-primary hover:underline">
                            Next: Multimedia and GEO
                            <ArrowRight class="ml-2 h-4 w-4" aria-hidden="true" />
                        </Link>
                    </nav>
                </div>
            </article>

            <!-- CTA Section -->
            <section class="border-t bg-muted/30 py-12" aria-labelledby="cta-heading">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 text-center">
                    <h2 id="cta-heading" class="text-2xl font-bold">See how your pages score on Answerability</h2>
                    <p class="mt-2 text-muted-foreground">Run a page through GeoSource.ai and find the answers that are buried, hedged, or missing.</p>
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
