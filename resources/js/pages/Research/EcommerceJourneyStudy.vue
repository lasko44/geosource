<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import SkipNav from '@/components/resources/SkipNav.vue';
import ResourceHeader from '@/components/resources/ResourceHeader.vue';
import ResourceFooter from '@/components/resources/ResourceFooter.vue';
import ResourceBreadcrumb from '@/components/resources/ResourceBreadcrumb.vue';
import { Line, Bar, Scatter, Doughnut } from 'vue-chartjs';
import {
    Chart as ChartJS,
    LinearScale,
    PointElement,
    LineElement,
    BarElement,
    ArcElement,
    Tooltip,
    Legend,
    Title,
    CategoryScale,
    Filler,
} from 'chart.js';
import { Calendar, ShoppingCart, Settings, Search } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

ChartJS.register(LinearScale, PointElement, LineElement, BarElement, ArcElement, Tooltip, Legend, Title, CategoryScale, Filler);

interface Brand {
    domain: string;
    category: string;
    geo_score: number | null;
    pillars: Record<string, number | null>;
    stage_rates: { discovery: number; filter: number; compare: number; purchase: number };
    stage_strengths: { discovery: number | null; filter: number | null; compare: number | null; purchase: number | null };
    survival_rate: number;
    strength_score: number;
    top_pick_rate: number;
    recommended_rate: number;
    survived_to_purchase: boolean;
    survived_discovery: boolean;
    survived_all_stages: boolean;
}

interface CategorySummary {
    category: string;
    n: number;
    avg_survival: number;
    avg_geo: number;
    purchase_survival_pct: number;
}

interface StageSummary {
    stage: string;
    n: number;
    avg_citation_rate: number;
    cited_count: number;
}

interface PillarRecommendation {
    available: boolean;
}

interface StageStrengthRow {
    stage: string;
    avg_strength: number;
    n_checks: number;
}

interface MentionTypeRow {
    stage: string;
    recommended: number;
    neutral: number;
    negative: number;
    absent: number;
    total: number;
}

interface StrengthByCategoryRow {
    category: string;
    n: number;
    avg_strength: number;
    avg_survival: number;
}

interface PlatformAgreement {
    unanimous_yes: number;
    unanimous_no: number;
    split: number;
    total: number;
}

interface StrengthBucket {
    range: string;
    count: number;
    min: number;
    max: number;
}

interface Headline {
    total_brands: number;
    total_entries: number;
    total_checks: number;
    categories: number;
    avg_survival_rate: number;
    avg_strength_score: number;
    survived_purchase_pct: number;
    survived_all_stages_pct: number;
    strength_analyzed: number;
}

const props = defineProps<{
    brands: Brand[];
    headline: Headline;
    categorySummaries: CategorySummary[];
    stageSummaries: StageSummary[];
    stageStrengthDecay: StageStrengthRow[];
    mentionTypeByStage: MentionTypeRow[];
    strengthByCategory: StrengthByCategoryRow[];
    platformAgreement: PlatformAgreement;
    strengthDistribution: StrengthBucket[];
    pillarRecommendation: PillarRecommendation;
}>();

const canonicalUrl = 'https://geosource.ai/blog/ecommerce-recommendation-survival';
const breadcrumbItems = [
    { label: 'Resources', href: '/resources' },
    { label: 'Ecommerce Recommendation Survival' },
];

const dataPending = computed(() => props.headline.total_brands === 0);

const PILLAR_LABELS: Record<string, string> = {
    answerability: 'Answerability',
    citations: 'Citation Quality',
    definitions: 'Definitions',
    structure: 'Structured Knowledge',
    authority: 'Topic Authority',
    machine_readable: 'Machine Readable',
    eeat: 'E-E-A-T',
    ai_accessibility: 'AI Accessibility',
    freshness: 'Freshness',
    readability: 'Readability',
    question_coverage: 'Question Coverage',
    multimedia: 'Multimedia',
};

const STAGE_LABELS: Record<string, string> = {
    discovery: '1. Discovery',
    filter: '2. Filter',
    compare: '3. Compare',
    purchase: '4. Purchase Intent',
};

const fmtR = (r: number | null | undefined): string => {
    if (r === null || r === undefined || Number.isNaN(r)) return '—';
    return (r >= 0 ? '+' : '') + r.toFixed(2);
};

const pillarLabel = (p: string): string => PILLAR_LABELS[p] || p;
const stageLabel = (s: string): string => STAGE_LABELS[s] || s;

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
    green: isDark.value ? '#4ade80' : '#22c55e',
    red: isDark.value ? '#f87171' : '#ef4444',
    blue: isDark.value ? '#60a5fa' : '#3b82f6',
    amber: isDark.value ? '#fbbf24' : '#f59e0b',
    tooltipBg: isDark.value ? '#1e293b' : '#ffffff',
    tooltipText: isDark.value ? '#e2e8f0' : '#1e293b',
    tooltipBorder: isDark.value ? '#334155' : '#e2e8f0',
}));

