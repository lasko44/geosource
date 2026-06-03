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
import { Bar, Doughnut } from 'vue-chartjs';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    BarElement,
    ArcElement,
    Title,
    Tooltip,
    Legend,
} from 'chart.js';
import { ArrowRight, Calendar, BarChart3, Brain, Globe, CheckCircle, XCircle, TrendingUp, AlertTriangle, ChevronDown } from 'lucide-vue-next';
import { register } from '@/routes';
import { computed, onMounted, ref } from 'vue';

ChartJS.register(CategoryScale, LinearScale, BarElement, ArcElement, Title, Tooltip, Legend);

const canonicalUrl = 'https://geosource.ai/blog/geo-citation-study';

const breadcrumbItems = [
    { label: 'Resources', href: '/resources' },
    { label: 'GEO Citation Study' },
];

// Study data — v3: Three-phase study with Citation Readiness Score validation
const studyData = {
    total: 61,
    cited: 31,
    notCited: 30,
    platforms: { openai: 27, perplexity: 23, claude: 24 },
    industries: [
        { name: 'Automotive', count: 1, avgScore: 92, citationRate: 100.0 },
        { name: 'Real Estate', count: 2, avgScore: 84.5, citationRate: 100.0 },
        { name: 'Travel', count: 4, avgScore: 56.8, citationRate: 83.3 },
        { name: 'Finance', count: 4, avgScore: 107.2, citationRate: 75.0 },
        { name: 'Healthcare', count: 5, avgScore: 72, citationRate: 66.7 },
        { name: 'News & Media', count: 3, avgScore: 94.3, citationRate: 55.6 },
        { name: 'Education', count: 4, avgScore: 59, citationRate: 50.0 },
        { name: 'Marketing', count: 3, avgScore: 97.7, citationRate: 44.4 },
        { name: 'Legal', count: 3, avgScore: 88, citationRate: 33.3 },
        { name: 'Fitness', count: 3, avgScore: 74.3, citationRate: 33.3 },
        { name: 'Cybersecurity', count: 3, avgScore: 78.7, citationRate: 33.3 },
        { name: 'B2B', count: 4, avgScore: 88, citationRate: 33.3 },
        { name: 'Gaming', count: 3, avgScore: 79, citationRate: 22.2 },
        { name: 'Food & Bev', count: 2, avgScore: 70.5, citationRate: 16.7 },
        { name: 'SaaS', count: 10, avgScore: 77.9, citationRate: 13.3 },
        { name: 'Ecommerce', count: 5, avgScore: 83.2, citationRate: 6.7 },
    ],
    pillarAnalysis: {
        // Direction-only summary; magnitudes are not published.
        // lift > 0 → positive direction; lift < 0 → negative; lift === 0 → neutral.
        highVsLow: [
            { pillar: 'Answerability', lift: 1, insight: 'Strongest positive predictor — direct, declarative content gets cited more often' },
            { pillar: 'Citations Quality', lift: 1, insight: 'Sites that cite external sources earn more AI citations themselves' },
            { pillar: 'Definitions', lift: 1, insight: 'Explicit "X is Y" definitions lift citation rates' },
            { pillar: 'Authority', lift: 1, insight: 'Topic depth and internal linking provide a moderate positive edge' },
            { pillar: 'Freshness', lift: 1, insight: 'Content recency provides a meaningful edge' },
            { pillar: 'Machine Readable', lift: 1, insight: 'Modest positive signal — helpful but not a differentiator' },
            { pillar: 'Structure', lift: -1, insight: 'Necessary but not sufficient — most sites already score well' },
            { pillar: 'Readability', lift: -1, insight: 'Neutral-to-slightly-negative at this threshold — quality may be binary' },
            { pillar: 'E-E-A-T', lift: -1, insight: 'Counter-intuitive negative — explored further in our E-E-A-T follow-up study' },
            { pillar: 'Question Coverage', lift: -1, insight: 'Small sample — most sites lack FAQ content' },
            { pillar: 'Multimedia', lift: -1, insight: 'Heavy multimedia may indicate less text for AI to parse' },
        ],
    },
    topCited: [
        { domain: 'bankrate.com', score: 118, industry: 'Finance' },
        { domain: 'nerdwallet.com', score: 104, industry: 'Finance' },
        { domain: 'legalzoom.com', score: 105, industry: 'Legal' },
        { domain: 'coursera.org', score: 99, industry: 'Education' },
        { domain: 'tripadvisor.com', score: 18, industry: 'Travel' },
        { domain: 'kayak.com', score: 56, industry: 'Travel' },
        { domain: 'webmd.com', score: 72, industry: 'Healthcare' },
        { domain: 'onemedical.com', score: 80, industry: 'Healthcare' },
        { domain: 'twilio.com', score: 87, industry: 'B2B' },
        { domain: 'techcrunch.com', score: 87, industry: 'News & Media' },
    ],
    notCitedSites: [
        { domain: 'khanacademy.org', score: 31, grade: 'F', industry: 'Education' },
        { domain: 'whoop.com', score: 56, grade: 'F', industry: 'Fitness' },
        { domain: 'hellofresh.com', score: 60, grade: 'F', industry: 'Food & Bev' },
        { domain: 'steampowered.com', score: 62, grade: 'F', industry: 'Gaming' },
        { domain: 'datadog.com', score: 63, grade: 'F', industry: 'B2B' },
        { domain: 'linear.app', score: 67, grade: 'F', industry: 'SaaS' },
        { domain: 'avvo.com', score: 69, grade: 'D', industry: 'Legal' },
        { domain: 'postman.com', score: 70, grade: 'D', industry: 'SaaS' },
        { domain: 'airtable.com', score: 70, grade: 'D', industry: 'SaaS' },
        { domain: 'peloton.com', score: 71, grade: 'D', industry: 'Fitness' },
    ],
    sizeStats: {
        large: { count: 40, avgScore: 79.2, citationRate: 49.2 },
        medium: { count: 20, avgScore: 84.6, citationRate: 25.0 },
        small: { count: 1, avgScore: 80, citationRate: 0.0 },
    },
    crScore: {
        highCR: { rate: 55.6, label: 'CR Score >= 50' },
        lowCR: { rate: 33.3, label: 'CR Score < 50' },
        lift: 67,
        geoGap: -3.1,
        crGap: 4.6,
        grades: [
            { label: 'High', rate: 66.7 },
            { label: 'Moderate', rate: 50.9 },
            { label: 'Low', rate: 30.0 },
            { label: 'Very Low', rate: 38.1 },
        ],
    },
};

const pillarCombos = {
    highAnswerAndCite: { rate: 51.6, n: 31 },
    tripleLow: { rate: 24.2, n: 11 },
};

// Direction-only summary of how pillar weights moved between v1 and v2 of
// the GEO scoring algorithm. Specific weight values are not published.
// v2Weight > v1Weight → Increased; v2Weight < v1Weight → Decreased.
const v1VsV2Pillars = [
    { pillar: 'Answerability', v1Weight: 0, v2Weight: 1 },
    { pillar: 'Citations Quality', v1Weight: 0, v2Weight: 1 },
    { pillar: 'Readability', v1Weight: 0, v2Weight: 1 },
    { pillar: 'Definitions', v1Weight: 0, v2Weight: 0 },
    { pillar: 'Structure', v1Weight: 1, v2Weight: 0 },
    { pillar: 'Machine Readable', v1Weight: 1, v2Weight: 0 },
    { pillar: 'Authority', v1Weight: 0, v2Weight: 0 },
];

