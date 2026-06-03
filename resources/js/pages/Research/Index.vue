<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import SkipNav from '@/components/resources/SkipNav.vue';
import ResourceHeader from '@/components/resources/ResourceHeader.vue';
import ResourceFooter from '@/components/resources/ResourceFooter.vue';
import { Microscope, BarChart3, ShoppingCart, BookOpen, ArrowRight, Sparkles } from 'lucide-vue-next';

const canonicalUrl = 'https://geosource.ai/research';

// The most important read — the cross-study synthesis pulls the headline
// findings out of all four studies. New visitors should start here.
const startHere = {
    href: '/blog/what-predicts-ai-citations',
    title: 'What actually predicts AI citations',
    description: 'Six findings that held across every one of our studies, with practical implications for what to optimize. The fastest way to understand our full research line.',
    readTime: '8 min read',
};

// The underlying studies, ordered chronologically (oldest → newest).
const studies = [
    {
        href: '/blog/geo-citation-study',
        kind: 'Foundational study',
        icon: BarChart3,
        title: 'The GEO citation study',
        question: 'Which content signals predict whether AI cites your site?',
        method: '61 sites · 17 industries · 3 phases',
        published: 'June 1, 2026',
    },
    {
        href: '/blog/eeat-content-type-study',
        kind: 'Controlled follow-up',
        icon: Microscope,
        title: 'E-E-A-T &amp; content type',
        question: 'Does the negative E-E-A-T effect hold when you control for what kind of page it is?',
        method: '40 URLs · 2×2 controlled design · methodology fix mid-study',
        published: 'June 2, 2026',
    },
    {
        href: '/blog/ecommerce-recommendation-survival',
        kind: 'Commercial-outcome study',
        icon: ShoppingCart,
        title: 'Ecommerce recommendation survival',
        question: 'Which brands survive the AI shopping journey all the way to a purchase recommendation?',
        method: '40 brands · 12 categories · 4 shopping-journey stages',
        published: 'June 2, 2026',
    },
];
</script>

<template>
    <Head>
        <title>Research | GeoSource.ai</title>
        <meta name="description" content="Original research from GeoSource.ai on how AI platforms cite and recommend content. A four-study research line on what actually predicts AI citation." />
        <link rel="canonical" :href="canonicalUrl" />
        <meta property="og:title" content="Research | GeoSource.ai" />
        <meta property="og:description" content="Original research on how AI platforms cite and recommend content." />
        <meta property="og:url" :content="canonicalUrl" />
        <meta property="og:type" content="website" />
    </Head>

    <div class="min-h-screen bg-background text-foreground">
        <SkipNav />
        <ResourceHeader />

        <main id="main-content">
            <!-- Hero -->
            <section class="border-b bg-gradient-to-b from-primary/5 to-background py-16 sm:py-24">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <Badge variant="secondary" class="mb-4">
                        <Microscope class="mr-1.5 h-3 w-3" aria-hidden="true" />
                        Original research
                    </Badge>
                    <h1 class="text-3xl font-bold tracking-tight sm:text-4xl lg:text-5xl">
                        How AI assistants decide what to cite
                    </h1>
                    <p class="mt-4 max-w-3xl text-lg text-muted-foreground sm:text-xl leading-relaxed">
                        A four-study research line testing which content signals actually predict whether AI platforms cite a website — and which signals don't, even when conventional wisdom says they should. Methodology, raw data, and what each finding means in practice.
                    </p>
                </div>
            </section>

            <!-- Start here: the synthesis -->
            <section class="py-12 sm:py-16">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <div class="mb-6 flex items-center gap-2">
                        <Sparkles class="h-5 w-5 text-primary" aria-hidden="true" />
                        <h2 class="text-sm font-semibold uppercase tracking-wide text-primary">Start here</h2>
                    </div>
                    <Link :href="startHere.href" class="block group focus:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 rounded-lg">
                        <Card class="border-2 border-primary/40 bg-gradient-to-br from-primary/5 to-transparent transition-shadow hover:shadow-lg">
                            <CardContent class="p-6 sm:p-8">
                                <Badge variant="secondary" class="mb-3">Cross-study synthesis</Badge>
                                <h3 class="text-2xl font-bold leading-tight group-hover:text-primary transition-colors">
                                    {{ startHere.title }}
                                </h3>
                                <p class="mt-3 text-muted-foreground leading-relaxed">
                                    {{ startHere.description }}
                                </p>
                                <div class="mt-4 flex items-center gap-3 text-sm text-muted-foreground">
                                    <span>{{ startHere.readTime }}</span>
                                </div>
                                <span class="mt-4 inline-flex items-center gap-1 text-sm font-medium text-primary">
                                    Read the synthesis <ArrowRight class="h-3.5 w-3.5" aria-hidden="true" />
                                </span>
                            </CardContent>
                        </Card>
                    </Link>
                </div>
            </section>

            <Separator />

            <!-- Underlying studies -->
            <section class="py-12 sm:py-16">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <h2 class="text-2xl font-bold tracking-tight sm:text-3xl mb-2">The studies</h2>
                    <p class="text-muted-foreground mb-8 max-w-3xl">
                        Each study tests one specific question. Read in order if you want the full picture; jump to whichever matches your situation if you don't.
                    </p>

                    <ol class="space-y-4">
                        <li v-for="s in studies" :key="s.href">
                            <Link
                                :href="s.href"
                                class="block group focus:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 rounded-lg"
                            >
                                <Card class="transition-shadow hover:shadow-md">
                                    <CardContent class="p-6">
                                        <div class="flex items-start gap-4">
                                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary">
                                                <component :is="s.icon" class="h-5 w-5" aria-hidden="true" />
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="flex flex-wrap items-center gap-2 mb-2">
                                                    <Badge variant="outline" class="text-[10px]">{{ s.kind }}</Badge>
                                                    <span class="text-xs text-muted-foreground">{{ s.published }}</span>
                                                </div>
                                                <h3 class="text-lg font-semibold leading-snug group-hover:text-primary transition-colors" v-html="s.title" />
                                                <p class="mt-2 text-sm text-muted-foreground leading-relaxed">
                                                    <strong class="text-foreground">The question:</strong> {{ s.question }}
                                                </p>
                                                <p class="mt-2 text-xs text-muted-foreground font-mono">
                                                    {{ s.method }}
                                                </p>
                                                <span class="mt-3 inline-flex items-center gap-1 text-sm font-medium text-primary">
                                                    Read the study <ArrowRight class="h-3.5 w-3.5" aria-hidden="true" />
                                                </span>
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>
                            </Link>
                        </li>
                    </ol>
                </div>
            </section>

            <Separator />

            <!-- What to do with this -->
            <section class="py-12 sm:py-16">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <h2 class="text-2xl font-bold tracking-tight sm:text-3xl mb-6">Looking for "how do I do GEO?"</h2>
                    <Card class="border-l-4 border-l-primary">
                        <CardContent class="p-6">
                            <p class="text-muted-foreground leading-relaxed">
                                The studies on this page tell you what AI assistants actually reward and why. If you're looking for foundational explainers, pillar deep-dives, frameworks, and how-to guides instead, head to <Link href="/resources" class="text-primary font-medium hover:underline">Learn GEO</Link> — that's where the practical guidance lives.
                            </p>
                        </CardContent>
                    </Card>
                </div>
            </section>
        </main>

        <ResourceFooter />
    </div>
</template>