const stageDecayData = computed(() => ({
    labels: props.stageSummaries.map((s) => stageLabel(s.stage)),
    datasets: [
        {
            label: 'Avg citation rate (%)',
            data: props.stageSummaries.map((s) => s.avg_citation_rate),
            backgroundColor: chartColors.value.primary + 'cc',
            borderColor: chartColors.value.primary,
            borderWidth: 2,
            tension: 0.3,
            fill: true,
        },
    ],
}));

const stageDecayOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: true,
    aspectRatio: 2.2,
    plugins: {
        legend: { display: false },
        tooltip: {
            backgroundColor: chartColors.value.tooltipBg,
            titleColor: chartColors.value.tooltipText,
            bodyColor: chartColors.value.tooltipText,
            borderColor: chartColors.value.tooltipBorder,
            borderWidth: 1,
            padding: 12,
            cornerRadius: 8,
        },
    },
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

// Stage strength decay (paired with citation decay)
const stageStrengthData = computed(() => ({
    labels: props.stageStrengthDecay.map((s) => stageLabel(s.stage)),
    datasets: [
        {
            label: 'Avg recommendation strength (0-100)',
            data: props.stageStrengthDecay.map((s) => s.avg_strength),
            backgroundColor: chartColors.value.amber + 'cc',
            borderColor: chartColors.value.amber,
            borderWidth: 2,
            tension: 0.3,
            fill: true,
        },
    ],
}));

const stageStrengthOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: true,
    aspectRatio: 2.2,
    plugins: { legend: { display: false } },
    scales: {
        y: {
            beginAtZero: true,
            max: 100,
            ticks: { color: chartColors.value.text },
            grid: { color: chartColors.value.grid },
        },
        x: { ticks: { color: chartColors.value.text }, grid: { display: false } },
    },
}));

// Mention type stacked bar by stage
const mentionTypeData = computed(() => ({
    labels: props.mentionTypeByStage.map((r) => stageLabel(r.stage)),
    datasets: [
        {
            label: 'Actively recommended',
            data: props.mentionTypeByStage.map((r) => r.recommended),
            backgroundColor: chartColors.value.green + 'cc',
            borderRadius: 4,
        },
        {
            label: 'Neutral mention',
            data: props.mentionTypeByStage.map((r) => r.neutral),
            backgroundColor: chartColors.value.amber + 'cc',
            borderRadius: 4,
        },
        {
            label: 'Negative mention',
            data: props.mentionTypeByStage.map((r) => r.negative),
            backgroundColor: chartColors.value.red + 'cc',
            borderRadius: 4,
        },
        {
            label: 'Not mentioned',
            data: props.mentionTypeByStage.map((r) => r.absent),
            backgroundColor: chartColors.value.grid,
            borderRadius: 4,
        },
    ],
}));

const mentionTypeOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: true,
    aspectRatio: 2,
    plugins: {
        legend: { position: 'top' as const, labels: { color: chartColors.value.text } },
        tooltip: {
            backgroundColor: chartColors.value.tooltipBg,
            titleColor: chartColors.value.tooltipText,
            bodyColor: chartColors.value.tooltipText,
            borderColor: chartColors.value.tooltipBorder,
            borderWidth: 1,
            padding: 12,
            cornerRadius: 8,
        },
    },
    scales: {
        x: { stacked: true, ticks: { color: chartColors.value.text }, grid: { display: false } },
        y: { stacked: true, beginAtZero: true, ticks: { color: chartColors.value.text }, grid: { color: chartColors.value.grid } },
    },
}));

// Strength by category
const strengthByCategoryData = computed(() => ({
    labels: props.strengthByCategory.map((c) => c.category.replace(/-/g, ' ')),
    datasets: [
        {
            label: 'Avg recommendation strength',
            data: props.strengthByCategory.map((c) => c.avg_strength),
            backgroundColor: chartColors.value.primary + 'cc',
            borderRadius: 4,
        },
        {
            label: 'Avg survival rate (%)',
            data: props.strengthByCategory.map((c) => c.avg_survival),
            backgroundColor: chartColors.value.blue + '66',
            borderRadius: 4,
        },
    ],
}));

const strengthByCategoryOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: true,
    aspectRatio: 1.6,
    indexAxis: 'y' as const,
    plugins: { legend: { labels: { color: chartColors.value.text } } },
    scales: {
        x: { beginAtZero: true, max: 100, ticks: { color: chartColors.value.text }, grid: { color: chartColors.value.grid } },
        y: { ticks: { color: chartColors.value.text }, grid: { display: false } },
    },
}));

// Platform agreement donut
const platformAgreementData = computed(() => ({
    labels: ['All 3 platforms cited', 'No platforms cited', 'Mixed (1-2 of 3)'],
    datasets: [
        {
            data: [props.platformAgreement.unanimous_yes, props.platformAgreement.unanimous_no, props.platformAgreement.split],
            backgroundColor: [chartColors.value.green, chartColors.value.grid, chartColors.value.amber],
            borderWidth: 0,
            hoverOffset: 8,
        },
    ],
}));

const platformAgreementOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: true,
    plugins: {
        legend: { position: 'bottom' as const, labels: { color: chartColors.value.text, padding: 16, usePointStyle: true, pointStyle: 'circle' } },
    },
}));

// Strength distribution histogram
const strengthHistogramData = computed(() => ({
    labels: props.strengthDistribution.map((b) => b.range),
    datasets: [
        {
            label: 'Brands',
            data: props.strengthDistribution.map((b) => b.count),
            backgroundColor: props.strengthDistribution.map((b) =>
                b.min >= 60 ? chartColors.value.green + 'cc'
                : b.min >= 40 ? chartColors.value.blue + 'cc'
                : b.min >= 20 ? chartColors.value.amber + 'cc'
                : chartColors.value.red + '99'
            ),
            borderRadius: 4,
        },
    ],
}));

const strengthHistogramOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: true,
    aspectRatio: 2,
    plugins: { legend: { display: false } },
    scales: {
        x: { ticks: { color: chartColors.value.text }, grid: { display: false } },
        y: { beginAtZero: true, ticks: { color: chartColors.value.text, stepSize: 1 }, grid: { color: chartColors.value.grid } },
    },
}));

// Survival vs Strength scatter — per-brand gap visualization
const survivalVsStrengthData = computed(() => ({
    datasets: [
        {
            label: 'Brands',
            data: props.brands.map((b) => ({ x: b.survival_rate, y: b.strength_score, domain: b.domain, category: b.category })),
            backgroundColor: chartColors.value.primary + 'cc',
            borderColor: chartColors.value.primary,
            pointRadius: 5,
            pointHoverRadius: 8,
        },
    ],
}));

const survivalVsStrengthOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: true,
    aspectRatio: 1.5,
    plugins: {
        legend: { display: false },
        tooltip: {
            backgroundColor: chartColors.value.tooltipBg,
            titleColor: chartColors.value.tooltipText,
            bodyColor: chartColors.value.tooltipText,
            borderColor: chartColors.value.tooltipBorder,
            borderWidth: 1,
            padding: 12,
            cornerRadius: 8,
            callbacks: {
                label: (ctx: any) => `${ctx.raw.domain}: survival ${ctx.parsed.x}%, strength ${ctx.parsed.y}`,
            },
        },
    },
    scales: {
        x: {
            title: { display: true, text: 'Survival rate (%)', color: chartColors.value.text },
            min: 0, max: 100,
            ticks: { color: chartColors.value.text },
            grid: { color: chartColors.value.grid },
        },
        y: {
            title: { display: true, text: 'Recommendation strength', color: chartColors.value.text },
            min: 0, max: 100,
            ticks: { color: chartColors.value.text },
            grid: { color: chartColors.value.grid },
        },
    },
}));

// Brand table filter state
const brandSearchTerm = ref('');
const brandCategoryFilter = ref('all');
const brandSortKey = ref<'survival_rate' | 'strength_score' | 'top_pick_rate' | 'geo_score'>('survival_rate');

const uniqueCategories = computed(() =>
    Array.from(new Set(props.brands.map((b) => b.category))).sort()
);

const filteredBrands = computed(() => {
    const term = brandSearchTerm.value.toLowerCase().trim();
    const list = props.brands.filter((b) => {
        if (brandCategoryFilter.value !== 'all' && b.category !== brandCategoryFilter.value) return false;
        if (term === '') return true;
        return b.domain.toLowerCase().includes(term) || b.category.toLowerCase().includes(term);
    });
    return [...list].sort((a, b) => (b[brandSortKey.value] as number) - (a[brandSortKey.value] as number));
});

</script>

