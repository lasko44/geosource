<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import SkipNav from '@/components/resources/SkipNav.vue';
import ResourceHeader from '@/components/resources/ResourceHeader.vue';
import ResourceFooter from '@/components/resources/ResourceFooter.vue';
import ResourceBreadcrumb from '@/components/resources/ResourceBreadcrumb.vue';
import { ArrowRight, BarChart3, Globe, Database, TrendingUp, Zap } from 'lucide-vue-next';
import { register } from '@/routes';

interface Benchmark {
    industry: string;
    sample_size: number;
    avg_score: number;
    avg_citation_rate: number;
    p25: number | null;
    p50: number | null;
    p75: number | null;
}

const props = defineProps<{
    stats: {
        total_scans: number;
        total_citations: number;
        total_correlations: number;
        unique_domains: number;
        industries_count: number;
        overall_avg_score: number;
        overall_avg_citation_rate: number;
        benchmarks: Benchmark[];
    };
}>();

const breadcrumbItems = [
    { label: 'Resources', href: '/resources' },
    { label: 'Industry Benchmarks' },
];

const canonicalUrl = 'https://geosource.ai/benchmarks';

const jsonLd = {
    '@context': 'https://schema.org',
    '@type': 'Dataset',
    name: 'GEO Industry Benchmarks',
    description: 'Live industry benchmarks for Generative Engine Optimization scores and AI citation rates, updated continuously from real scan and citation data.',
    url: canonicalUrl,
    creator: { '@type': 'Organization', name: 'GeoSource.ai', url: 'https://geosource.ai' },
};
</script>

