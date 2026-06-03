<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import SkipNav from '@/components/resources/SkipNav.vue';
import ResourceHeader from '@/components/resources/ResourceHeader.vue';
import ResourceFooter from '@/components/resources/ResourceFooter.vue';
import ResourceBreadcrumb from '@/components/resources/ResourceBreadcrumb.vue';
import { Bar, Doughnut } from 'vue-chartjs';
import {
    Chart as ChartJS,
    LinearScale,
    BarElement,
    ArcElement,
    Tooltip,
    Legend,
    Title,
    CategoryScale,
} from 'chart.js';
import { BarChart3, Calendar, BookOpen, Building2, AlertTriangle, TrendingDown, TrendingUp, Search } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

ChartJS.register(LinearScale, BarElement, ArcElement, Tooltip, Legend, Title, CategoryScale);

interface Entry {
    domain: string;
    industry: string;
    content_type: string;
    query: string;
    citation_rate: number;
    platforms_cited: string[];
}

interface CellSummary {
    industry: string;
    content_type: string;
    n: number;
    avg_citation_rate: number;
}

interface AggregateRow {
    industry?: string;
    content_type?: string;
    n: number;
    avg_citation_rate: number;
}

interface Distribution {
    all_cited: number;
    none_cited: number;
    partial: number;
    total: number;
}

interface Headline {
    n: number;
    industries: number;
    content_types: number;
    avg_citation_rate?: number;
}

interface V1Summary {
    byContentType: AggregateRow[];
    n: number;
}

const props = defineProps<{
    entries: Entry[];
    cellSummaries: CellSummary[];
    byContentType: AggregateRow[];
    byIndustryPublic: AggregateRow[];
    citationDistribution: Distribution;
    headline: Headline;
    v1Summary: V1Summary;
}>();

const canonicalUrl = 'https://geosource.ai/blog/eeat-content-type-study';

const breadcrumbItems = [
    { label: 'Resources', href: '/resources' },
    { label: 'E-E-A-T & Content Type Study' },
];

const dataPending = computed(() => props.headline.n === 0);

const cellLabel = (industry: string, contentType: string): string => {
    const ind = industry.charAt(0).toUpperCase() + industry.slice(1);
    const ct = contentType === 'educational' ? 'Educational' : 'Authority';
    return `${ind} × ${ct}`;
};

const findCell = (industry: string, contentType: string): CellSummary | undefined =>
    props.cellSummaries.find((c) => c.industry === industry && c.content_type === contentType);

const cellOrder: Array<{ industry: string; content_type: string }> = [
    { industry: 'healthcare', content_type: 'authority' },
    { industry: 'healthcare', content_type: 'educational' },
    { industry: 'saas', content_type: 'authority' },
    { industry: 'saas', content_type: 'educational' },
];

const fmtR = (r: number | null | undefined): string => {
    if (r === null || r === undefined || Number.isNaN(r)) return '—';
    return (r >= 0 ? '+' : '') + r.toFixed(2);
};

const fmtPct = (n: number | null | undefined): string =>
    n === null || n === undefined ? '—' : `${n}%`;

const isDark = ref(false);
onMounted(() => {
    isDark.value = document.documentElement.classList.contains('dark');
    const observer = new MutationObserver(() => {
        isDark.value = document.documentElement.classList.contains('dark');
    });
    observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
});

const chartColors = computed(() => ({
    text: isDark.value ? '#b0bec5' : '#475569',
    grid: isDark.value ? 'rgba(148, 163, 184, 0.1)' : 'rgba(100, 116, 139, 0.08)',
    primary: '#8b5cf6',
    amber: isDark.value ? '#fbbf24' : '#f59e0b',
    green: isDark.value ? '#4ade80' : '#22c55e',
    blue: isDark.value ? '#60a5fa' : '#3b82f6',
    red: isDark.value ? '#f87171' : '#ef4444',
    tooltipBg: isDark.value ? '#1e293b' : '#ffffff',
    tooltipText: isDark.value ? '#e2e8f0' : '#1e293b',
    tooltipBorder: isDark.value ? '#334155' : '#e2e8f0',
}));

const cellColor = (industry: string, contentType: string): string => {
    if (industry === 'healthcare' && contentType === 'educational') return '#22c55e';
    if (industry === 'healthcare' && contentType === 'authority') return '#3b82f6';
    if (industry === 'saas' && contentType === 'educational') return '#f59e0b';
    return '#a855f7';
};