<template>
    <Head>
        <title>Ecommerce Recommendation Survival: A Multi-Stage AI Citation Study | GeoSource.ai</title>
        <meta name="description" content="A 43-brand multi-stage study of how ecommerce sites survive the AI shopping journey. We test which content signals best predict making it to the purchase recommendation." />
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
                        <ShoppingCart class="mr-1.5 h-3 w-3" aria-hidden="true" />
                        Original Research
                    </Badge>
                    <h1 class="text-3xl font-bold tracking-tight sm:text-4xl lg:text-5xl">
                        Which ecommerce brands survive the AI shopping journey?
                    </h1>
                    <p class="mt-4 max-w-3xl text-lg text-muted-foreground sm:text-xl leading-relaxed">
                        Single-turn citation rate isn't the same as business impact — a brand can be invisible in the first answer but show up later in the shopping conversation and still drive a sale. To close that gap, we built a four-stage shopping-journey study: discovery, filter, compare, purchase. For each of 43 ecommerce brands across 12 categories, we measured which ones survived all the way to the buy-now recommendation. Then we used the results to <strong class="text-foreground">tune our scoring algorithm</strong> against an empirical commercial outcome instead of bare citation rate.
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
                            <p class="text-base">Study pipeline still running. Refresh once it finishes.</p>
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
                                    <div class="text-3xl font-bold text-primary">{{ headline.total_brands }}</div>
                                    <div class="mt-1 text-xs text-muted-foreground">Brands tested</div>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardContent class="p-5 text-center">
                                    <div class="text-3xl font-bold text-primary">{{ headline.categories }}</div>
                                    <div class="mt-1 text-xs text-muted-foreground">Product categories</div>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardContent class="p-5 text-center">
                                    <div class="text-3xl font-bold text-primary">{{ headline.total_checks }}</div>
                                    <div class="mt-1 text-xs text-muted-foreground">Citation checks (4 stages × 3 platforms)</div>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardContent class="p-5 text-center">
                                    <div class="text-3xl font-bold text-primary">{{ headline.survived_purchase_pct }}%</div>
                                    <div class="mt-1 text-xs text-muted-foreground">Of brands reach the purchase stage</div>
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
                                    <strong class="text-foreground">Average survival rate is {{ headline.avg_survival_rate }}%.</strong> Across all {{ headline.total_brands }} brands, each tested at 4 journey stages on 3 AI platforms, the typical brand was cited at {{ headline.avg_survival_rate }}% of those (stage × platform) opportunities. <strong class="text-foreground">{{ headline.survived_all_stages_pct }}%</strong> of brands survived every stage with at least one citation per stage.
                                </p>
                                <p>
                                    <strong class="text-foreground">Citation rate decays as the user narrows.</strong> Discovery-stage queries pulled the most citations; purchase-intent queries pulled the fewest. Plenty of brands surface for <em>"what are the best running shoes"</em> but don't survive narrowing to <em>"recommend running shoes for flat feet under $150"</em> — exactly the kind of gap that separates presence from purchase impact.
                                </p>
                                <p v-if="pillarRecommendation.available">
                                    <strong class="text-foreground">The pillars that predict citation in informational queries don't predict survival in shopping queries — several of them point the wrong way.</strong> Pages high on Topic Authority, Answerability, and Multimedia tend to be recommended <em>less</em> in AI shopping flows than cleaner utility pages. We shipped a new <strong class="text-foreground">Recommendation Readiness Score</strong> calibrated against this dataset for ecommerce-detected pages.
                                </p>
                            </CardContent>
                        </Card>
                    </div>
                </section>

                <Separator />

                <!-- Stage decay chart -->
                <section class="py-12 sm:py-16">
                    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                        <h2 class="text-2xl font-bold tracking-tight sm:text-3xl mb-2">Citation rate decays through the funnel</h2>
                        <p class="text-muted-foreground mb-6 max-w-3xl">
                            Each stage asks a more specific question than the last. The narrower the query, the fewer brands AI cites — and the gap between discovery and purchase intent is where most brands lose the recommendation.
                        </p>
                        <Card>
                            <CardContent class="p-6">
                                <Line :data="stageDecayData" :options="stageDecayOptions" />
                                <div class="mt-4 grid gap-3 sm:grid-cols-4">
                                    <div v-for="s in stageSummaries" :key="s.stage" class="rounded-md bg-muted/30 p-3 text-sm">
                                        <div class="font-medium">{{ stageLabel(s.stage) }}</div>
                                        <div class="mt-1 text-muted-foreground">
                                            <span class="font-mono text-foreground">{{ s.avg_citation_rate }}%</span> avg cite ·
                                            <span class="font-mono text-foreground">{{ s.cited_count }}/{{ s.n }}</span> brand-stages cited
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </section>

                <Separator />

                <!-- Strength decay across the funnel -->
                <section class="py-12 sm:py-16">
                    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                        <h2 class="text-2xl font-bold tracking-tight sm:text-3xl mb-2">Strength decays even faster than presence</h2>
                        <p class="text-muted-foreground mb-6 max-w-3xl">
                            The "did you get cited?" line decays through the funnel — but the "how strongly are you being recommended?" line decays even faster. Brands that appear at discovery often get downgraded to neutral mentions or dropped entirely by the purchase stage.
                        </p>
                        <Card>
                            <CardContent class="p-6">
                                <Line :data="stageStrengthData" :options="stageStrengthOptions" />
                            </CardContent>
                        </Card>
                    </div>
                </section>

                <Separator />

                <!-- Mention type breakdown by stage -->
                <section class="py-12 sm:py-16">
                    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                        <h2 class="text-2xl font-bold tracking-tight sm:text-3xl mb-2">What kind of mention each stage actually produces</h2>
                        <p class="text-muted-foreground mb-6 max-w-3xl">
                            Citations fall on a spectrum: actively recommended, neutrally listed alongside competitors, or not mentioned at all. As queries narrow toward purchase intent, the "actively recommended" share shrinks and the "not mentioned" share grows. That's where brands lose recommendation strength.
                        </p>
                        <Card>
                            <CardContent class="p-6">
                                <Bar :data="mentionTypeData" :options="mentionTypeOptions" />
                                <p class="mt-4 text-xs text-muted-foreground">
                                    Negative mentions were rare across all stages — AI assistants tend to omit brands rather than disparage them.
                                </p>
                            </CardContent>
                        </Card>
                    </div>
                </section>

                <Separator />

                <!-- Pillar findings (qualitative) -->
                <section class="py-12 sm:py-16">
                    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                        <h2 class="text-2xl font-bold tracking-tight sm:text-3xl mb-2">Which pillars predict survival?</h2>
                        <p class="text-muted-foreground mb-6 max-w-3xl">
                            We tested all 12 GEO pillars against per-brand survival rate. The directional pattern is the headline finding.
                        </p>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <Card class="border-l-4 border-l-red-500">
                                <CardContent class="p-5">
                                    <h3 class="font-semibold mb-2 text-red-600 dark:text-red-400">Pillars where more was worse</h3>
                                    <p class="text-sm text-muted-foreground">
                                        Pages high on <strong class="text-foreground">Topic Authority</strong>, <strong class="text-foreground">Answerability</strong>, and <strong class="text-foreground">Multimedia</strong> tended to be recommended less in AI shopping flows. Structure and E-E-A-T pointed the same direction with weaker effect.
                                    </p>
                                </CardContent>
                            </Card>
                            <Card class="border-l-4 border-l-green-500">
                                <CardContent class="p-5">
                                    <h3 class="font-semibold mb-2 text-green-600 dark:text-green-400">Pillars where direction was flat or positive</h3>
                                    <p class="text-sm text-muted-foreground">
                                        Readability and Question Coverage showed effectively no relationship with survival. AI Accessibility was the only pillar with a positive direction, but with most brands already scoring near max, the signal was thin.
                                    </p>
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                </section>

                <Separator />

                <!-- Discovery: signals invert in shopping contexts -->
                <section class="py-12 sm:py-16">
                    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                        <h2 class="text-2xl font-bold tracking-tight sm:text-3xl mb-2">A discovery: the signals invert in shopping contexts</h2>
                        <p class="text-muted-foreground mb-6 max-w-3xl">
                            The most interesting thing this study uncovered isn't a tuning tweak — it's that the content signals AI rewards in informational queries do the <em>opposite</em> work in shopping queries. The same pillars that predict citation for "what is X" predict <em>against</em> recommendation for "recommend me X."
                        </p>
                        <Card class="border-l-4 border-l-primary">
                            <CardContent class="p-6 space-y-3 text-sm text-muted-foreground leading-relaxed">
                                <div class="flex items-center gap-2 font-semibold text-foreground text-base">
                                    <Settings class="h-4 w-4 text-primary" aria-hidden="true" />
                                    The pattern we found
                                </div>
                                <p>
                                    In informational AI search, high-authority and high-answerability pages get cited. Long-form, expert-reviewed content is the natural source for "what is high blood pressure" or "what is CRM software." Our earlier research confirmed this and built the Citation Readiness Score around it.
                                </p>
                                <p>
                                    In AI shopping flows, that pattern flips. The 40 brands we tested showed that pages high on Topic Authority, Answerability, and Multimedia were recommended <em>less</em> often, not more, as users moved from discovery through to purchase intent. Heavily-SEO-optimized DTC landing pages — dense answer copy, expert bylines, video carousels, FAQ accordions — tended to lose to cleaner utility-style product pages from category-fit brands.
                                </p>
                                <p>
                                    The pattern was consistent enough across 12 categories and 4 journey stages to be a finding, not a quirk of any single brand. Whatever AI shopping assistants are doing, they're doing something different from what AI research assistants are doing.
                                </p>
                                <p class="text-xs italic pt-2 border-t border-border/40">
                                    Product note: we've added a Recommendation Readiness Score to our scan results that captures this finding — it surfaces automatically when a page is detected as transactional, alongside the existing Citation Readiness Score for informational content. The weights live behind the API; this page presents the discovery, not the implementation.
                                </p>
                            </CardContent>
                        </Card>
                    </div>
                </section>

                <Separator />

                <!-- Why the inverse direction -->
                <section class="py-12 sm:py-16">
                    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                        <h2 class="text-2xl font-bold tracking-tight sm:text-3xl mb-6">Why the inverse direction?</h2>
                        <Card>
                            <CardContent class="p-6 space-y-4 text-muted-foreground leading-relaxed">
                                <p>
                                    The brands that survived the journey were not the high-GEO brands. <strong class="text-foreground">Casper</strong>, <strong class="text-foreground">Allbirds</strong>, <strong class="text-foreground">Athletic Greens</strong>, and <strong class="text-foreground">Glossier</strong> — all heavily-SEO-optimized DTC landing pages — scored zero across all stages. Meanwhile <strong class="text-foreground">Chewy</strong>, <strong class="text-foreground">Hoka</strong>, and <strong class="text-foreground">Helix</strong> dominated their categories.
                                </p>
                                <p>
                                    Our best read of why: the high-GEO pages in this set are textbook conversion-optimized landing pages — long hero, social proof bands, FAQ accordions, expert-reviewer bylines, the works. AI shopping flows appear to skip past those in favor of mentioning the brand by name and (sometimes) linking to simpler product or category pages. Heavily-answerable pages also tend to read like SEO bait, which the platforms may be down-ranking in shopping contexts.
                                </p>
                                <p>
                                    The honest caveats: brand recognition is a huge confound we can't measure directly — Chewy survives partly because Chewy is the obvious answer for pet supplies, not because of anything on chewy.com. Some of our high-GEO brands have drifted from their original positioning (Allbirds no longer makes serious running shoes), so the AI is correctly not recommending them — that's category-fit, not page quality. And with 40 brands, individual results have meaningful uncertainty; read the overall pattern, not any single brand's number.
                                </p>
                                <p>
                                    Despite those caveats, three independent rounds of our research (the original citation study, the E-E-A-T follow-up, and this ecommerce study) all point at the same thing: <strong class="text-foreground">a high GEO Authority/E-E-A-T pillar score is not a reliable predictor of AI citation or recommendation</strong>. For ecommerce specifically, it appears to anti-correlate. That's a strong enough pattern to bake into the algorithm even with the caveats.
                                </p>
                            </CardContent>
                        </Card>
                    </div>
                </section>

                <Separator />

                <!-- Category leaderboard -->
                <section class="py-12 sm:py-16">
                    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                        <h2 class="text-2xl font-bold tracking-tight sm:text-3xl mb-6">Survival by category</h2>
                        <div class="overflow-x-auto rounded-md border">
                            <table class="w-full text-sm">
                                <thead class="bg-muted/40 text-left">
                                    <tr>
                                        <th class="px-3 py-2 font-medium">Category</th>
                                        <th class="px-3 py-2 font-medium text-right">N brands</th>
                                        <th class="px-3 py-2 font-medium text-right">Avg survival</th>
                                        <th class="px-3 py-2 font-medium text-right">Reached purchase</th>
                                        <th class="px-3 py-2 font-medium text-right">Avg GEO</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="c in categorySummaries" :key="c.category" class="border-t">
                                        <td class="px-3 py-2 font-medium">{{ c.category.replace(/-/g, ' ') }}</td>
                                        <td class="px-3 py-2 text-right font-mono">{{ c.n }}</td>
                                        <td class="px-3 py-2 text-right font-mono">{{ c.avg_survival }}%</td>
                                        <td class="px-3 py-2 text-right font-mono">{{ c.purchase_survival_pct }}%</td>
                                        <td class="px-3 py-2 text-right font-mono">{{ c.avg_geo }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <Separator />

                <!-- Recommendation Strength (replaces affiliate-data plan) -->
                <section v-if="headline.strength_analyzed > 0" class="py-12 sm:py-16">
                    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                        <h2 class="text-2xl font-bold tracking-tight sm:text-3xl mb-2">Beyond binary: recommendation strength</h2>
                        <p class="text-muted-foreground mb-6 max-w-3xl">
                            Being mentioned isn't the same as being recommended — and being recommended isn't the same as being the top pick. We went back to the {{ headline.total_checks }} AI responses we stored during this study and ran a follow-up classifier on each one to score how strongly each brand was actually being recommended, not just whether its name appeared.
                        </p>
                        <p class="text-muted-foreground mb-6 max-w-3xl">
                            Each AI response is now tagged with: how the brand was mentioned (actively recommended, neutrally listed, or negative), where in the answer it appeared, whether it landed in a top-3 list, and whether the AI included a buy/comparison link. Those dimensions combine into a 0-100 <strong class="text-foreground">Recommendation Strength Score</strong> — a much closer proxy for "would-this-drive-a-sale" than the raw cited/not-cited signal.
                        </p>
                        <div class="grid gap-4 sm:grid-cols-3 mb-6">
                            <Card>
                                <CardContent class="p-5 text-center">
                                    <div class="text-3xl font-bold text-primary">{{ headline.avg_strength_score }}</div>
                                    <div class="mt-1 text-xs text-muted-foreground">Average strength score (0–100)</div>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardContent class="p-5 text-center">
                                    <div class="text-3xl font-bold text-primary">{{ brands.filter((b) => b.recommended_rate > 50).length }}</div>
                                    <div class="mt-1 text-xs text-muted-foreground">Brands actively recommended in &gt; 50% of checks</div>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardContent class="p-5 text-center">
                                    <div class="text-3xl font-bold text-primary">{{ brands.filter((b) => b.top_pick_rate > 0).length }}</div>
                                    <div class="mt-1 text-xs text-muted-foreground">Brands that hit a top-3 list in at least one check</div>
                                </CardContent>
                            </Card>
                        </div>
                        <Card class="border-l-4 border-l-primary">
                            <CardContent class="p-6 space-y-3 text-sm leading-relaxed text-muted-foreground">
                                <p>
                                    <strong class="text-foreground">What this lets us see that survival rate didn't:</strong> brands with similar survival rates can have very different strength scores. A brand named neutrally in a long list of competitors gets the same "cited" credit as a brand actively recommended at position #1 — but only one of those is going to drive a purchase. The strength score separates them.
                                </p>
                                <p>
                                    The score is still an inferred proxy, not measured purchase data. We can't directly observe whether an AI recommendation translated to a sale. But it's the closest we can get using only the response data we have.
                                </p>
                            </CardContent>
                        </Card>
                    </div>
                </section>

                <Separator v-if="headline.strength_analyzed > 0" />

                <!-- Survival vs Strength scatter -->
                <section v-if="headline.strength_analyzed > 0" class="py-12 sm:py-16">
                    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                        <h2 class="text-2xl font-bold tracking-tight sm:text-3xl mb-2">Survival rate vs recommendation strength, per brand</h2>
                        <p class="text-muted-foreground mb-6 max-w-3xl">
                            Each dot is one brand. The diagonal would be the perfect-agreement line — brands above it are recommended more strongly than their survival rate alone would suggest; brands below are cited a lot but rarely as a top pick. Hover to see which brand each dot is.
                        </p>
                        <Card>
                            <CardContent class="p-6">
                                <Scatter :data="survivalVsStrengthData" :options="survivalVsStrengthOptions" />
                            </CardContent>
                        </Card>
                    </div>
                </section>

                <Separator v-if="headline.strength_analyzed > 0" />

                <!-- Strength by category -->
                <section v-if="headline.strength_analyzed > 0" class="py-12 sm:py-16">
                    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                        <h2 class="text-2xl font-bold tracking-tight sm:text-3xl mb-2">Recommendation behaviour by category</h2>
                        <p class="text-muted-foreground mb-6 max-w-3xl">
                            Survival (how often a brand was cited) and strength (how strongly when cited) ranked by category. Coffee subscriptions came in highest on both; supplements and apparel verticals were the toughest categories for any single brand to dominate.
                        </p>
                        <Card>
                            <CardContent class="p-6">
                                <Bar :data="strengthByCategoryData" :options="strengthByCategoryOptions" />
                            </CardContent>
                        </Card>
                    </div>
                </section>

                <Separator v-if="headline.strength_analyzed > 0" />

                <!-- Distribution + platform agreement side by side -->
                <section v-if="headline.strength_analyzed > 0" class="py-12 sm:py-16">
                    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                        <h2 class="text-2xl font-bold tracking-tight sm:text-3xl mb-2">Distribution &amp; platform agreement</h2>
                        <p class="text-muted-foreground mb-6 max-w-3xl">
                            Two views of how concentrated AI shopping recommendations are: how strength scores distribute across the brand set, and how often all three platforms reached the same verdict.
                        </p>
                        <div class="grid gap-6 md:grid-cols-2">
                            <Card>
                                <CardContent class="p-6">
                                    <h3 class="font-semibold mb-2 text-sm">Brands by strength bucket</h3>
                                    <Bar :data="strengthHistogramData" :options="strengthHistogramOptions" />
                                    <p class="mt-3 text-xs text-muted-foreground">
                                        Most brands cluster at the low end. No brand in our study reached a strength score above 80 — strong recommendation is rare even for well-known brands.
                                    </p>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardContent class="p-6">
                                    <h3 class="font-semibold mb-2 text-sm">Cross-platform agreement</h3>
                                    <Doughnut :data="platformAgreementData" :options="platformAgreementOptions" />
                                    <p class="mt-3 text-xs text-muted-foreground">
                                        Of {{ platformAgreement.total }} (brand × stage) pairs, the three platforms reached the same verdict (yes or no) {{ platformAgreement.total > 0 ? Math.round(((platformAgreement.unanimous_yes + platformAgreement.unanimous_no) / platformAgreement.total) * 100) : 0 }}% of the time. Platform-by-platform optimization is rarely necessary.
                                    </p>
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                </section>

                <Separator />

                <!-- Per-brand table with filters -->
                <section class="py-12 sm:py-16">
                    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                        <h2 class="text-2xl font-bold tracking-tight sm:text-3xl mb-2">All {{ brands.length }} brands</h2>
                        <p class="text-muted-foreground mb-4 text-sm">
                            Strength is the 0–100 follow-up classifier score; Top-3% is the fraction of checks where the brand landed in a top-3 ranked list.
                        </p>

                        <!-- Filters -->
                        <div class="mb-4 flex flex-wrap items-center gap-2 text-sm">
                            <div class="relative flex-1 min-w-[200px]">
                                <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" aria-hidden="true" />
                                <input
                                    v-model="brandSearchTerm"
                                    type="search"
                                    placeholder="Search brand or category…"
                                    class="w-full rounded-md border border-input bg-background pl-9 pr-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                                />
                            </div>
                            <select
                                v-model="brandCategoryFilter"
                                class="rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-ring"
                            >
                                <option value="all">All categories</option>
                                <option v-for="c in uniqueCategories" :key="c" :value="c">{{ c.replace(/-/g, ' ') }}</option>
                            </select>
                            <select
                                v-model="brandSortKey"
                                class="rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-ring"
                            >
                                <option value="survival_rate">Sort: survival</option>
                                <option value="strength_score">Sort: strength</option>
                                <option value="top_pick_rate">Sort: top-3 %</option>
                                <option value="geo_score">Sort: GEO</option>
                            </select>
                            <span class="ml-auto text-xs text-muted-foreground">
                                Showing {{ filteredBrands.length }} of {{ brands.length }}
                            </span>
                        </div>

                        <div class="overflow-x-auto rounded-md border">
                            <table class="w-full text-sm">
                                <thead class="bg-muted/40 text-left">
                                    <tr>
                                        <th class="px-3 py-2 font-medium">Brand</th>
                                        <th class="px-3 py-2 font-medium">Category</th>
                                        <th class="px-3 py-2 font-medium text-right">GEO</th>
                                        <th class="px-3 py-2 font-medium text-right">Discovery</th>
                                        <th class="px-3 py-2 font-medium text-right">Filter</th>
                                        <th class="px-3 py-2 font-medium text-right">Compare</th>
                                        <th class="px-3 py-2 font-medium text-right">Purchase</th>
                                        <th class="px-3 py-2 font-medium text-right">Survival</th>
                                        <th class="px-3 py-2 font-medium text-right">Strength</th>
                                        <th class="px-3 py-2 font-medium text-right">Top-3%</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="b in filteredBrands" :key="b.domain" class="border-t">
                                        <td class="px-3 py-2 font-mono text-xs">{{ b.domain }}</td>
                                        <td class="px-3 py-2 text-muted-foreground">{{ b.category.replace(/-/g, ' ') }}</td>
                                        <td class="px-3 py-2 text-right font-mono">{{ b.geo_score ?? '—' }}</td>
                                        <td class="px-3 py-2 text-right font-mono">{{ b.stage_rates.discovery }}%</td>
                                        <td class="px-3 py-2 text-right font-mono">{{ b.stage_rates.filter }}%</td>
                                        <td class="px-3 py-2 text-right font-mono">{{ b.stage_rates.compare }}%</td>
                                        <td class="px-3 py-2 text-right font-mono">{{ b.stage_rates.purchase }}%</td>
                                        <td class="px-3 py-2 text-right font-mono font-semibold">{{ b.survival_rate }}%</td>
                                        <td class="px-3 py-2 text-right font-mono">{{ b.strength_score }}</td>
                                        <td class="px-3 py-2 text-right font-mono">{{ b.top_pick_rate }}%</td>
                                    </tr>
                                    <tr v-if="filteredBrands.length === 0">
                                        <td colspan="10" class="px-3 py-6 text-center text-muted-foreground">
                                            No brands match this filter.
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
                                    We picked 12 product verticals where AI assistants are plausibly part of the buying journey (running shoes, memory-foam mattresses, eyewear, minimalist skincare, sustainable apparel, coffee subscriptions, meal kits, pet supplies, DTC furniture, wellness supplements, razors, bedsheets). For each, we selected 3-5 well-known brands competing in that category — {{ headline.total_brands }} brands in total.
                                </p>
                                <p>
                                    For each category, we wrote four queries representing a typical shopping journey: <strong class="text-foreground">discovery</strong> ("what are the best running shoes"), <strong class="text-foreground">filter</strong> ("for flat feet and overpronation"), <strong class="text-foreground">compare</strong> ("compare the top running shoes for stability and cushioning"), and <strong class="text-foreground">purchase intent</strong> ("recommend the top 3 with links"). Every brand in a category was tested against the same four queries.
                                </p>
                                <p>
                                    Each brand × stage was checked across ChatGPT, Perplexity, and Claude — {{ headline.total_checks }} citation checks total. We compute per-stage citation rate and aggregate across stages to a per-brand survival rate. We then look at how each of the 12 GEO pillars relates to per-brand survival to find the strongest predictors.
                                </p>
                                <p>
                                    The Recommendation Readiness Score weights are derived from those relationships. The score uses the same pillar scores the GEO score uses — only the weighting and direction are new.
                                </p>
                                <p>
                                    <strong class="text-foreground">Limitations.</strong> <em>Concept queries don't capture conversational state.</em> Each stage is an independent API call — we approximate a multi-turn conversation, not the real thing. <em>Survival ≠ purchase.</em> Being recommended at the buy-now stage is the closest proxy we can build, but actual purchase outcomes require attribution data we don't have. <em>Brand recognition dominates.</em> A small DTC brand with a great page may lose to a well-known competitor on every stage. Survival isn't purely a function of page quality.
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
                                    <p class="text-sm text-muted-foreground mb-3">61 sites across 17 industries. The starting point for our AI-citation research.</p>
                                    <Link href="/blog/geo-citation-study" class="text-sm font-medium text-primary hover:underline">Read the original citation study &rarr;</Link>
                                </CardContent>
                            </Card>
                            <Card class="border-l-4 border-l-primary">
                                <CardContent class="p-5">
                                    <h3 class="font-semibold mb-2">E-E-A-T &amp; content type</h3>
                                    <p class="text-sm text-muted-foreground mb-3">Controlled 2×2 testing how E-E-A-T signals behave across content types.</p>
                                    <Link href="/blog/eeat-content-type-study" class="text-sm font-medium text-primary hover:underline">Read the follow-up &rarr;</Link>
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