<template>
    <Head>
        <title>GEO Industry Benchmarks | GeoSource.ai</title>
        <meta name="description" content="Live GEO scoring benchmarks by industry. See how your content compares across healthcare, SaaS, ecommerce, finance, and more. Updated from real scan and citation data." />
        <link rel="canonical" :href="canonicalUrl" />
        <meta property="og:title" content="GEO Industry Benchmarks | GeoSource.ai" />
        <meta property="og:description" content="Live GEO scoring benchmarks by industry, updated from real scan and citation data." />
        <meta property="og:url" :content="canonicalUrl" />
        <meta property="og:type" content="website" />
        <meta property="og:site_name" content="GeoSource.ai" />
        <component :is="'script'" type="application/ld+json">{{ JSON.stringify(jsonLd) }}</component>
    </Head>

    <div class="min-h-screen bg-background text-foreground">
        <SkipNav />
        <ResourceHeader />

        <main id="main-content">
            <ResourceBreadcrumb :items="breadcrumbItems" />

            <!-- Hero -->
            <section class="border-b bg-gradient-to-b from-primary/5 to-background py-16 sm:py-20">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <Badge variant="secondary" class="mb-4">
                        <Database class="mr-1.5 h-3 w-3" aria-hidden="true" />
                        Live Data
                    </Badge>
                    <h1 class="text-3xl font-bold tracking-tight sm:text-4xl lg:text-5xl">
                        GEO Industry Benchmarks
                    </h1>
                    <p class="mt-4 max-w-3xl text-lg text-muted-foreground sm:text-xl leading-relaxed">
                        How does your content compare? These benchmarks are calculated from real GEO scans and AI citation checks — updated continuously as more data flows in. No other GEO tool has this.
                    </p>
                </div>
            </section>

            <!-- Live Stats -->
            <section class="py-12 sm:py-16">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <Card>
                            <CardContent class="p-6 text-center">
                                <BarChart3 class="mx-auto mb-2 h-6 w-6 text-primary" aria-hidden="true" />
                                <div class="text-3xl font-bold text-primary">{{ stats.total_scans.toLocaleString() }}</div>
                                <div class="mt-1 text-sm text-muted-foreground">GEO Scans Completed</div>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardContent class="p-6 text-center">
                                <Zap class="mx-auto mb-2 h-6 w-6 text-primary" aria-hidden="true" />
                                <div class="text-3xl font-bold text-primary">{{ stats.total_citations.toLocaleString() }}</div>
                                <div class="mt-1 text-sm text-muted-foreground">Citation Checks Run</div>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardContent class="p-6 text-center">
                                <Globe class="mx-auto mb-2 h-6 w-6 text-primary" aria-hidden="true" />
                                <div class="text-3xl font-bold text-primary">{{ stats.unique_domains.toLocaleString() }}</div>
                                <div class="mt-1 text-sm text-muted-foreground">Unique Domains Analyzed</div>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardContent class="p-6 text-center">
                                <TrendingUp class="mx-auto mb-2 h-6 w-6 text-primary" aria-hidden="true" />
                                <div class="text-3xl font-bold text-primary">{{ stats.total_correlations }}</div>
                                <div class="mt-1 text-sm text-muted-foreground">Score-Citation Correlations</div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </section>

            <Separator />

            <!-- Industry Benchmarks Table -->
            <section v-if="stats.benchmarks.length > 0" class="py-12 sm:py-16">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <h2 class="text-2xl font-bold tracking-tight sm:text-3xl mb-2">Benchmarks by industry</h2>
                    <p class="text-muted-foreground mb-8 leading-relaxed">
                        Average GEO scores and AI citation rates across {{ stats.industries_count }} industries. Use these to understand how your content compares within your vertical.
                    </p>
                    <div class="overflow-x-auto rounded-lg border">
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr class="border-b bg-muted/50">
                                    <th class="px-4 py-3 font-semibold">Industry</th>
                                    <th class="px-4 py-3 font-semibold text-center">Sites</th>
                                    <th class="px-4 py-3 font-semibold text-center">Avg GEO Score</th>
                                    <th class="px-4 py-3 font-semibold text-center">Avg Citation Rate</th>
                                    <th class="px-4 py-3 font-semibold text-center">25th %ile</th>
                                    <th class="px-4 py-3 font-semibold text-center">Median</th>
                                    <th class="px-4 py-3 font-semibold text-center">75th %ile</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="b in stats.benchmarks" :key="b.industry" class="border-b last:border-b-0 transition-colors hover:bg-muted/30">
                                    <td class="px-4 py-3 font-medium">{{ b.industry }}</td>
                                    <td class="px-4 py-3 text-center text-muted-foreground">{{ b.sample_size }}</td>
                                    <td class="px-4 py-3 text-center font-semibold">{{ b.avg_score }}</td>
                                    <td class="px-4 py-3 text-center font-semibold"
                                        :class="Number(b.avg_citation_rate) >= 60 ? 'text-green-600 dark:text-green-400' :
                                                Number(b.avg_citation_rate) >= 30 ? 'text-yellow-600 dark:text-yellow-400' : 'text-red-600 dark:text-red-400'">
                                        {{ b.avg_citation_rate }}%
                                    </td>
                                    <td class="px-4 py-3 text-center text-muted-foreground">{{ b.p25 ?? '—' }}</td>
                                    <td class="px-4 py-3 text-center text-muted-foreground">{{ b.p50 ?? '—' }}</td>
                                    <td class="px-4 py-3 text-center text-muted-foreground">{{ b.p75 ?? '—' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p class="mt-4 text-sm text-muted-foreground italic">
                        Data sourced from GeoSource.ai scans and citation checks. Benchmarks refresh as new data is collected.
                    </p>
                </div>
            </section>

            <!-- Empty state if no benchmarks yet -->
            <section v-else class="py-12 sm:py-16">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 text-center">
                    <Database class="mx-auto mb-4 h-12 w-12 text-muted-foreground" aria-hidden="true" />
                    <h2 class="text-2xl font-bold tracking-tight sm:text-3xl mb-2">Building benchmarks</h2>
                    <p class="text-muted-foreground max-w-lg mx-auto">
                        Industry benchmarks are generated from real scan and citation data. As more sites are scanned and citations tracked, benchmarks become more accurate. Run a scan to contribute data.
                    </p>
                </div>
            </section>

            <Separator />

            <!-- How it works -->
            <section class="py-12 sm:py-16">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <h2 class="text-2xl font-bold tracking-tight sm:text-3xl mb-6">How benchmarks are calculated</h2>
                    <div class="grid gap-6 sm:grid-cols-3">
                        <Card>
                            <CardContent class="p-6">
                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-primary/10 text-sm font-bold text-primary mb-3">1</div>
                                <h3 class="font-semibold mb-2">Scan data collected</h3>
                                <p class="text-sm text-muted-foreground">Every GEO scan contributes pillar scores, content type, and overall score to our dataset.</p>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardContent class="p-6">
                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-primary/10 text-sm font-bold text-primary mb-3">2</div>
                                <h3 class="font-semibold mb-2">Citations correlated</h3>
                                <p class="text-sm text-muted-foreground">When a domain has both scan data and citation checks, we link the two to measure what scores predict actual citations.</p>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardContent class="p-6">
                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-primary/10 text-sm font-bold text-primary mb-3">3</div>
                                <h3 class="font-semibold mb-2">Benchmarks refresh</h3>
                                <p class="text-sm text-muted-foreground">Industry averages, percentiles, and citation rates update as new data flows in. More data = more accurate benchmarks.</p>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </section>

            <Separator />

            <!-- Research link -->
            <section class="py-12 sm:py-16">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 text-center">
                    <h2 class="text-2xl font-bold tracking-tight sm:text-3xl mb-4">Read the research behind these benchmarks</h2>
                    <p class="text-muted-foreground max-w-xl mx-auto mb-6">
                        These benchmarks are seeded from our two-phase study of 60 websites across 17 industries. Read the full methodology and findings.
                    </p>
                    <Link href="/blog/geo-citation-study">
                        <Button variant="outline" size="lg" class="gap-2">
                            Read the study
                            <ArrowRight class="h-4 w-4" aria-hidden="true" />
                        </Button>
                    </Link>
                </div>
            </section>

            <Separator />

            <!-- CTA -->
            <section class="py-16 sm:py-20">
                <div class="mx-auto max-w-4xl px-4 text-center sm:px-6 lg:px-8">
                    <h2 class="text-2xl font-bold tracking-tight sm:text-3xl">
                        See where you stand
                    </h2>
                    <p class="mx-auto mt-4 max-w-xl text-muted-foreground">
                        Run a free GEO scan and compare your score against your industry benchmark.
                    </p>
                    <div class="mt-8">
                        <Link :href="register().url">
                            <Button size="lg" class="gap-2">
                                Run a free scan
                                <ArrowRight class="h-4 w-4" aria-hidden="true" />
                            </Button>
                        </Link>
                    </div>
                </div>
            </section>
        </main>

        <ResourceFooter />
    </div>
</template>