const faqItems = [
    { question: 'What is Generative Engine Optimization (GEO)?', answer: 'Generative Engine Optimization (GEO) is the practice of optimizing web content so that AI search engines like ChatGPT, Perplexity, Claude, and Gemini can understand, trust, and cite it. While traditional SEO optimizes for Google\'s ranking algorithm, GEO optimizes for the content signals AI platforms use to select sources for their generated answers.' },
    { question: 'What is a GEO score?', answer: 'A GEO score measures how well a web page is optimized for AI search engines across 12 evidence-weighted pillars. It evaluates definition clarity, answerability, citation quality, content structure, readability, and more. The GEO score gives you a comprehensive view of content quality. For a focused prediction of citation likelihood, we also provide the Citation Readiness Score — built from the three pillars our research proved most strongly predict citations.' },
    { question: 'What is the Citation Readiness Score?', answer: 'The Citation Readiness Score (CR Score) is a focused metric built from our three-phase study. It uses only the three pillars proven to predict AI citations: Answerability, Citation Quality, and Definitions. Sites with a high CR Score (>=50) are cited substantially more often than sites with a low CR Score, and unlike the overall GEO score, the CR Score gap points in the right direction.' },
    { question: 'Which GEO pillars matter most for AI citations?', answer: 'Based on our three-phase study of 61 websites, the three most predictive pillars are Answerability, Citation Quality, and Definitions. These three pillars form the basis of our Citation Readiness Score. Other pillars like Structure, E-E-A-T, and Multimedia showed no positive correlation or even negative correlation with citation rates.' },
    { question: 'Does a high GEO score guarantee AI citations?', answer: 'No single score can guarantee citations — AI citation depends on content quality, industry context, query type, and brand recognition working together. What we can say from our data: the Citation Readiness Score (built from the three most predictive pillars) materially predicts whether AI cites a site. The GEO score measures overall content quality for AI comprehension, while the CR Score focuses specifically on citation likelihood. Both are useful for different purposes.' },
    { question: 'How is this study different from other GEO research?', answer: 'This is a three-phase study that tested and refined its own methodology. Phase 1 used equal-weight scoring and discovered which pillars matter. Phase 2 rebalanced weights based on Phase 1 findings. Phase 3 built and validated a focused Citation Readiness Score. Most GEO research stops at Phase 1 and presents correlations as conclusions. We ran 540+ citation checks across 3 AI platforms to validate our findings.' },
    { question: 'How were websites selected for this study?', answer: 'We selected websites across 17 industries, mixing large brands (Salesforce, Airbnb), mid-size companies (Linear, Whoop), and content publishers (Investopedia, Wirecutter). 61 sites were successfully scanned with the full 12-pillar analysis and included in the final dataset for Phase 3.' },
    { question: 'Can small companies compete with big brands in AI search?', answer: 'It depends on the query type. For informational queries ("how to find a doctor"), brand matters less — content quality can win. For recommendation queries ("what is the best CRM"), brand recognition dominates. Our data shows large sites are cited 49.2% vs 25% for medium sites, despite medium sites having higher average GEO scores (84.6 vs 79.2). Mid-size companies should focus on informational, educational content where content quality outweighs brand recognition.' },
    { question: 'Which AI platform cites most frequently?', answer: 'In this study, ChatGPT cited 27 domains, Claude cited 24, and Perplexity cited 23 out of 61 total. The rates are close enough to suggest that optimizing for one platform effectively optimizes for all of them. The content signals AI looks for are converging.' },
    { question: 'What should I focus on to get cited by AI?', answer: 'Based on our research, prioritize three things: (1) Answerability — write direct, declarative content that answers questions without making the reader dig; (2) Citation Quality — cite authoritative external sources in your content; (3) Definitions — include explicit "X is Y" statements. These three pillars drive the Citation Readiness Score and materially raise citation likelihood for high scorers. Beyond content, your industry and brand recognition matter enormously — focus on informational content where content quality can differentiate you.' },
];

const faqJsonLd = computed(() =>
    JSON.stringify({
        '@context': 'https://schema.org',
        '@type': 'FAQPage',
        mainEntity: faqItems.map((item) => ({
            '@type': 'Question',
            name: item.question,
            acceptedAnswer: {
                '@type': 'Answer',
                text: item.answer,
            },
        })),
    }),
);

const openFaqIndex = ref<number | null>(null);
const toggleFaq = (index: number) => {
    openFaqIndex.value = openFaqIndex.value === index ? null : index;
};

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

const industryChartData = computed(() => ({
    labels: studyData.industries.map((i) => i.name),
    datasets: [
        {
            label: 'Citation Rate (%)',
            data: studyData.industries.map((i) => i.citationRate),
            backgroundColor: studyData.industries.map((i) =>
                i.citationRate >= 75 ? chartColors.value.green + 'cc'
                : i.citationRate >= 50 ? chartColors.value.blue + 'cc'
                : i.citationRate >= 33 ? chartColors.value.amber + 'cc'
                : chartColors.value.red + 'cc'
            ),
            borderRadius: 4,
            borderSkipped: false,
        },
    ],
}));

const industryChartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: true,
    aspectRatio: 1.4,
    indexAxis: 'y' as const,
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
                label: (ctx: any) => `Citation Rate: ${ctx.parsed.x}%`,
                afterLabel: (ctx: any) => {
                    const ind = studyData.industries[ctx.dataIndex];
                    return `Avg GEO Score: ${ind.avgScore} | n=${ind.count}`;
                },
            },
        },
    },
    scales: {
        x: {
            beginAtZero: true,
            max: 110,
            ticks: { color: chartColors.value.text, callback: (v: number) => v + '%' },
            grid: { color: chartColors.value.grid },
        },
        y: {
            ticks: { color: chartColors.value.text, font: { size: 11 } },
            grid: { display: false },
        },
    },
}));

const platformChartData = computed(() => ({
    labels: ['ChatGPT', 'Perplexity', 'Claude'],
    datasets: [
        {
            data: [studyData.platforms.openai, studyData.platforms.perplexity, studyData.platforms.claude],
            backgroundColor: [chartColors.value.green, chartColors.value.blue, chartColors.value.primary],
            borderWidth: 0,
            hoverOffset: 8,
        },
    ],
}));

const platformChartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: true,
    plugins: {
        legend: {
            position: 'bottom' as const,
            labels: { color: chartColors.value.text, padding: 16, usePointStyle: true, pointStyle: 'circle' },
        },
        tooltip: {
            backgroundColor: chartColors.value.tooltipBg,
            titleColor: chartColors.value.tooltipText,
            bodyColor: chartColors.value.tooltipText,
            borderColor: chartColors.value.tooltipBorder,
            borderWidth: 1,
            padding: 12,
            cornerRadius: 8,
            callbacks: {
                label: (ctx: any) => {
                    const pct = ((ctx.parsed / studyData.total) * 100).toFixed(1);
                    return ` ${ctx.label}: ${ctx.parsed} sites (${pct}%)`;
                },
            },
        },
    },
}));

const crScoreChartData = computed(() => ({
    labels: studyData.crScore.grades.map((g) => g.label),
    datasets: [
        {
            label: 'Citation Rate (%)',
            data: studyData.crScore.grades.map((g) => g.rate),
            backgroundColor: [
                chartColors.value.green + 'cc',
                chartColors.value.blue + 'cc',
                chartColors.value.amber + 'cc',
                chartColors.value.red + '99',
            ],
            borderRadius: 6,
            borderSkipped: false,
        },
    ],
}));

const crScoreChartOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: true,
    aspectRatio: 2,
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
                label: (ctx: any) => `Citation Rate: ${ctx.parsed.y}%`,
            },
        },
    },
    scales: {
        y: {
            beginAtZero: true,
            max: 80,
            ticks: { color: chartColors.value.text, callback: (v: number) => v + '%' },
            grid: { color: chartColors.value.grid },
        },
        x: {
            ticks: { color: chartColors.value.text },
            grid: { display: false },
        },
    },
}));

const gradeColor = (grade: string): string => {
    if (grade.startsWith('A')) return 'text-green-600 dark:text-green-400';
    if (grade.startsWith('B')) return 'text-blue-600 dark:text-blue-400';
    if (grade.startsWith('C')) return 'text-yellow-600 dark:text-yellow-400';
    if (grade.startsWith('D')) return 'text-orange-600 dark:text-orange-400';
    return 'text-red-600 dark:text-red-400';
};