const contentTypeBarData = computed(() => ({
    labels: props.byContentType.map((r) => (r.content_type === 'educational' ? 'Educational pages' : 'Authority pages')),
    datasets: [
        {
            label: 'Avg citation rate (%)',
            data: props.byContentType.map((r) => r.avg_citation_rate),
            backgroundColor: props.byContentType.map((r) => (r.content_type === 'educational' ? '#22c55ecc' : '#3b82f6cc')),
            borderRadius: 6,
        },
    ],
}));

const contentTypeBarOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: true,
    aspectRatio: 2,
    plugins: { legend: { display: false } },
    scales: {
        y: {
            beginAtZero: true,
            max: 100,
            ticks: { color: chartColors.value.text, callback: (v: any) => `${v}%` },
            grid: { color: chartColors.value.grid },
        },
        x: {
            ticks: { color: chartColors.value.text },
            grid: { display: false },
        },
    },
}));

// v1 → v2 methodology shift comparison (authority and educational cite rates)
const methodologyShiftData = computed(() => {
    const v1Auth = props.v1Summary.byContentType.find((r) => r.content_type === 'authority')?.avg_citation_rate ?? 0;
    const v2Auth = props.byContentType.find((r) => r.content_type === 'authority')?.avg_citation_rate ?? 0;
    const v1Edu = props.v1Summary.byContentType.find((r) => r.content_type === 'educational')?.avg_citation_rate ?? 0;
    const v2Edu = props.byContentType.find((r) => r.content_type === 'educational')?.avg_citation_rate ?? 0;
    return {
        labels: ['Authority pages', 'Educational pages'],
        datasets: [
            {
                label: 'First run (brand-named queries)',
                data: [v1Auth, v1Edu],
                backgroundColor: chartColors.value.amber + 'cc',
                borderRadius: 4,
            },
            {
                label: 'Redesign (concept-only queries)',
                data: [v2Auth, v2Edu],
                backgroundColor: chartColors.value.primary + 'cc',
                borderRadius: 4,
            },
        ],
    };
});

const methodologyShiftOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: true,
    aspectRatio: 1.8,
    plugins: { legend: { position: 'top' as const, labels: { color: chartColors.value.text } } },
    scales: {
        y: { beginAtZero: true, max: 100, ticks: { color: chartColors.value.text, callback: (v: any) => `${v}%` }, grid: { color: chartColors.value.grid } },
        x: { ticks: { color: chartColors.value.text }, grid: { display: false } },
    },
}));

// Citation distribution donut
const distributionData = computed(() => ({
    labels: ['Cited by all 3 platforms', 'Not cited by any', 'Partial (1-2 of 3)'],
    datasets: [
        {
            data: [props.citationDistribution.all_cited, props.citationDistribution.none_cited, props.citationDistribution.partial],
            backgroundColor: [chartColors.value.green || '#22c55e', '#cbd5e1', '#fbbf24'],
            borderWidth: 0,
            hoverOffset: 8,
        },
    ],
}));

const distributionOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: true,
    plugins: { legend: { position: 'bottom' as const, labels: { color: chartColors.value.text, padding: 12, usePointStyle: true, pointStyle: 'circle' } } },
}));

// 2x2 heatmap cells with intensity by citation rate
const heatmapColor = (rate: number): string => {
    if (rate >= 75) return 'bg-green-500/30 dark:bg-green-500/40';
    if (rate >= 50) return 'bg-blue-500/25 dark:bg-blue-500/35';
    if (rate >= 25) return 'bg-amber-500/25 dark:bg-amber-500/35';
    if (rate > 0) return 'bg-red-400/25 dark:bg-red-500/30';
    return 'bg-muted';
};

const heatmapTextColor = (rate: number): string => {
    if (rate === 0) return 'text-muted-foreground';
    return 'text-foreground';
};

// Brand search filter for the URL table
const eeatSearchTerm = ref('');
const filteredEntries = computed(() => {
    const term = eeatSearchTerm.value.toLowerCase().trim();
    if (!term) return props.entries;
    return props.entries.filter((e) =>
        e.domain.toLowerCase().includes(term)
        || e.query.toLowerCase().includes(term)
        || e.content_type.toLowerCase().includes(term)
        || e.industry.toLowerCase().includes(term)
    );
});
</script>

