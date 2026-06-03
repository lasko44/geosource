<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import SkipNav from '@/components/resources/SkipNav.vue';
import ResourceHeader from '@/components/resources/ResourceHeader.vue';
import ResourceFooter from '@/components/resources/ResourceFooter.vue';
import ResourceBreadcrumb from '@/components/resources/ResourceBreadcrumb.vue';
import { BookOpen, Calendar, Building2, Search, TrendingDown, BarChart3, Layers, GitCompare, Check, X, Minus, Microscope, Globe, ShoppingCart, MessageCircle } from 'lucide-vue-next';
import { Bar } from 'vue-chartjs';
import {
    Chart as ChartJS,
    LinearScale,
    BarElement,
    Tooltip,
    Legend,
    Title,
    CategoryScale,
} from 'chart.js';
import { computed, onMounted, ref } from 'vue';

ChartJS.register(LinearScale, BarElement, Tooltip, Legend, Title, CategoryScale);

const canonicalUrl = 'https://geosource.ai/blog/what-predicts-ai-citations';

const breadcrumbItems = [
    { label: 'Resources', href: '/resources' },
    { label: 'What predicts AI citations' },
];

// Research-at-a-glance — summary stats across the entire research line
const researchStats = [
    { value: '4', label: 'Studies conducted', icon: Microscope },
    { value: '125+', label: 'Brands & sites tested', icon: Globe },
    { value: '1,500+', label: 'AI citation checks run', icon: BarChart3 },
    { value: '3', label: 'AI platforms compared', icon: GitCompare },
];

// Which study supports which finding. Each cell is one of:
//   'strong'  — finding was directly demonstrated by this study
//   'weak'    — finding was directionally consistent but not the focus
//   'na'      — finding wasn't tested by this study
const studyShorthand = [
    { key: 'citation', label: 'Citation study', icon: BarChart3 },
    { key: 'eeat', label: 'E-E-A-T 2×2', icon: Microscope },
    { key: 'ecommerce', label: 'Ecommerce', icon: ShoppingCart },
    { key: 'multiturn', label: 'Multi-turn', icon: MessageCircle },
];

const supportMatrix = [
    { finding: 'Brand recognition swamps page quality', citation: 'strong', eeat: 'weak', ecommerce: 'strong', multiturn: 'weak' },
    { finding: 'Query phrasing matters more than page content', citation: 'na', eeat: 'strong', ecommerce: 'weak', multiturn: 'na' },
    { finding: 'E-E-A-T / Authority never predicts citation', citation: 'strong', eeat: 'strong', ecommerce: 'strong', multiturn: 'na' },
    { finding: 'Citation rate is the wrong top-line metric', citation: 'weak', eeat: 'na', ecommerce: 'strong', multiturn: 'na' },
    { finding: 'Content type sets the citation ceiling', citation: 'strong', eeat: 'strong', ecommerce: 'strong', multiturn: 'na' },
    { finding: 'Citations are mostly all-or-nothing', citation: 'weak', eeat: 'strong', ecommerce: 'strong', multiturn: 'strong' },
];

const findings = [
    {
        icon: Building2,
        title: 'Brand recognition swamps page quality',
        body: 'Across every study, well-known brands got cited even with weak on-page signals; lesser-known brands struggled to get cited even with strong ones. In our ecommerce work, brands with very low GEO scores routinely outperformed brands with very high ones. Page quality moves the needle, but brand familiarity is the bigger lever — and one that page-level optimization can\'t fix.',
    },
    {
        icon: Search,
        title: 'Query phrasing matters more than page content',
        body: 'When we changed the wording of a query — switching "is Mayo Clinic trustworthy" to "what makes a hospital trustworthy" — the same page\'s citation rate collapsed without any change to the page itself. Whether your content gets cited is mostly decided by how the query gets asked, not by anything you optimize on the page.',
    },
    {
        icon: TrendingDown,
        title: 'E-E-A-T signaling doesn\'t predict AI citation',
        body: 'Three independent studies — the original homepage study, the E-E-A-T 2×2 follow-up, and the ecommerce survival study — all showed the same thing. Visible E-E-A-T signals (author bylines, "expert reviewed by" labels, credential sidebars) don\'t make AI more likely to cite you. In the ecommerce study they actively hurt. Whatever those signals do for Google\'s framework, they aren\'t what AI assistants are reading.',
    },
    {
        icon: BarChart3,
        title: 'Citation rate is the wrong top-line metric',
        body: 'Being mentioned isn\'t the same as being recommended. Brands with identical citation rates can be treated completely differently — one as the AI\'s top pick, another buried in a list of competitors. The yes/no signal hides that. Our follow-up strength scoring separates active recommendations from neutral mentions.',
    },
    {
        icon: Layers,
        title: 'Content type sets the citation ceiling',
        body: 'Informational pages (definitions, how-tos, condition guides) get cited at dramatically higher rates than product or landing pages. Some queries don\'t produce sourced citations at all — AI just answers from training. Users can\'t change their product category, but they can choose what kinds of content to publish, and that\'s one of the highest-leverage levers we found.',
    },
    {
        icon: GitCompare,
        title: 'Platforms agree more than they disagree',
        body: 'When we asked the same query on ChatGPT, Perplexity, and Claude, they almost always agreed on which sources to cite. A page that gets cited by one platform usually gets cited by all; a page that\'s ignored by one is usually ignored by all. Per-platform optimization rarely pays off — per-query optimization does.',
    },
];