const articleJsonLd = computed(() =>
    JSON.stringify({
        '@context': 'https://schema.org',
        '@type': 'ScholarlyArticle',
        headline: 'GEO Score and AI Citation Correlation: A Three-Phase Empirical Study',
        description: 'Three-phase research study analyzing the relationship between GEO optimization scores, Citation Readiness Scores, and AI search engine citation rates across 61 websites and 17 industries.',
        url: canonicalUrl,
        datePublished: '2026-06-01',
        dateModified: '2026-06-01',
        author: { '@type': 'Organization', name: 'GeoSource.ai', url: 'https://geosource.ai' },
        publisher: { '@type': 'Organization', name: 'GeoSource.ai', url: 'https://geosource.ai' },
    }),
);
</script>

<template>
    <Head>
        <title>GEO Citation Study: A Three-Phase Analysis | GeoSource.ai Research</title>
        <meta name="description" content="Three-phase research study analyzing GEO scores, Citation Readiness Scores, and AI citation rates across 61 websites, 17 industries, and 540+ citation checks." />
        <link rel="canonical" :href="canonicalUrl" />
        <meta property="og:title" content="GEO Citation Study: A Three-Phase Analysis | GeoSource.ai Research" />
        <meta property="og:description" content="We ran 540+ citation checks across 3 AI platforms to find out what actually predicts AI citations. The answer surprised us." />
        <meta property="og:url" :content="canonicalUrl" />
        <meta property="og:type" content="article" />
        <meta property="og:site_name" content="GeoSource.ai" />
        <meta property="og:image" content="https://geosource.ai/images/og-citation-study.png" />
        <meta property="og:image:width" content="1200" />
        <meta property="og:image:height" content="630" />
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:image" content="https://geosource.ai/images/og-citation-study.png" />
        <component :is="'script'" type="application/ld+json">{{ articleJsonLd }}</component>
        <component :is="'script'" type="application/ld+json">{{ faqJsonLd }}</component>
    </Head>

    <div class="min-h-screen bg-background text-foreground">
        <SkipNav />
        <ResourceHeader />

        <main id="main-content">
            <ResourceBreadcrumb :items="breadcrumbItems" />

            <!-- Hero Section -->
            <section class="border-b bg-gradient-to-b from-primary/5 to-background py-16 sm:py-24">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <Badge variant="secondary" class="mb-4">
                        <BarChart3 class="mr-1.5 h-3 w-3" aria-hidden="true" />
                        Original Research
                    </Badge>
                    <h1 class="text-3xl font-bold tracking-tight sm:text-4xl lg:text-5xl">
                        What predicts AI citations? A three-phase study.
                    </h1>
                    <p class="mt-4 max-w-3xl text-lg text-muted-foreground sm:text-xl leading-relaxed">
                        We ran three rounds of analysis on 61 websites across 17 industries and 540+ citation checks. <strong class="text-foreground">Phase 1</strong> discovered which GEO pillars predict citations. <strong class="text-foreground">Phase 2</strong> rebalanced the algorithm based on those findings. <strong class="text-foreground">Phase 3</strong> built and validated a focused Citation Readiness Score that actually predicts whether AI will cite your content.
                    </p>
                    <div class="mt-4 flex items-center gap-4 text-sm text-muted-foreground">
                        <span class="flex items-center gap-1.5">
                            <Calendar class="h-3.5 w-3.5" aria-hidden="true" />
                            Published June 1, 2026
                        </span>
                        <span>&middot;</span>
                        <span>GeoSource.ai Research</span>
                    </div>
                </div>
            </section>

            <!-- Key Metrics -->
            <section class="py-12 sm:py-16">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <div class="grid gap-4 sm:grid-cols-3 lg:grid-cols-6">
                        <Card>
                            <CardContent class="p-5 text-center">
                                <div class="text-3xl font-bold text-primary">3</div>
                                <div class="mt-1 text-xs text-muted-foreground">Study phases</div>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardContent class="p-5 text-center">
                                <div class="text-3xl font-bold text-primary">61</div>
                                <div class="mt-1 text-xs text-muted-foreground">Websites per phase</div>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardContent class="p-5 text-center">
                                <div class="text-3xl font-bold text-primary">17</div>
                                <div class="mt-1 text-xs text-muted-foreground">Industries covered</div>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardContent class="p-5 text-center">
                                <div class="text-3xl font-bold text-primary">12</div>
                                <div class="mt-1 text-xs text-muted-foreground">Research-weighted pillars</div>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardContent class="p-5 text-center">
                                <div class="text-3xl font-bold text-primary">540+</div>
                                <div class="mt-1 text-xs text-muted-foreground">Total citation checks</div>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardContent class="p-5 text-center">
                                <div class="text-3xl font-bold text-primary">3</div>
                                <div class="mt-1 text-xs text-muted-foreground">AI platforms tested</div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </section>

            <Separator />

            <!-- Abstract -->
            <section class="py-12 sm:py-16">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <h2 class="text-2xl font-bold tracking-tight sm:text-3xl mb-6">Abstract</h2>
                    <Card class="border-l-4 border-l-primary">
                        <CardContent class="p-6">
                            <p class="text-muted-foreground leading-relaxed">
                                This three-phase study examines what content signals predict AI citations across ChatGPT, Perplexity AI, and Claude — and uses those findings to build a more accurate scoring tool. Phase 1 scanned 61 websites across 17 industries and identified three pillars that strongly predict citations: <strong class="text-foreground">Answerability</strong>, <strong class="text-foreground">Citation Quality</strong>, and <strong class="text-foreground">Definitions</strong>. Phase 2 rebalanced our scoring algorithm to weight these pillars more heavily. Phase 3 validated a new <strong class="text-foreground">Citation Readiness Score</strong> built from these three pillars; high CR sites are cited substantially more often than low CR sites. The study also revealed that informational content gets cited at dramatically higher rates than product pages, giving smaller companies a clear path to competing with larger brands through educational content.
                            </p>
                        </CardContent>
                    </Card>
                </div>
            </section>

            <Separator />

            <!-- Methodology -->
            <section class="py-12 sm:py-16">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <h2 class="text-2xl font-bold tracking-tight sm:text-3xl mb-6">Methodology</h2>
                    <div class="space-y-6">
                        <div>
                            <h3 class="text-lg font-semibold mb-2">Sample selection</h3>
                            <p class="text-muted-foreground leading-relaxed">
                                We selected websites across 17 industry verticals, intentionally mixing large brands (e.g., Salesforce, Airbnb), mid-size companies (e.g., Linear, Whoop), and authoritative content publishers (e.g., Investopedia, Wirecutter). 61 sites were successfully scanned and included in the final analysis. Sites were chosen to represent a diversity of GEO optimization levels, content strategies, and market positions.
                            </p>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold mb-2">GEO scoring</h3>
                            <p class="text-muted-foreground leading-relaxed">
                                Each website's homepage was scanned using the GeoSource.ai full-tier GEO scoring engine, which evaluates content across 12 pillars grouped into three tiers. <strong class="text-foreground">Core pillars</strong>: Definition Clarity, Structured Knowledge, Topic Authority, Machine-Readable Formatting, and Answerability. <strong class="text-foreground">Advanced pillars</strong>: E-E-A-T Signals, Citation Quality, and AI Accessibility. <strong class="text-foreground">Expert pillars</strong>: Content Freshness, Readability, Question Coverage, and Multimedia Optimization. Composite scores range from 0 to ~150 depending on content depth.
                            </p>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold mb-2">Citation testing</h3>
                            <p class="text-muted-foreground leading-relaxed">
                                For each domain, we crafted a natural-language query that a real user might ask an AI assistant about that company's space. Each query was sent to three AI platforms — <strong class="text-foreground">ChatGPT</strong> (OpenAI), <strong class="text-foreground">Perplexity AI</strong>, and <strong class="text-foreground">Claude</strong> (Anthropic) — and the responses were analyzed for whether the domain appeared as a citation or recommendation. A site was considered "cited" for a platform if the AI's response mentioned the domain or brand name.
                            </p>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold mb-2">Three-phase design</h3>
                            <p class="text-muted-foreground leading-relaxed">
                                <strong class="text-foreground">Phase 1 (Discovery)</strong> used the original equally-weighted scoring algorithm to scan all sites and run citation checks. The findings identified which pillars most strongly predict citations — and revealed that the overall GEO score was not a good predictor. <strong class="text-foreground">Phase 2 (Algorithm Update)</strong> rebalanced the pillar weights based on Phase 1 data, increasing weight for the pillars that predicted citation and reducing weight for those that did not. <strong class="text-foreground">Phase 3 (Validation)</strong> built a focused Citation Readiness Score using only the three proven pillars (Answerability, Citation Quality, and Definitions) and ran a fresh round of 180+ citation checks to validate it against real-world AI behavior.
                            </p>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold mb-2">Limitations</h3>
                            <p class="text-muted-foreground leading-relaxed">
                                This study has several important limitations. Sample size (n=61) limits statistical power. AI responses are non-deterministic — the same query may produce different citations at different times. We tested only homepage URLs, which may not represent a site's best-optimized content. GEO scores reflect a single point-in-time scan. Brand recognition and training data prevalence are confounding variables that this study design cannot fully isolate from content optimization effects. Each phase's citation checks were run at different times, introducing potential temporal variation in AI responses.
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <Separator />

            <!-- Key Discovery -->
            <section class="py-12 sm:py-16">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <h2 class="text-2xl font-bold tracking-tight sm:text-3xl mb-2">What we discovered about AI citations</h2>
                    <p class="text-muted-foreground mb-8 leading-relaxed">
                        This study revealed that <strong class="text-foreground">AI citations are driven by multiple factors working together</strong> — content quality, industry context, query type, and brand awareness. The most valuable insight: we identified exactly which content signals you can control that make the biggest difference, and built those into the Citation Readiness Score.
                    </p>

                    <Card class="border-l-4 border-l-primary mb-8">
                        <CardContent class="p-6">
                            <h3 class="font-semibold text-lg mb-3">The four factors that drive AI citations</h3>
                            <div class="space-y-4">
                                <div class="flex items-start gap-4">
                                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-green-500/10 text-sm font-bold text-green-600 dark:text-green-400">1</div>
                                    <div>
                                        <div class="font-semibold">Content quality — the pillars you can control <span class="text-green-600 dark:text-green-400 font-bold">(meaningful lift with CR Score)</span></div>
                                        <p class="text-sm text-muted-foreground mt-1">Three specific pillars — Answerability, Citation Quality, and Definitions — have the strongest measurable impact on citation likelihood. Sites scoring high on these three are cited substantially more often than low scorers. This is what GeoSource optimizes and what you can directly improve.</p>
                                    </div>
                                </div>
                                <div class="flex items-start gap-4">
                                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-500/10 text-sm font-bold text-blue-600 dark:text-blue-400">2</div>
                                    <div>
                                        <div class="font-semibold">Content type — your strategic advantage <span class="text-blue-600 dark:text-blue-400 font-bold">(informational content wins)</span></div>
                                        <p class="text-sm text-muted-foreground mt-1">Informational content (guides, how-tos, educational pages) gets cited at dramatically higher rates than product pages. This is where smaller companies gain an edge — you don't need to be the biggest brand to have the best answer to "how to choose a CRM" or "what causes back pain."</p>
                                    </div>
                                </div>
                                <div class="flex items-start gap-4">
                                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary/10 text-sm font-bold text-primary">3</div>
                                    <div>
                                        <div class="font-semibold">Industry context <span class="text-primary font-bold">(wide natural variation)</span></div>
                                        <p class="text-sm text-muted-foreground mt-1">Healthcare (66.7%), travel (83.3%), and finance (75%) naturally get cited more often because AI platforms confidently answer factual questions in these domains. SaaS (13.3%) and ecommerce (6.7%) face lower baseline rates — making content quality even more important as a differentiator.</p>
                                    </div>
                                </div>
                                <div class="flex items-start gap-4">
                                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-amber-500/10 text-sm font-bold text-amber-600 dark:text-amber-400">4</div>
                                    <div>
                                        <div class="font-semibold">Brand recognition <span class="text-amber-600 dark:text-amber-400 font-bold">(builds over time)</span></div>
                                        <p class="text-sm text-muted-foreground mt-1">Well-known brands carry an advantage from AI training data. TripAdvisor gets cited even with a low GEO score. But brand recognition isn't static — it's built through consistent presence on review sites, forums, and industry publications. GEO optimization and brand building work together.</p>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </section>

            <Separator />

            <!-- PHASE 1 HEADER -->
            <section class="py-10 sm:py-12 bg-primary/5">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 text-center">
                    <Badge class="mb-3 text-sm px-4 py-1 bg-purple-600 text-white dark:bg-purple-500">Phase 1</Badge>
                    <h2 class="text-2xl font-bold tracking-tight sm:text-3xl">Discovery: Which pillars predict AI citations?</h2>
                    <p class="mt-3 max-w-2xl mx-auto text-muted-foreground leading-relaxed">
                        We scanned 61 sites across 17 industries with all 12 pillars. The goal: identify which pillars most strongly predict AI citations, so we can weight them accordingly and give users the most actionable score possible.
                    </p>
                </div>
            </section>

            <Separator />

            <!-- Finding 1: Industry Breakdown -->
            <section class="py-12 sm:py-16">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <h2 class="text-2xl font-bold tracking-tight sm:text-3xl mb-2">Finding 1: Industry is the strongest predictor of citation</h2>
                    <p class="text-muted-foreground mb-8 leading-relaxed">
                        Industry was the most powerful predictor of citation likelihood in our dataset — more so than GEO score, any individual pillar, or content quality. Informational industries where AI platforms have strong knowledge and users ask factual questions showed dramatically higher citation rates. Transaction-oriented industries where queries tend toward subjective preferences showed much lower rates. The spread between the top and bottom industries is enormous: <strong class="text-foreground">Automotive/Real Estate at 100% vs Ecommerce at 6.7%</strong>.
                    </p>
                    <Card>
                        <CardContent class="p-6">
                            <Bar :data="industryChartData" :options="industryChartOptions" />
                        </CardContent>
                    </Card>
                    <p class="mt-4 text-sm text-muted-foreground italic">
                        Figure 1. Citation rates by industry vertical. Industries are sorted by citation rate. Green bars indicate rates above 75%, blue above 50%, amber above 33%, and red below 33%.
                    </p>
                    <div class="mt-6 overflow-x-auto rounded-lg border">
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr class="border-b bg-muted/50">
                                    <th class="px-4 py-3 font-semibold">Industry</th>
                                    <th class="px-4 py-3 font-semibold text-center">Sites (n)</th>
                                    <th class="px-4 py-3 font-semibold text-center">Avg GEO Score</th>
                                    <th class="px-4 py-3 font-semibold text-center">Citation Rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="ind in studyData.industries" :key="ind.name" class="border-b last:border-b-0 transition-colors hover:bg-muted/30">
                                    <td class="px-4 py-3 font-medium">{{ ind.name }}</td>
                                    <td class="px-4 py-3 text-center text-muted-foreground">{{ ind.count }}</td>
                                    <td class="px-4 py-3 text-center text-muted-foreground">{{ ind.avgScore }}</td>
                                    <td class="px-4 py-3 text-center font-semibold" :class="ind.citationRate >= 50 ? 'text-green-600 dark:text-green-400' : ind.citationRate >= 33 ? 'text-amber-600 dark:text-amber-400' : 'text-red-600 dark:text-red-400'">{{ ind.citationRate }}%</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <Separator />

            <!-- Finding 2: Pillar Analysis -->
            <section class="py-12 sm:py-16">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <h2 class="text-2xl font-bold tracking-tight sm:text-3xl mb-2">Finding 2: Which of the 12 GEO pillars predict citations?</h2>
                    <p class="text-muted-foreground mb-8 leading-relaxed">
                        We evaluated all 12 GEO pillars to identify which ones most strongly predict AI citation likelihood. For each pillar, we split sites into "high" (score &ge;50%) and "low" (&lt;50%) groups and compared citation rates. Three pillars emerged as clear winners: <strong class="text-foreground">Answerability</strong>, <strong class="text-foreground">Citation Quality</strong>, and <strong class="text-foreground">Definitions</strong>. The remaining pillars showed weak, neutral, or even negative correlations.
                    </p>
                    <div class="overflow-x-auto rounded-lg border">
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr class="border-b bg-muted/50">
                                    <th class="px-4 py-3 font-semibold">GEO Pillar</th>
                                    <th class="px-4 py-3 font-semibold text-center">Direction</th>
                                    <th class="px-4 py-3 font-semibold">Interpretation</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="p in studyData.pillarAnalysis.highVsLow" :key="p.pillar" class="border-b last:border-b-0 transition-colors hover:bg-muted/30">
                                    <td class="px-4 py-3 font-medium">{{ p.pillar }}</td>
                                    <td class="px-4 py-3 text-center font-bold" :class="p.lift > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                                        {{ p.lift > 0 ? 'Positive' : (p.lift < 0 ? 'Negative' : 'Neutral') }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-muted-foreground">{{ p.insight }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p class="mt-4 text-sm text-muted-foreground italic">
                        Table 2. Citation lift by pillar. The top three pillars (Answerability, Citation Quality, Definitions) show consistent, meaningful positive lift. The bottom five pillars show no positive signal.
                    </p>
                    <Card class="mt-6 border-l-4 border-l-amber-500 bg-amber-50/30 dark:bg-amber-950/10">
                        <CardContent class="p-5 space-y-2">
                            <p class="text-sm leading-relaxed">
                                <strong class="text-foreground">Follow-up 1:</strong> The negative E-E-A-T finding above was worth isolating from possible content-type confounds. We ran a controlled 2×2 study to test it.
                                <Link href="/blog/eeat-content-type-study" class="font-medium text-primary hover:underline">
                                    Read the E-E-A-T &amp; content type follow-up &rarr;
                                </Link>
                            </p>
                            <p class="text-sm leading-relaxed">
                                <strong class="text-foreground">Follow-up 2:</strong> Single-turn citation isn't the same as commercial outcome. We ran a 4-stage shopping-journey study across 40 ecommerce brands and updated the algorithm with a Recommendation Readiness Score.
                                <Link href="/blog/ecommerce-recommendation-survival" class="font-medium text-primary hover:underline">
                                    Read the ecommerce recommendation-survival study &rarr;
                                </Link>
                            </p>
                            <p class="text-sm leading-relaxed">
                                <strong class="text-foreground">Cross-study synthesis:</strong> Six findings that held across all of our research, with practical implications for what to optimize.
                                <Link href="/blog/what-predicts-ai-citations" class="font-medium text-primary hover:underline">
                                    Read the synthesis &rarr;
                                </Link>
                            </p>
                        </CardContent>
                    </Card>
                </div>
            </section>

            <Separator />

            <!-- Finding 3: Platform Parity -->
            <section class="py-12 sm:py-16">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <h2 class="text-2xl font-bold tracking-tight sm:text-3xl mb-2">Finding 3: Platform citation distribution</h2>
                    <p class="text-muted-foreground mb-8 leading-relaxed">
                        All three AI platforms cited sites at similar rates. ChatGPT cited 27 domains (44.3%), Claude cited 24 (39.3%), and Perplexity cited 23 (37.7%). This convergence suggests that the content signals AI platforms use for citation selection are similar — <strong class="text-foreground">optimizing for one platform effectively optimizes for all of them</strong>.
                    </p>
                    <div class="grid gap-6 sm:grid-cols-2">
                        <Card>
                            <CardContent class="p-6">
                                <Doughnut :data="platformChartData" :options="platformChartOptions" />
                            </CardContent>
                        </Card>
                        <div class="space-y-4">
                            <Card v-for="[name, count] in [['ChatGPT (OpenAI)', studyData.platforms.openai], ['Perplexity AI', studyData.platforms.perplexity], ['Claude (Anthropic)', studyData.platforms.claude]]" :key="name">
                                <CardContent class="p-4 flex items-center justify-between">
                                    <div>
                                        <div class="font-semibold">{{ name }}</div>
                                        <div class="text-sm text-muted-foreground">{{ count }} of {{ studyData.total }} sites cited</div>
                                    </div>
                                    <div class="text-2xl font-bold text-primary">{{ ((count / studyData.total) * 100).toFixed(1) }}%</div>
                                </CardContent>
                            </Card>
                            <Card class="bg-muted/50">
                                <CardContent class="p-4">
                                    <p class="text-sm text-muted-foreground leading-relaxed">
                                        <strong class="text-foreground">Practical implication:</strong> You do not need to optimize separately for each AI platform. Content that is clear, well-cited, and directly answerable performs consistently across all three.
                                    </p>
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                </div>
            </section>

            <Separator />

            <!-- PHASE 2 HEADER -->
            <section class="py-10 sm:py-12 bg-blue-500/5 dark:bg-blue-500/5">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 text-center">
                    <Badge class="mb-3 text-sm px-4 py-1 bg-blue-600 text-white dark:bg-blue-500">Phase 2</Badge>
                    <h2 class="text-2xl font-bold tracking-tight sm:text-3xl">Algorithm Update: Rebalancing pillar weights</h2>
                    <p class="mt-3 max-w-2xl mx-auto text-muted-foreground leading-relaxed">
                        Phase 1 revealed which pillars matter and which don't. Phase 2 rebalanced the GEO scoring algorithm to reflect reality — increasing weight for the three proven predictors and reducing weight on pillars that showed no correlation with citations.
                    </p>
                </div>
            </section>

            <Separator />

            <!-- Finding 4: Algorithm Rebalancing -->
            <section class="py-12 sm:py-16">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <h2 class="text-2xl font-bold tracking-tight sm:text-3xl mb-2">Finding 4: Algorithm rebalancing results</h2>
                    <p class="text-muted-foreground mb-8 leading-relaxed">
                        Based on Phase 1 findings, we rebalanced the GEO scoring algorithm — increasing weight on the pillars that demonstrably predict citations and reducing weight on those that don't. The rebalanced algorithm was then used to re-scan all sites and re-run citation checks. The change preserved the same set of pillars; only the weights moved, and the directional changes are summarized below.
                    </p>

                    <div class="overflow-x-auto rounded-lg border mb-6">
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr class="border-b bg-muted/50">
                                    <th class="px-4 py-3 font-semibold">Pillar</th>
                                    <th class="px-4 py-3 font-semibold text-center">Weight change (first → second algorithm version)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="p in v1VsV2Pillars" :key="p.pillar" class="border-b last:border-b-0 transition-colors hover:bg-muted/30">
                                    <td class="px-4 py-3 font-medium">{{ p.pillar }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <span v-if="p.v2Weight > p.v1Weight" class="text-green-600 dark:text-green-400 font-semibold">Increased</span>
                                        <span v-else-if="p.v2Weight < p.v1Weight" class="text-amber-600 dark:text-amber-400 font-semibold">Decreased</span>
                                        <span v-else class="text-muted-foreground">Unchanged</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p class="text-sm text-muted-foreground italic mb-8">
                        Table 3. Direction of each pillar's weight change between the first and second versions of the GEO scoring algorithm. Specific weight values are not published.
                    </p>

                    <Card class="bg-muted/50">
                        <CardContent class="p-6">
                            <h3 class="font-semibold mb-2">What Phase 2 told us</h3>
                            <p class="text-sm text-muted-foreground leading-relaxed">
                                The rebalanced algorithm improved individual pillar correlations. But we wanted to go further — rather than averaging 12 pillars where only 3 are strong predictors, we asked: <strong class="text-foreground">what if we built a focused score from just the three pillars that matter most?</strong> That question led directly to Phase 3 and the Citation Readiness Score.
                            </p>
                        </CardContent>
                    </Card>
                </div>
            </section>

            <Separator />

            <!-- PHASE 3 HEADER -->
            <section class="py-10 sm:py-12 bg-green-500/5 dark:bg-green-500/5">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 text-center">
                    <Badge class="mb-3 text-sm px-4 py-1 bg-green-600 text-white dark:bg-green-500">Phase 3</Badge>
                    <h2 class="text-2xl font-bold tracking-tight sm:text-3xl">Validation: The Citation Readiness Score</h2>
                    <p class="mt-3 max-w-2xl mx-auto text-muted-foreground leading-relaxed">
                        Phase 2 improved individual pillar accuracy. Phase 3 went further — building a focused Citation Readiness Score from the three proven predictive pillars and testing it with a fresh round of 180+ citation checks. The results validated the approach.
                    </p>
                </div>
            </section>

            <Separator />

            <!-- Finding 5: CR Score vs GEO Score -->
            <section class="py-12 sm:py-16">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <h2 class="text-2xl font-bold tracking-tight sm:text-3xl mb-2">Finding 5: The Citation Readiness Score — a focused predictor</h2>
                    <p class="text-muted-foreground mb-8 leading-relaxed">
                        Building on what Phase 1 and 2 taught us, we created a <strong class="text-foreground">Citation Readiness (CR) Score</strong> using the three empirically-proven predictive pillars: Answerability (40% weight), Citation Quality (35%), and Definitions (25%). The CR Score gives users a focused, actionable metric for the content signals they can directly improve. The results validated this approach.
                    </p>

                    <div class="grid gap-4 sm:grid-cols-2 mb-8">
                        <Card class="border-l-4 border-l-red-500 dark:border-l-red-400">
                            <CardContent class="p-6 text-center">
                                <div class="text-sm font-semibold text-muted-foreground mb-2">GEO Score Gap (Cited - Not Cited)</div>
                                <div class="text-4xl font-bold text-red-600 dark:text-red-400">-3.1</div>
                                <div class="text-sm text-muted-foreground mt-2">Wrong direction. Cited sites average <em>lower</em> than non-cited.</div>
                            </CardContent>
                        </Card>
                        <Card class="border-l-4 border-l-green-500 dark:border-l-green-400">
                            <CardContent class="p-6 text-center">
                                <div class="text-sm font-semibold text-muted-foreground mb-2">CR Score Gap (Cited - Not Cited)</div>
                                <div class="text-4xl font-bold text-green-600 dark:text-green-400">+4.6</div>
                                <div class="text-sm text-muted-foreground mt-2">Correct direction. Cited sites average <em>higher</em>.</div>
                            </CardContent>
                        </Card>
                    </div>

                    <Card class="border-l-4 border-l-primary mb-8">
                        <CardContent class="p-6">
                            <div class="text-center">
                                <div class="text-sm font-semibold text-muted-foreground mb-2">CR Score Validation</div>
                                <div class="text-base text-foreground">Sites with a higher Citation Readiness Score were cited substantially more often than sites with a lower score across the validation round of checks.</div>
                            </div>
                        </CardContent>
                    </Card>

                    <h3 class="text-lg font-semibold mb-4">Citation rate by CR Score grade</h3>
                    <p class="text-muted-foreground mb-6 leading-relaxed">
                        Breaking the CR Score into four grade bands reveals a clear gradient — the higher the CR grade, the more likely a site is to be cited by AI platforms. The one exception is "Very Low" scoring slightly above "Low," which we attribute to small sample noise and brand override effects.
                    </p>
                    <Card>
                        <CardContent class="p-6">
                            <Bar :data="crScoreChartData" :options="crScoreChartOptions" />
                        </CardContent>
                    </Card>
                    <p class="mt-4 text-sm text-muted-foreground italic">
                        Figure 3. Citation rate by CR Score grade. High-grade sites (66.7%) are cited more than twice as often as Low-grade sites (30.0%). This is the gradient that the overall GEO score fails to produce.
                    </p>

                    <div class="mt-6 overflow-x-auto rounded-lg border">
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr class="border-b bg-muted/50">
                                    <th class="px-4 py-3 font-semibold">CR Score Grade</th>
                                    <th class="px-4 py-3 font-semibold text-center">Citation Rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="g in studyData.crScore.grades" :key="g.label" class="border-b last:border-b-0 transition-colors hover:bg-muted/30">
                                    <td class="px-4 py-3 font-medium">{{ g.label }}</td>
                                    <td class="px-4 py-3 text-center font-bold" :class="g.rate >= 60 ? 'text-green-600 dark:text-green-400' : g.rate >= 40 ? 'text-blue-600 dark:text-blue-400' : 'text-amber-600 dark:text-amber-400'">{{ g.rate }}%</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <Separator />

            <!-- Finding 6: Content Type Detection -->
            <section class="py-12 sm:py-16">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <h2 class="text-2xl font-bold tracking-tight sm:text-3xl mb-2">Finding 6: Content type determines citation ceiling</h2>
                    <p class="text-muted-foreground mb-8 leading-relaxed">
                        Phase 1 revealed that industry matters more than any pillar. Phase 3 confirmed that this is fundamentally a <strong class="text-foreground">content type problem</strong>. Informational content ("how to find a doctor," "what is compound interest") gets cited at dramatically higher rates than transactional content ("best CRM software," "best running shoes"). This finding led us to build content type detection into every scan.
                    </p>

                    <div class="grid gap-4 sm:grid-cols-3 mb-6">
                        <Card class="border-l-4 border-l-green-500 dark:border-l-green-400">
                            <CardContent class="p-5">
                                <div class="text-2xl font-bold text-green-600 dark:text-green-400">83%+</div>
                                <div class="text-sm font-semibold mt-1">Informational Queries</div>
                                <p class="text-sm text-muted-foreground mt-2 leading-relaxed">
                                    Factual, how-to, and educational queries. Travel, healthcare, finance. AI confidently cites authoritative sources for factual answers.
                                </p>
                            </CardContent>
                        </Card>
                        <Card class="border-l-4 border-l-amber-500">
                            <CardContent class="p-5">
                                <div class="text-2xl font-bold text-amber-600 dark:text-amber-400">33-55%</div>
                                <div class="text-sm font-semibold mt-1">Mixed/Comparison Queries</div>
                                <p class="text-sm text-muted-foreground mt-2 leading-relaxed">
                                    Review and comparison queries. News, education, marketing. AI cites some sources but hedges on subjective elements.
                                </p>
                            </CardContent>
                        </Card>
                        <Card class="border-l-4 border-l-red-500 dark:border-l-red-400">
                            <CardContent class="p-5">
                                <div class="text-2xl font-bold text-red-600 dark:text-red-400">6-22%</div>
                                <div class="text-sm font-semibold mt-1">Transactional Queries</div>
                                <p class="text-sm text-muted-foreground mt-2 leading-relaxed">
                                    "Best X" product queries. SaaS, ecommerce, gaming. AI avoids recommending specific products, preferring to list options without strong endorsement.
                                </p>
                            </CardContent>
                        </Card>
                    </div>
                    <Card class="bg-muted/50">
                        <CardContent class="p-6">
                            <p class="text-sm text-muted-foreground leading-relaxed">
                                <strong class="text-foreground">The practical implication:</strong> If you're a SaaS company with only product pages, your citation ceiling is roughly 13%. Creating educational, informational content — guides, definitions, how-to articles — can lift your ceiling to 50%+ because you're shifting the query type from transactional to informational.
                            </p>
                        </CardContent>
                    </Card>
                </div>
            </section>

            <Separator />

            <!-- Finding 7: Brand vs Score -->
            <section class="py-12 sm:py-16">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <h2 class="text-2xl font-bold tracking-tight sm:text-3xl mb-2">Finding 7: How brand awareness and content quality work together</h2>
                    <p class="text-muted-foreground mb-8 leading-relaxed">
                        Brand recognition gives well-known sites a built-in advantage — AI has encountered them extensively in training data. But brand isn't destiny. Smaller companies with strong informational content regularly earn citations in our data, especially for specific, educational queries where expertise matters more than name recognition. The most effective AI visibility strategy combines GEO-optimized content with ongoing brand building through review sites, forums, and industry publications.
                    </p>

                    <h3 class="text-lg font-semibold mb-4">Established brands — cited across all platforms</h3>
                    <div class="overflow-x-auto rounded-lg border mb-8">
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr class="border-b bg-muted/50">
                                    <th class="px-4 py-3 font-semibold">Domain</th>
                                    <th class="px-4 py-3 font-semibold text-center">GEO Score</th>
                                    <th class="px-4 py-3 font-semibold">Industry</th>
                                    <th class="px-4 py-3 font-semibold">Why cited despite score?</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="border-b hover:bg-muted/30">
                                    <td class="px-4 py-3 font-medium">tripadvisor.com</td>
                                    <td class="px-4 py-3 text-center font-semibold text-red-600 dark:text-red-400">18</td>
                                    <td class="px-4 py-3 text-muted-foreground">Travel</td>
                                    <td class="px-4 py-3 text-sm text-muted-foreground">Category-defining brand, massive training data presence</td>
                                </tr>
                                <tr class="border-b hover:bg-muted/30">
                                    <td class="px-4 py-3 font-medium">kayak.com</td>
                                    <td class="px-4 py-3 text-center font-semibold text-red-600 dark:text-red-400">56</td>
                                    <td class="px-4 py-3 text-muted-foreground">Travel</td>
                                    <td class="px-4 py-3 text-sm text-muted-foreground">Category leader, universally recognized in travel search</td>
                                </tr>
                                <tr class="border-b last:border-b-0 hover:bg-muted/30">
                                    <td class="px-4 py-3 font-medium">webmd.com</td>
                                    <td class="px-4 py-3 text-center font-semibold text-amber-600 dark:text-amber-400">72</td>
                                    <td class="px-4 py-3 text-muted-foreground">Healthcare</td>
                                    <td class="px-4 py-3 text-sm text-muted-foreground">Dominant health information brand, decades of authority</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <h3 class="text-lg font-semibold mb-4">High score, 0% cited — brand isn't strong enough</h3>
                    <div class="overflow-x-auto rounded-lg border">
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr class="border-b bg-muted/50">
                                    <th class="px-4 py-3 font-semibold">Domain</th>
                                    <th class="px-4 py-3 font-semibold text-center">GEO Score</th>
                                    <th class="px-4 py-3 font-semibold text-center">Grade</th>
                                    <th class="px-4 py-3 font-semibold">Industry</th>
                                    <th class="px-4 py-3 font-semibold">Why not cited?</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="border-b hover:bg-muted/30">
                                    <td class="px-4 py-3 font-medium">peloton.com</td>
                                    <td class="px-4 py-3 text-center font-semibold">71</td>
                                    <td class="px-4 py-3 text-center font-bold text-orange-600 dark:text-orange-400">D</td>
                                    <td class="px-4 py-3 text-muted-foreground">Fitness</td>
                                    <td class="px-4 py-3 text-sm text-muted-foreground">Product/transactional query in competitive category</td>
                                </tr>
                                <tr class="border-b hover:bg-muted/30">
                                    <td class="px-4 py-3 font-medium">airtable.com</td>
                                    <td class="px-4 py-3 text-center font-semibold">70</td>
                                    <td class="px-4 py-3 text-center font-bold text-orange-600 dark:text-orange-400">D</td>
                                    <td class="px-4 py-3 text-muted-foreground">SaaS</td>
                                    <td class="px-4 py-3 text-sm text-muted-foreground">SaaS niche — AI cites larger competitors</td>
                                </tr>
                                <tr class="border-b last:border-b-0 hover:bg-muted/30">
                                    <td class="px-4 py-3 font-medium">postman.com</td>
                                    <td class="px-4 py-3 text-center font-semibold">70</td>
                                    <td class="px-4 py-3 text-center font-bold text-orange-600 dark:text-orange-400">D</td>
                                    <td class="px-4 py-3 text-muted-foreground">SaaS</td>
                                    <td class="px-4 py-3 text-sm text-muted-foreground">Developer tool — AI prefers broader platform recommendations</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <Separator />

            <!-- Site Size Analysis -->
            <section class="py-12 sm:py-16">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <h2 class="text-2xl font-bold tracking-tight sm:text-3xl mb-2">Site size and citation rates</h2>
                    <p class="text-muted-foreground mb-8 leading-relaxed">
                        Large sites were cited nearly twice as often as medium sites (49.2% vs 25.0%), despite having <em>lower</em> average GEO scores (79.2 vs 84.6). This confirms that brand size and training data presence are significant confounding variables. For mid-size and smaller companies, this makes content optimization even more critical — they cannot rely on brand recognition alone.
                    </p>
                    <div class="grid gap-4 sm:grid-cols-3">
                        <Card>
                            <CardContent class="p-6 text-center">
                                <Globe class="mx-auto mb-3 h-8 w-8 text-muted-foreground" aria-hidden="true" />
                                <div class="text-lg font-semibold">Large Sites</div>
                                <div class="mt-2 text-3xl font-bold text-primary">{{ studyData.sizeStats.large.citationRate }}%</div>
                                <div class="text-sm text-muted-foreground">citation rate</div>
                                <div class="mt-2 text-sm text-muted-foreground">
                                    n={{ studyData.sizeStats.large.count }} |
                                    avg score: {{ studyData.sizeStats.large.avgScore }}
                                </div>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardContent class="p-6 text-center">
                                <Globe class="mx-auto mb-3 h-8 w-8 text-muted-foreground" aria-hidden="true" />
                                <div class="text-lg font-semibold">Medium Sites</div>
                                <div class="mt-2 text-3xl font-bold text-primary">{{ studyData.sizeStats.medium.citationRate }}%</div>
                                <div class="text-sm text-muted-foreground">citation rate</div>
                                <div class="mt-2 text-sm text-muted-foreground">
                                    n={{ studyData.sizeStats.medium.count }} |
                                    avg score: {{ studyData.sizeStats.medium.avgScore }}
                                </div>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardContent class="p-6 text-center">
                                <Globe class="mx-auto mb-3 h-8 w-8 text-muted-foreground" aria-hidden="true" />
                                <div class="text-lg font-semibold">Small Sites</div>
                                <div class="mt-2 text-3xl font-bold text-primary">{{ studyData.sizeStats.small.citationRate }}%</div>
                                <div class="text-sm text-muted-foreground">citation rate</div>
                                <div class="mt-2 text-sm text-muted-foreground">
                                    n={{ studyData.sizeStats.small.count }} |
                                    avg score: {{ studyData.sizeStats.small.avgScore }}
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </section>

            <Separator />

            <!-- Top Cited Sites -->
            <section class="py-12 sm:py-16">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <h2 class="text-2xl font-bold tracking-tight sm:text-3xl mb-2">Most cited sites (100% citation rate)</h2>
                    <p class="text-muted-foreground mb-6 leading-relaxed">
                        These 10 sites were cited by all three AI platforms. They span a wide range of GEO scores — from TripAdvisor at 18 to Bankrate at 118 — reinforcing that citation is multifactorial. Note TripAdvisor's score of 18: this is a homepage with minimal text content, yet it gets cited 100% of the time because of overwhelming brand recognition and training data presence.
                    </p>
                    <div class="overflow-x-auto rounded-lg border">
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr class="border-b bg-muted/50">
                                    <th class="px-4 py-3 font-semibold">Domain</th>
                                    <th class="px-4 py-3 font-semibold text-center">GEO Score</th>
                                    <th class="px-4 py-3 font-semibold">Industry</th>
                                    <th class="px-4 py-3 font-semibold text-center">Cited</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="site in studyData.topCited" :key="site.domain" class="border-b last:border-b-0 transition-colors hover:bg-muted/30">
                                    <td class="px-4 py-3 font-medium">{{ site.domain }}</td>
                                    <td class="px-4 py-3 text-center font-semibold">{{ site.score }}</td>
                                    <td class="px-4 py-3 text-muted-foreground">{{ site.industry }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="flex items-center justify-center gap-1">
                                            <CheckCircle class="h-4 w-4 text-green-500 dark:text-green-400" title="ChatGPT" />
                                            <CheckCircle class="h-4 w-4 text-green-500 dark:text-green-400" title="Perplexity" />
                                            <CheckCircle class="h-4 w-4 text-green-500 dark:text-green-400" title="Claude" />
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <Separator />

            <!-- Not Cited Sites -->
            <section class="py-12 sm:py-16">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <h2 class="text-2xl font-bold tracking-tight sm:text-3xl mb-2">Sites with room to improve</h2>
                    <p class="text-muted-foreground mb-6 leading-relaxed">
                        These sites were not cited in our test queries. Most are product-focused pages that could improve by creating educational, informational content alongside their product pages. The opportunity: target informational queries where content quality differentiates, rather than relying solely on product pages where brand recognition dominates.
                    </p>
                    <div class="overflow-x-auto rounded-lg border">
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr class="border-b bg-muted/50">
                                    <th class="px-4 py-3 font-semibold">Domain</th>
                                    <th class="px-4 py-3 font-semibold text-center">GEO Score</th>
                                    <th class="px-4 py-3 font-semibold text-center">Grade</th>
                                    <th class="px-4 py-3 font-semibold">Industry</th>
                                    <th class="px-4 py-3 font-semibold text-center">Cited</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="site in studyData.notCitedSites" :key="site.domain" class="border-b last:border-b-0 transition-colors hover:bg-muted/30">
                                    <td class="px-4 py-3 font-medium">{{ site.domain }}</td>
                                    <td class="px-4 py-3 text-center font-semibold">{{ site.score }}</td>
                                    <td class="px-4 py-3 text-center font-bold" :class="gradeColor(site.grade)">{{ site.grade }}</td>
                                    <td class="px-4 py-3 text-muted-foreground">{{ site.industry }}</td>
                                    <td class="px-4 py-3 text-center"><XCircle class="mx-auto h-4 w-4 text-red-500 dark:text-red-400" /></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <Separator />

            <!-- What We Built -->
            <section class="py-12 sm:py-16">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <h2 class="text-2xl font-bold tracking-tight sm:text-3xl mb-6">What we built from this research</h2>
                    <p class="text-muted-foreground mb-8 leading-relaxed">
                        We didn't publish this study and move on. The findings changed the product. If the data shows that the overall GEO score is a weak predictor but specific pillars and content types matter enormously, the tool should reflect that reality.
                    </p>

                    <div class="grid gap-6 sm:grid-cols-3 mb-8">
                        <Card class="border-l-4 border-l-primary">
                            <CardContent class="p-6">
                                <BarChart3 class="h-8 w-8 text-primary mb-3" aria-hidden="true" />
                                <h3 class="font-semibold mb-2">Citation Readiness Score</h3>
                                <p class="text-sm text-muted-foreground leading-relaxed">
                                    A focused metric calculated from only the three empirically-proven predictive pillars: Answerability, Citation Quality, and Definitions. High CR sites are cited substantially more often than low CR sites, and unlike the overall GEO score the CR Score gap points in the correct direction. This is now prominently displayed alongside your overall GEO score.
                                </p>
                            </CardContent>
                        </Card>
                        <Card class="border-l-4 border-l-blue-500 dark:border-l-blue-400">
                            <CardContent class="p-6">
                                <Globe class="h-8 w-8 text-blue-500 dark:text-blue-400 mb-3" aria-hidden="true" />
                                <h3 class="font-semibold mb-2">Content Type Detection</h3>
                                <p class="text-sm text-muted-foreground leading-relaxed">
                                    Every scan now detects whether your content is informational, transactional, or educational — and tells you what that means for your citation ceiling. Informational content in healthcare and travel gets cited 83%+ of the time. Transactional SaaS pages get cited 13%. You'll get recommendations specific to your content type.
                                </p>
                            </CardContent>
                        </Card>
                        <Card class="border-l-4 border-l-green-500 dark:border-l-green-400">
                            <CardContent class="p-6">
                                <TrendingUp class="h-8 w-8 text-green-500 dark:text-green-400 mb-3" aria-hidden="true" />
                                <h3 class="font-semibold mb-2">Industry Benchmarks</h3>
                                <p class="text-sm text-muted-foreground leading-relaxed">
                                    Your scan results now include expected citation rates based on our study data across 17 industries. Instead of an abstract score, you'll see how your site compares to others in your specific industry — because a 75% citation rate in SaaS is exceptional while it's average in healthcare.
                                </p>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </section>

            <Separator />

            <!-- Key Definitions -->
            <section class="py-12 sm:py-16">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <h2 class="text-2xl font-bold tracking-tight sm:text-3xl mb-6">Key definitions</h2>
                    <p class="text-muted-foreground mb-8 leading-relaxed">
                        The following terms are used throughout this study. Clear definitions support accurate interpretation of findings.
                    </p>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <Card>
                            <CardContent class="p-5">
                                <h3 class="font-semibold text-primary mb-1">Generative Engine Optimization (GEO)</h3>
                                <p class="text-sm text-muted-foreground leading-relaxed">GEO is the practice of optimizing web content so that AI search engines can understand, trust, and cite it. It encompasses content structure, definitions, authority signals, machine readability, and answerability.</p>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardContent class="p-5">
                                <h3 class="font-semibold text-primary mb-1">Citation Readiness Score (CR Score)</h3>
                                <p class="text-sm text-muted-foreground leading-relaxed">A focused metric built from three empirically-proven predictive pillars: Answerability (40%), Citation Quality (35%), and Definitions (25%). Unlike the overall GEO score, the CR Score shows a meaningful positive correlation with actual AI citation rates (+4.6 gap, correct direction).</p>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardContent class="p-5">
                                <h3 class="font-semibold text-primary mb-1">AI Citation</h3>
                                <p class="text-sm text-muted-foreground leading-relaxed">An AI citation occurs when an AI search platform (ChatGPT, Perplexity, Claude) references a website or brand in its generated response. Citations appear as inline mentions, source links, or direct recommendations.</p>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardContent class="p-5">
                                <h3 class="font-semibold text-primary mb-1">GEO Score</h3>
                                <p class="text-sm text-muted-foreground leading-relaxed">A composite numerical score (0-150 with 12 pillars) measuring how well a web page is optimized for AI comprehension. Our research found this is a weak overall predictor of citations (-3.1 gap), which is why we developed the CR Score as a focused alternative.</p>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardContent class="p-5">
                                <h3 class="font-semibold text-primary mb-1">Citation Rate</h3>
                                <p class="text-sm text-muted-foreground leading-relaxed">The percentage of AI platforms that cited a given domain when asked a relevant query. A site cited by all 3 platforms has a 100% citation rate. A site cited by 1 of 3 has a 33.3% rate.</p>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardContent class="p-5">
                                <h3 class="font-semibold text-primary mb-1">Lift</h3>
                                <p class="text-sm text-muted-foreground leading-relaxed">The relative direction in citation rate when a pillar scores high (&ge;50%) vs low (&lt;50%). Positive means high-scoring sites are cited more often than low-scoring sites.</p>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </section>

            <Separator />

            <!-- FAQ Section -->
            <section class="py-12 sm:py-16">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <h2 class="mb-8 text-2xl font-bold tracking-tight sm:text-3xl">
                        Frequently asked questions
                    </h2>
                    <div class="space-y-3">
                        <div
                            v-for="(item, index) in faqItems"
                            :key="index"
                            class="rounded-lg border"
                        >
                            <button
                                class="flex w-full items-center justify-between px-6 py-4 text-left font-medium transition-colors hover:bg-muted/50 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 rounded-lg"
                                :aria-expanded="openFaqIndex === index"
                                :aria-controls="`faq-panel-${index}`"
                                @click="toggleFaq(index)"
                            >
                                <span>{{ item.question }}</span>
                                <ChevronDown
                                    class="h-5 w-5 shrink-0 text-muted-foreground transition-transform duration-200"
                                    :class="{ 'rotate-180': openFaqIndex === index }"
                                    aria-hidden="true"
                                />
                            </button>
                            <div
                                v-show="openFaqIndex === index"
                                :id="`faq-panel-${index}`"
                                role="region"
                                class="px-6 pb-4"
                            >
                                <p class="text-muted-foreground leading-relaxed">{{ item.answer }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <Separator />

            <!-- CTA -->
            <section class="py-16 sm:py-20">
                <div class="mx-auto max-w-4xl px-4 text-center sm:px-6 lg:px-8">
                    <h2 class="text-2xl font-bold tracking-tight sm:text-3xl">
                        Check your Citation Readiness Score
                    </h2>
                    <p class="mx-auto mt-4 max-w-xl text-muted-foreground">
                        Run a free GEO scan to see your CR Score, GEO Score, and industry benchmarks. Get recommendations calibrated against real citation data from this three-phase study.
                    </p>
                    <div class="mt-8">
                        <Link :href="register().url">
                            <Button size="lg" class="gap-2">
                                Run a free GEO scan
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