<template>
    <Head>
        <title>E-E-A-T & Content Type: A Follow-Up Study | GeoSource.ai</title>
        <meta
            name="description"
            content="Does the negative E-E-A-T correlation from our prior homepage research survive when we control for content type? A 2×2 follow-up across healthcare and SaaS."
        />
        <link rel="canonical" :href="canonicalUrl" />
    </Head>

    <div class="min-h-screen bg-background text-foreground">
        <SkipNav />
        <ResourceHeader />

        <main id="main-content">
            <ResourceBreadcrumb :items="breadcrumbItems" />

            <!-- Hero -->
            <section class="border-b bg-gradient-to-b from-primary/5 to-background py-16 sm:py-24">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <Badge variant="secondary" class="mb-4">
                        <BarChart3 class="mr-1.5 h-3 w-3" aria-hidden="true" />
                        Follow-Up Research
                    </Badge>
                    <h1 class="text-3xl font-bold tracking-tight sm:text-4xl lg:text-5xl">
                        Does E-E-A-T still hurt AI citations when you control for content type?
                    </h1>
                    <p class="mt-4 max-w-3xl text-lg text-muted-foreground sm:text-xl leading-relaxed">
                        Our earlier research turned up a surprising result: high-E-E-A-T pages got cited <em>less</em> by AI assistants, not more — a finding that runs counter to Google's own framework. Every URL in that earlier study was a homepage, though, so we wanted to rule out the possibility that the result was really about homepages rather than E-E-A-T. We ran a follow-up with 40 pages split evenly across healthcare vs SaaS and across educational vs authority pages. The first run had a flaw in how we built the queries; we fixed it and reran. <strong class="text-foreground">The negative E-E-A-T pattern still showed up</strong> — it isn't a homepage artifact.
                    </p>
                    <div class="mt-4 flex items-center gap-4 text-sm text-muted-foreground">
                        <span class="flex items-center gap-1.5">
                            <Calendar class="h-3.5 w-3.5" aria-hidden="true" />
                            Published June 2, 2026
                        </span>
                        <span>&middot;</span>
                        <span>GeoSource.ai Research</span>
                    </div>
                </div>
            </section>

            <section v-if="dataPending" class="py-16">
                <div class="mx-auto max-w-2xl px-4 text-center">
                    <Card class="border-amber-300 bg-amber-50/40 dark:border-amber-700 dark:bg-amber-950/20">
                        <CardContent class="p-6">
                            <p class="text-base">Study results are still being collected. Refresh once the pipeline finishes.</p>
                        </CardContent>
                    </Card>
                </div>
            </section>

            <template v-else>
                <!-- Key Metrics -->
                <section class="py-12 sm:py-16">
                    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            <Card>
                                <CardContent class="p-5 text-center">
                                    <div class="text-3xl font-bold text-primary">{{ headline.n }}</div>
                                    <div class="mt-1 text-xs text-muted-foreground">URLs scanned</div>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardContent class="p-5 text-center">
                                    <div class="text-3xl font-bold text-primary">{{ headline.industries }} × {{ headline.content_types }}</div>
                                    <div class="mt-1 text-xs text-muted-foreground">Factorial design</div>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardContent class="p-5 text-center">
                                    <div class="text-3xl font-bold text-primary">{{ headline.n * 3 }}</div>
                                    <div class="mt-1 text-xs text-muted-foreground">Citation checks ({{ headline.n }} × 3 platforms)</div>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardContent class="p-5 text-center">
                                    <div class="text-3xl font-bold text-primary">{{ headline.avg_citation_rate ?? '—' }}%</div>
                                    <div class="mt-1 text-xs text-muted-foreground">Avg citation rate</div>
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                </section>

                <Separator />

                <!-- TL;DR -->
                <section class="py-12 sm:py-16">
                    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                        <h2 class="text-2xl font-bold tracking-tight sm:text-3xl mb-6">Key findings</h2>
                        <Card class="border-l-4 border-l-primary">
                            <CardContent class="p-6 space-y-3 text-muted-foreground leading-relaxed">
                                <p>
                                    <strong class="text-foreground">The negative E-E-A-T finding replicates.</strong> Across all {{ headline.n }} URLs, higher E-E-A-T pages got cited <em>less</em>, not more — the same direction as the original homepage study. The pattern survives the move off homepages.
                                </p>
                                <p>
                                    <strong class="text-foreground">Authority and educational pages got cited at similar rates</strong> ({{ byContentType.find((r) => r.content_type === 'authority')?.avg_citation_rate ?? '—' }}% vs {{ byContentType.find((r) => r.content_type === 'educational')?.avg_citation_rate ?? '—' }}%). Our first attempt at this study showed a much bigger gap between them, but we found a flaw in how we built the queries. After we fixed it, the gap mostly closed — meaning the apparent advantage of authority pages was really an artifact of how we were asking.
                                </p>
                                <p>
                                    <strong class="text-foreground">SaaS educational pages showed the cleanest version of the pattern.</strong> Within that cell, the highest-E-E-A-T definition pages got cited <em>less</em> than the simpler, more direct ones.
                                </p>
                                <p>
                                    <strong class="text-foreground">AI assistants agreed with each other.</strong> Of {{ citationDistribution.total }} URLs, {{ citationDistribution.all_cited }} were cited by all three platforms and {{ citationDistribution.none_cited }} by none. Only {{ citationDistribution.partial }} were split decisions. When a query pulls a sourced citation, the platforms tend to land on the same source.
                                </p>
                            </CardContent>
                        </Card>
                    </div>
                </section>

                <!-- Why this study has two versions -->
                <section class="pb-12 sm:pb-16">
                    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                        <h2 class="text-2xl font-bold tracking-tight sm:text-3xl mb-6">Why this study has two versions</h2>
                        <Card class="border-l-4 border-l-amber-500 bg-amber-50/30 dark:bg-amber-950/10">
                            <CardContent class="p-6 space-y-3 text-sm leading-relaxed">
                                <div class="flex items-center gap-2 font-semibold text-foreground text-base">
                                    <AlertTriangle class="h-4 w-4 text-amber-600" aria-hidden="true" />
                                    A methodology lesson worth sharing
                                </div>
                                <p>
                                    Our first run of this study had an asymmetry in the queries. Authority queries named the brand whose page we were checking — <em>"is Mayo Clinic trustworthy", "who founded Notion", "who runs the NHS website"</em>. Educational queries didn't — <em>"what causes migraines", "what is CRM software"</em>. Brand-named queries strongly prime AI assistants to look up that specific brand's site, so the authority-vs-educational gap was inflated by query design, not by the page properties we wanted to test.
                                </p>
                                <p>
                                    We redesigned. Every query in the second run is concept-based and doesn't name a brand — even for authority pages. Healthcare authority queries became things like <em>"how should you choose a hospital for serious illness."</em> SaaS authority queries became things like <em>"what are the largest payment processing companies."</em> We also expanded the sample from 24 to 40 URLs.
                                </p>
                                <p>
                                    <strong class="text-foreground">What changed:</strong> the authority-page citation rate dropped from 91.7% to {{ byContentType.find((r) => r.content_type === 'authority')?.avg_citation_rate ?? '—' }}%. About half the apparent advantage was query priming. Educational rates barely moved ({{ byContentType.find((r) => r.content_type === 'educational')?.avg_citation_rate ?? '—' }}% vs 50%). Mayo Clinic's about page — cited by all 3 platforms in the first run — was cited by none in the second.
                                </p>
                                <p>
                                    <strong class="text-foreground">What stayed the same:</strong> the negative E-E-A-T effect. Both runs of this study and the original homepage study all pointed the same direction. Three different designs, same finding.
                                </p>
                                <p class="italic text-muted-foreground">
                                    The numbers on this page are from the redesigned run. We're keeping the first-run numbers in the discussion as an honest record of how the methodology evolved.
                                </p>
                            </CardContent>
                        </Card>
                    </div>
                </section>

                <Separator />

                <!-- Methodology shift -->
                <section v-if="v1Summary.n > 0" class="py-12 sm:py-16">
                    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                        <h2 class="text-2xl font-bold tracking-tight sm:text-3xl mb-2">What the methodology fix changed</h2>
                        <p class="text-muted-foreground mb-6 max-w-3xl">
                            Side-by-side: how citation rates per content type moved between the first run (brand-named authority queries) and the redesigned run (concept-only queries). Authority pages dropped sharply once we stopped naming the brand in the query. Educational pages barely moved because their queries were already concept-based.
                        </p>
                        <Card>
                            <CardContent class="p-6">
                                <Bar :data="methodologyShiftData" :options="methodologyShiftOptions" />
                                <p class="mt-4 text-xs text-muted-foreground">
                                    The size of the authority-page drop is the size of the query-design bug. The educational-page stability is what tells us the bug wasn't elsewhere.
                                </p>
                            </CardContent>
                        </Card>
                    </div>
                </section>

                <Separator v-if="v1Summary.n > 0" />

                <!-- Citation distribution donut -->
                <section class="py-12 sm:py-16">
                    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                        <h2 class="text-2xl font-bold tracking-tight sm:text-3xl mb-2">How citations distribute across platforms</h2>
                        <p class="text-muted-foreground mb-6 max-w-3xl">
                            Every URL was checked across ChatGPT, Perplexity, and Claude. Most landed either at "cited by all three" or "cited by none" — very few split decisions. The platforms tend to agree on what to cite.
                        </p>
                        <div class="grid gap-6 md:grid-cols-2 items-center">
                            <Card>
                                <CardContent class="p-6">
                                    <Doughnut :data="distributionData" :options="distributionOptions" />
                                </CardContent>
                            </Card>
                            <Card>
                                <CardContent class="p-6 space-y-3 text-sm text-muted-foreground leading-relaxed">
                                    <p>
                                        <strong class="text-foreground">All three platforms agreed</strong> on whether to cite a given URL {{ Math.round(((citationDistribution.all_cited + citationDistribution.none_cited) / citationDistribution.total) * 100) }}% of the time.
                                    </p>
                                    <p>
                                        Practical takeaway for content owners: if a page isn't being cited by one platform, it's usually not being cited by the others either. Per-platform optimization rarely pays off; per-query optimization does.
                                    </p>
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                </section>

                <Separator />

                <!-- Headline finding: content type bar chart -->
                <section class="py-12 sm:py-16">
                    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                        <h2 class="text-2xl font-bold tracking-tight sm:text-3xl mb-2">Citation rate by content type</h2>
                        <p class="text-muted-foreground mb-6 max-w-3xl">
                            With concept-only queries, authority pages and educational pages get cited at similar rates. The big gap our first run showed was a query-design artifact, not a real content-type effect.
                        </p>
                        <Card>
                            <CardContent class="p-6">
                                <Bar :data="contentTypeBarData" :options="contentTypeBarOptions" />
                                <div class="mt-4 grid gap-3 sm:grid-cols-2 text-sm">
                                    <div v-for="row in byContentType" :key="row.content_type" class="rounded-md bg-muted/30 p-3">
                                        <div class="font-medium capitalize">{{ row.content_type }} pages (n={{ row.n }})</div>
                                        <div class="mt-1 text-muted-foreground">
                                            Avg citation: <span class="font-mono text-foreground">{{ row.avg_citation_rate }}%</span>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </section>

                <Separator />

                <!-- 2x2 cell results -->
                <section class="py-12 sm:py-16">
                    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                        <h2 class="text-2xl font-bold tracking-tight sm:text-3xl mb-2">The 2×2 cell results</h2>
                        <p class="text-muted-foreground mb-8 max-w-3xl">
                            Each cell holds industry and content type constant. With only 6 URLs per cell the within-cell numbers are exploratory; we look at direction across cells rather than any single magnitude.
                        </p>

                        <!-- 2x2 heatmap matrix -->
                        <div class="mb-6 overflow-x-auto rounded-lg border">
                            <table class="w-full text-sm">
                                <thead class="bg-muted/40">
                                    <tr>
                                        <th class="px-3 py-3 text-left font-medium"></th>
                                        <th class="px-3 py-3 text-center font-medium">
                                            <BookOpen class="inline-block h-3.5 w-3.5 mr-1 text-muted-foreground" aria-hidden="true" />
                                            Educational
                                        </th>
                                        <th class="px-3 py-3 text-center font-medium">
                                            <Building2 class="inline-block h-3.5 w-3.5 mr-1 text-muted-foreground" aria-hidden="true" />
                                            Authority
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="industry in ['healthcare', 'saas']" :key="industry" class="border-t">
                                        <td class="px-3 py-4 font-medium capitalize">{{ industry }}</td>
                                        <td
                                            v-for="content_type in ['educational', 'authority']"
                                            :key="content_type"
                                            class="px-3 py-4 text-center"
                                            :class="heatmapColor(findCell(industry, content_type)?.avg_citation_rate ?? 0)"
                                        >
                                            <div class="text-2xl font-bold font-mono" :class="heatmapTextColor(findCell(industry, content_type)?.avg_citation_rate ?? 0)">
                                                {{ findCell(industry, content_type)?.avg_citation_rate ?? '—' }}%
                                            </div>
                                            <div class="mt-1 text-xs text-muted-foreground">
                                                n={{ findCell(industry, content_type)?.n ?? 0 }}
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <p class="mb-6 text-xs text-muted-foreground">
                            Cell shading scales with citation rate — darker means higher. Cells are independent samples; this is qualitative direction across cells, not a statistical test.
                        </p>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <Card
                                v-for="cell in cellOrder"
                                :key="`${cell.industry}-${cell.content_type}`"
                                class="border-l-4"
                                :style="{ borderLeftColor: cellColor(cell.industry, cell.content_type) }"
                            >
                                <CardContent class="p-6">
                                    <div class="flex items-center gap-2 mb-3">
                                        <component :is="cell.content_type === 'educational' ? BookOpen : Building2" class="h-4 w-4 text-muted-foreground" />
                                        <h3 class="font-semibold">{{ cellLabel(cell.industry, cell.content_type) }}</h3>
                                    </div>
                                    <div class="space-y-2 text-sm">
                                        <div class="flex justify-between">
                                            <span class="text-muted-foreground">N</span>
                                            <span class="font-mono">{{ findCell(cell.industry, cell.content_type)?.n ?? '—' }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-muted-foreground">Avg citation rate</span>
                                            <span class="font-mono">{{ findCell(cell.industry, cell.content_type)?.avg_citation_rate ?? '—' }}%</span>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                </section>

                <Separator />

                <!-- The unexpected finding -->
                <section class="py-12 sm:py-16">
                    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                        <h2 class="text-2xl font-bold tracking-tight sm:text-3xl mb-6">What this actually tells us</h2>
                        <div class="space-y-6">
                            <Card class="border-l-4 border-l-green-500">
                                <CardContent class="p-6">
                                    <div class="flex items-center gap-2 mb-3">
                                        <TrendingUp class="h-4 w-4 text-green-600" aria-hidden="true" />
                                        <h3 class="font-semibold">Confirmed: the negative E-E-A-T effect isn't a homepage artifact</h3>
                                    </div>
                                    <p class="text-muted-foreground leading-relaxed">
                                        On this controlled set, E-E-A-T and citation rate were again negatively related — same direction as the original homepage study. The effect survived the move from homepages to specific content pages with content-matched queries. Whatever E-E-A-T captures, more of it does <em>not</em> reliably predict more citations.
                                    </p>
                                </CardContent>
                            </Card>

                            <Card class="border-l-4 border-l-blue-500">
                                <CardContent class="p-6">
                                    <div class="flex items-center gap-2 mb-3">
                                        <Search class="h-4 w-4 text-blue-600" aria-hidden="true" />
                                        <h3 class="font-semibold">Naming the brand in the query is a huge lever</h3>
                                    </div>
                                    <p class="text-muted-foreground leading-relaxed">
                                        Asking <em>"who founded Notion"</em> reliably pulls a citation back to Notion's site. Asking <em>"what are the most popular productivity software companies"</em> often doesn't — even though Notion is one of them. Once we stopped naming brands in our authority queries, the citation rate for authority pages dropped sharply. The takeaway: a lot of whether you get cited depends on whether the queries your audience asks contain your brand. If they don't, your about page is unlikely to surface.
                                    </p>
                                </CardContent>
                            </Card>

                            <Card class="border-l-4 border-l-purple-500">
                                <CardContent class="p-6">
                                    <div class="flex items-center gap-2 mb-3">
                                        <TrendingDown class="h-4 w-4 text-purple-600" aria-hidden="true" />
                                        <h3 class="font-semibold">SaaS educational pages: the cleanest replication</h3>
                                    </div>
                                    <p class="text-muted-foreground leading-relaxed">
                                        Within the SaaS educational cell, the highest-E-E-A-T definition pages got <em>zero</em> citations on their concept queries. Shorter, sparser definition pages from the same category got cited consistently. The pattern matched what we saw in the first run of this study and in the original homepage research.
                                    </p>
                                </CardContent>
                            </Card>

                            <Card class="border-l-4 border-l-amber-500">
                                <CardContent class="p-6">
                                    <div class="flex items-center gap-2 mb-3">
                                        <Building2 class="h-4 w-4 text-amber-600" aria-hidden="true" />
                                        <h3 class="font-semibold">Authority pages get cited when they're the right answer</h3>
                                    </div>
                                    <p class="text-muted-foreground leading-relaxed">
                                        Which authority pages got cited was uneven. CDC, FDA, and MedlinePlus about pages all got cited for queries like <em>"what is the role of national public health agencies"</em> because those pages actually answer that. Mayo Clinic's, Cleveland Clinic's, and Harvard Health's about pages weren't cited for <em>"how should you choose a hospital for serious illness"</em> because that query pulls editorial advice, not institutional self-descriptions. The lesson: an authority page gets cited when it happens to be the natural answer to the query — and that depends more on the query than the page.
                                    </p>
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                </section>

                <Separator />

                <!-- What might the E-E-A-T pillar actually be measuring -->
                <section class="py-12 sm:py-16">
                    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                        <h2 class="text-2xl font-bold tracking-tight sm:text-3xl mb-6">Why might higher E-E-A-T scores produce fewer citations?</h2>
                        <Card>
                            <CardContent class="p-6 space-y-4 text-muted-foreground leading-relaxed">
                                <p>
                                    A plausible mechanical explanation: visible expertise signaling on a page — author bylines with credentials, medical-reviewer attribution, "expert reviewed by" labels, professional-bio sidebars — appears most prominently on long-form, expert-reviewed content. Those same pages tend to be <em>dense</em>, with multiple sections, FAQs, and visual breakouts.
                                </p>
                                <p>
                                    AI assistants answering <em>"what is high blood pressure"</em> often generate a confident synthesis from training without surfacing any citation at all. The well-credentialed long-form page never gets attributed. A sparser page with a clean definition near the top fits more naturally into the AI's answer structure — and gets pulled in instead.
                                </p>
                                <p>
                                    That fits a hypothesis we've heard articulated elsewhere: AI can verify parseable answers but can't verify credentials, so it leans on what it can directly check.
                                </p>
                                <p>
                                    <strong class="text-foreground">The honest takeaway:</strong> the E-E-A-T pillar isn't a reliable citation predictor. Three independent studies (the original homepage research, the first run of this study, and the redesigned run) all point the same direction. Whatever those signals do for Google's framework, they aren't what AI citation behaviour rewards.
                                </p>
                            </CardContent>
                        </Card>
                    </div>
                </section>

                <Separator />

                <!-- Per-URL table with search -->
                <section class="py-12 sm:py-16">
                    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                        <h2 class="text-2xl font-bold tracking-tight sm:text-3xl mb-2">All URLs and outcomes</h2>
                        <p class="text-muted-foreground mb-4 text-sm">
                            Search by domain, industry, content type, or query text.
                        </p>
                        <div class="mb-4 flex items-center gap-2">
                            <div class="relative flex-1">
                                <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" aria-hidden="true" />
                                <input
                                    v-model="eeatSearchTerm"
                                    type="search"
                                    placeholder="Filter URLs…"
                                    class="w-full rounded-md border border-input bg-background pl-9 pr-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                                />
                            </div>
                            <span class="text-xs text-muted-foreground">
                                {{ filteredEntries.length }} of {{ entries.length }}
                            </span>
                        </div>
                        <div class="overflow-x-auto rounded-md border">
                            <table class="w-full text-sm">
                                <thead class="bg-muted/40 text-left">
                                    <tr>
                                        <th class="px-3 py-2 font-medium">Domain</th>
                                        <th class="px-3 py-2 font-medium">Industry</th>
                                        <th class="px-3 py-2 font-medium">Content type</th>
                                        <th class="px-3 py-2 font-medium">Query</th>
                                        <th class="px-3 py-2 font-medium text-right">Cite %</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(e, idx) in filteredEntries" :key="`${e.domain}-${idx}`" class="border-t">
                                        <td class="px-3 py-2 font-mono text-xs">{{ e.domain }}</td>
                                        <td class="px-3 py-2 capitalize">{{ e.industry }}</td>
                                        <td class="px-3 py-2 capitalize">{{ e.content_type }}</td>
                                        <td class="px-3 py-2 text-muted-foreground italic">{{ e.query }}</td>
                                        <td class="px-3 py-2 text-right font-mono">{{ e.citation_rate }}%</td>
                                    </tr>
                                    <tr v-if="filteredEntries.length === 0">
                                        <td colspan="5" class="px-3 py-6 text-center text-muted-foreground">
                                            No URLs match this filter.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <Separator />

                <!-- Methodology -->
                <section class="py-12 sm:py-16">
                    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                        <h2 class="text-2xl font-bold tracking-tight sm:text-3xl mb-6">Methodology</h2>
                        <Card>
                            <CardContent class="p-6 space-y-4 text-muted-foreground leading-relaxed">
                                <p>
                                    We selected 40 URLs in a balanced 2×2 design crossing two factors. <strong class="text-foreground">Industry:</strong> healthcare (YMYL territory where Google's framework says credentials matter most) vs SaaS (technical/commercial, where author authority should matter less). <strong class="text-foreground">Content type:</strong> educational pages whose purpose is to answer a topic question vs authority pages whose purpose is to convey institutional credibility. Ten pages per cell.
                                </p>
                                <p>
                                    Each URL was scanned with the same GeoSource scoring engine that produced the original study's pillar scores. The only things varying across the three studies are the URL set and the query phrasing.
                                </p>
                                <p>
                                    <strong class="text-foreground">Queries (redesigned).</strong> Every query is concept-based and never names a specific brand. Educational pages were paired with topical concept queries (<em>"what causes migraines", "what is CRM software"</em>). Authority pages were paired with concept queries about credibility or category (<em>"how should you choose a hospital for serious illness", "what are the largest payment processing companies"</em>). Whether an authority page surfaces under those queries is the test. The first-run design used brand-named authority queries, which inflated the authority-cell citation rate by roughly 47 percentage points (see "Why this study has two versions" above).
                                </p>
                                <p>
                                    Citations were checked across ChatGPT, Perplexity, and Claude — 120 checks total. A URL counts as cited if either the domain appears in the response text or the platform lists a URL from that domain as a source. We re-ran the analysis restricting to formal URL citations only (excluding incidental prose mentions) — the numbers were identical, so the citations are real, not just brand names floating through answers.
                                </p>
                                <p>
                                    <strong class="text-foreground">Limitations.</strong> <em>Sample size.</em> Ten URLs per cell is small — read the direction across cells rather than any single magnitude. <em>Bimodality.</em> Most URLs are either fully cited or not cited at all, which compresses what any aggregate measure can detect. <em>Authority pages tested indirectly.</em> Asking <em>"how should you choose a hospital"</em> measures whether Mayo Clinic's about page happens to be the natural answer — that depends as much on the query as the page. A future study could vary E-E-A-T signals directly on the same URL (A/B with and without author bylines) to isolate the page-content effect. <em>What "E-E-A-T" means here.</em> The score we're calling E-E-A-T is the GeoSource pillar's measure of visible credential signaling on the page — it's not Google's full E-E-A-T framework, which includes off-page signals our scanner can't see.
                                </p>
                            </CardContent>
                        </Card>
                    </div>
                </section>

                <Separator />

                <section class="py-12 sm:py-16">
                    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                        <h2 class="text-2xl font-bold tracking-tight sm:text-3xl mb-6">Related research</h2>
                        <div class="grid gap-4 sm:grid-cols-3">
                            <Card class="border-l-4 border-l-primary">
                                <CardContent class="p-5">
                                    <h3 class="font-semibold mb-2">Cross-study synthesis</h3>
                                    <p class="text-sm text-muted-foreground mb-3">Six findings that held across all of our research, with practical implications for what to optimize.</p>
                                    <Link href="/blog/what-predicts-ai-citations" class="text-sm font-medium text-primary hover:underline">Read the synthesis &rarr;</Link>
                                </CardContent>
                            </Card>
                            <Card class="border-l-4 border-l-primary">
                                <CardContent class="p-5">
                                    <h3 class="font-semibold mb-2">3-phase GEO citation study</h3>
                                    <p class="text-sm text-muted-foreground mb-3">61 sites across 17 industries. Where the negative E-E-A-T direction was first observed.</p>
                                    <Link href="/blog/geo-citation-study" class="text-sm font-medium text-primary hover:underline">Read the original study &rarr;</Link>
                                </CardContent>
                            </Card>
                            <Card class="border-l-4 border-l-primary">
                                <CardContent class="p-5">
                                    <h3 class="font-semibold mb-2">Ecommerce recommendation-survival</h3>
                                    <p class="text-sm text-muted-foreground mb-3">40 brands × 12 categories × 4 journey stages. Tests commercial-impact signals.</p>
                                    <Link href="/blog/ecommerce-recommendation-survival" class="text-sm font-medium text-primary hover:underline">Read the ecommerce study &rarr;</Link>
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                </section>
            </template>
        </main>

        <ResourceFooter />
    </div>
</template>