const studies = [
    {
        label: 'Study 1',
        title: 'The original GEO citation study',
        body: '61 sites across 17 industries, testing which content signals correlate with AI citations and building the first version of the GEO scoring model. Where we first noticed the brand-recognition confound and the surprising negative E-E-A-T result.',
        href: '/blog/geo-citation-study',
    },
    {
        label: 'Study 2',
        title: 'E-E-A-T &amp; content type follow-up',
        body: 'A controlled 2×2 follow-up to test whether the negative E-E-A-T result was a content-type confound. 40 URLs across healthcare vs SaaS, educational vs authority pages. We ran the study, found a query-design bug in the first attempt, redesigned, and reran — and the negative E-E-A-T direction survived the fix.',
        href: '/blog/eeat-content-type-study',
    },
    {
        label: 'Study 3',
        title: 'Ecommerce recommendation-survival study',
        body: '40 brands × 12 product categories × 4 shopping-journey stages. Tested how brands survive a multi-stage shopping conversation, then ran a follow-up strength classifier on every AI response to score recommendation strength beyond binary citation. The results shipped as the Recommendation Readiness Score.',
        href: '/blog/ecommerce-recommendation-survival',
    },
];
</script>

<template>
    <Head>
        <title>What predicts AI citations: six findings from our research line | GeoSource.ai</title>
        <meta name="description" content="Six patterns that held across multiple independent studies of how AI platforms cite and recommend content. Cross-study synthesis with links to the underlying research." />
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
                        <BookOpen class="mr-1.5 h-3 w-3" aria-hidden="true" />
                        Cross-study synthesis
                    </Badge>
                    <h1 class="text-3xl font-bold tracking-tight sm:text-4xl lg:text-5xl">
                        What actually predicts AI citations? Six findings.
                    </h1>
                    <p class="mt-4 max-w-3xl text-lg text-muted-foreground sm:text-xl leading-relaxed">
                        We ran four studies of how AI platforms cite and recommend content. A handful of patterns showed up in every one of them. Those are the patterns worth acting on. This page is the consolidated read, with links to each underlying study.
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

            <!-- Research at a glance -->
            <section class="py-12 sm:py-16">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <h2 class="text-2xl font-bold tracking-tight sm:text-3xl mb-6">Research at a glance</h2>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <Card v-for="s in researchStats" :key="s.label">
                            <CardContent class="p-5">
                                <component :is="s.icon" class="h-5 w-5 text-primary mb-3" aria-hidden="true" />
                                <div class="text-3xl font-bold">{{ s.value }}</div>
                                <div class="mt-1 text-xs text-muted-foreground">{{ s.label }}</div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </section>

            <Separator />

            <!-- The 6 findings -->
            <section class="py-12 sm:py-16">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <h2 class="text-2xl font-bold tracking-tight sm:text-3xl mb-2">The six findings</h2>
                    <p class="text-muted-foreground mb-8 max-w-3xl">
                        Each of these showed up across at least two of our independent studies. We're presenting them qualitatively here; specific numbers live on the individual study pages.
                    </p>

                    <div class="space-y-6">
                        <Card v-for="(f, i) in findings" :key="f.title" class="border-l-4 border-l-primary">
                            <CardContent class="p-6">
                                <div class="flex items-start gap-4">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary/10 text-sm font-bold text-primary">{{ i + 1 }}</div>
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-2">
                                            <component :is="f.icon" class="h-5 w-5 text-primary" aria-hidden="true" />
                                            <h3 class="text-lg font-semibold">{{ f.title }}</h3>
                                        </div>
                                        <p class="text-muted-foreground leading-relaxed">{{ f.body }}</p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </section>

            <Separator />

            <!-- Finding × Study support matrix -->
            <section class="py-12 sm:py-16">
                <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                    <h2 class="text-2xl font-bold tracking-tight sm:text-3xl mb-2">Which studies support which findings</h2>
                    <p class="text-muted-foreground mb-6 max-w-3xl">
                        A finding is only listed above if at least two independent studies pointed at it. This matrix shows where each finding showed up — a green check means the study tested it directly, a blue check means the study showed the same direction as a side observation, and a dash means the study didn't test that question.
                    </p>
                    <div class="overflow-x-auto rounded-lg border">
                        <table class="w-full text-sm">
                            <thead class="bg-muted/40 text-left">
                                <tr>
                                    <th class="px-3 py-3 font-medium">Finding</th>
                                    <th v-for="s in studyShorthand" :key="s.key" class="px-3 py-3 font-medium text-center">
                                        <div class="flex items-center justify-center gap-1.5">
                                            <component :is="s.icon" class="h-3.5 w-3.5 text-muted-foreground" aria-hidden="true" />
                                            <span class="text-xs">{{ s.label }}</span>
                                        </div>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="row in supportMatrix" :key="row.finding" class="border-t">
                                    <td class="px-3 py-3 font-medium">{{ row.finding }}</td>
                                    <td v-for="s in studyShorthand" :key="s.key" class="px-3 py-3 text-center">
                                        <span v-if="row[s.key as keyof typeof row] === 'strong'" class="inline-flex items-center justify-center h-6 w-6 rounded-full bg-green-500/15 text-green-600 dark:text-green-400">
                                            <Check class="h-3.5 w-3.5" aria-hidden="true" />
                                        </span>
                                        <span v-else-if="row[s.key as keyof typeof row] === 'weak'" class="inline-flex items-center justify-center h-6 w-6 rounded-full bg-blue-500/15 text-blue-600 dark:text-blue-400" title="directionally consistent">
                                            <Check class="h-3 w-3 opacity-60" aria-hidden="true" />
                                        </span>
                                        <span v-else class="inline-flex items-center justify-center h-6 w-6 rounded-full bg-muted text-muted-foreground" title="not tested">
                                            <Minus class="h-3 w-3" aria-hidden="true" />
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4 flex items-center gap-6 text-xs text-muted-foreground">
                        <span class="flex items-center gap-1.5"><span class="inline-block h-3 w-3 rounded-full bg-green-500/40"></span> Directly tested</span>
                        <span class="flex items-center gap-1.5"><span class="inline-block h-3 w-3 rounded-full bg-blue-500/40"></span> Side observation</span>
                        <span class="flex items-center gap-1.5"><span class="inline-block h-3 w-3 rounded-full bg-muted"></span> Not tested</span>
                    </div>
                </div>
            </section>

            <Separator />

            <!-- Underlying studies -->
            <section class="py-12 sm:py-16">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <h2 class="text-2xl font-bold tracking-tight sm:text-3xl mb-2">The studies behind these findings</h2>
                    <p class="text-muted-foreground mb-8 max-w-3xl">
                        Each finding above is sourced from at least one of these. Each study page includes its full methodology, limitations, and qualitative outcomes.
                    </p>

                    <div class="space-y-4">
                        <Card v-for="s in studies" :key="s.title">
                            <CardContent class="p-6">
                                <Badge variant="outline" class="mb-2">{{ s.label }}</Badge>
                                <h3 class="text-lg font-semibold mb-2">{{ s.title }}</h3>
                                <p class="text-muted-foreground leading-relaxed mb-3">{{ s.body }}</p>
                                <Link :href="s.href" class="text-sm font-medium text-primary hover:underline">
                                    Read the full study &rarr;
                                </Link>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </section>

            <Separator />

            <!-- What this means for users -->
            <section class="py-12 sm:py-16">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <h2 class="text-2xl font-bold tracking-tight sm:text-3xl mb-6">What this means if you're trying to get cited</h2>
                    <Card class="border-l-4 border-l-amber-500 bg-amber-50/30 dark:bg-amber-950/10">
                        <CardContent class="p-6 space-y-4 text-muted-foreground leading-relaxed">
                            <p>
                                <strong class="text-foreground">The biggest leverage is upstream of your page.</strong> How your audience phrases their queries, what category your page competes in, and how well-known your brand already is — these matter more than the on-page signals our scoring engine measures. If you optimize the page in isolation, you're working on the small lever.
                            </p>
                            <p>
                                <strong class="text-foreground">Pick the right queries to compete for.</strong> Brand-named queries ("who founded X", "is X trustworthy") almost always pull citations back to that brand. Concept queries ("what causes migraines", "what is CRM software") rarely do unless your page is genuinely the best answer. Both have a place in a content strategy, but they behave very differently.
                            </p>
                            <p>
                                <strong class="text-foreground">Don't over-index on E-E-A-T signaling.</strong> Author bylines and credential cues didn't predict AI citation in any of our studies. They may help with Google SERPs, but they aren't what AI platforms are reading. Focus content effort on the pillars that did show up positively.
                            </p>
                            <p>
                                <strong class="text-foreground">"Cited" isn't the goal — "actively recommended" is.</strong> Being named at position #8 in a list of competitors isn't the same outcome as being the top pick. Track the difference. Our Recommendation Readiness Score and follow-up strength analyzer are built around exactly this distinction.
                            </p>
                        </CardContent>
                    </Card>
                </div>
            </section>
        </main>

        <ResourceFooter />
    </div>
</template>
