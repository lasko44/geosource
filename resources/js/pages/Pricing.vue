<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Check, X, Sparkles, Zap, Building2, Globe, ArrowRight, Menu, Mail, BookOpen, Layers, Award, Code, MessageSquare, UserCheck, Quote, Bot, Clock, Type, HelpCircle, Image } from 'lucide-vue-next';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import ThemeSwitcher from '@/components/ThemeSwitcher.vue';
import { dashboard, login, register } from '@/routes';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

interface PlanWithLimits {
    name: string;
    description: string;
    price: number;
    interval: string;
    features: string[];
    popular?: boolean;
    limits: Record<string, number | boolean>;
}

interface Props {
    plans: Record<string, PlanWithLimits>;
    canRegister: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    canRegister: true,
});

// GEO Score Pillars by tier
const freePillars = [
    { name: 'Clear Definitions', points: 20, icon: BookOpen },
    { name: 'Structured Knowledge', points: 20, icon: Layers },
    { name: 'Topic Authority', points: 25, icon: Award },
    { name: 'Machine-Readable', points: 15, icon: Code },
    { name: 'Answerability', points: 20, icon: MessageSquare },
];

const proPillars = [
    { name: 'E-E-A-T Signals', points: 15, icon: UserCheck },
    { name: 'Citations & Sources', points: 12, icon: Quote },
    { name: 'AI Crawler Access', points: 8, icon: Bot },
];

const agencyPillars = [
    { name: 'Content Freshness', points: 10, icon: Clock },
    { name: 'Readability', points: 10, icon: Type },
    { name: 'Question Coverage', points: 10, icon: HelpCircle },
    { name: 'Multimedia', points: 10, icon: Image },
];

// Define the free tier for display
const freeTier = {
    name: 'Free',
    description: 'Get started with basic GEO analysis',
    price: 0,
    interval: 'month' as const,
    features: [
        '3 scans per month',
        '5 GEO pillars (100 pts max)',
        'Top 3 recommendations',
        '7-day scan history',
    ],
    limits: {
        scans_per_month: 3,
        history_days: 7,
        team_members: 0,
        competitor_tracking: 0,
        recommendations_shown: 3,
        api_access: false,
        white_label: false,
        scheduled_scans: false,
        pdf_export: false,
        bulk_scanning: false,
    },
};

// Feature comparison data
const featureCategories = [
    {
        name: 'GEO Score Analysis',
        features: [
            { name: 'GEO Score pillars', key: 'geo_pillars', format: 'pillars' },
            { name: 'Maximum score', key: 'max_score', format: 'points' },
            { name: 'Recommendations shown', key: 'recommendations_shown', format: 'number' },
        ],
    },
    {
        name: 'Scanning',
        features: [
            { name: 'Scans per month', key: 'scans_per_month', format: 'number' },
            { name: 'Scan history', key: 'history_days', format: 'days' },
            { name: 'Bulk URL scanning', key: 'bulk_scanning', format: 'boolean' },
            { name: 'Scheduled scans', key: 'scheduled_scans', format: 'boolean' },
        ],
    },
    {
        name: 'Export',
        features: [
            { name: 'PDF export', key: 'pdf_export', format: 'boolean' },
        ],
    },
    {
        name: 'Team & Branding',
        features: [
            { name: 'Team members', key: 'team_members', format: 'number' },
            { name: 'White-label reports', key: 'white_label', format: 'boolean' },
        ],
    },
];

const formatValue = (value: number | boolean | string | undefined, format: string, planKey: string) => {
    if (format === 'boolean') {
        return value;
    }
    if (format === 'number') {
        if (value === -1) return 'Unlimited';
        if (value === 0) return '—';
        return value;
    }
    if (format === 'days') {
        if (value === -1) return 'Unlimited';
        return `${value} days`;
    }
    if (format === 'domains') {
        if (value === -1) return 'Unlimited';
        if (value === 0) return '—';
        return `${value} domains`;
    }
    if (format === 'pillars') {
        if (planKey === 'free') return '5 pillars';
        if (planKey === 'pro') return '8 pillars';
        if (planKey === 'agency') return '12 pillars';
        return value;
    }
    if (format === 'points') {
        if (planKey === 'free') return '100 pts';
        if (planKey === 'pro') return '135 pts';
        if (planKey === 'agency') return '175 pts';
        return value;
    }
    if (format === 'text') {
        if (planKey === 'free') return 'Basic';
        return 'Full';
    }
    return value;
};

const getPlanLimit = (planKey: string, limitKey: string) => {
    // Handle special GEO score keys
    if (limitKey === 'geo_pillars') {
        if (planKey === 'free') return 5;
        if (planKey === 'pro') return 8;
        if (planKey === 'agency') return 12;
    }
    if (limitKey === 'max_score') {
        if (planKey === 'free') return 100;
        if (planKey === 'pro') return 135;
        if (planKey === 'agency') return 175;
    }

    if (planKey === 'free') {
        return freeTier.limits[limitKey as keyof typeof freeTier.limits];
    }
    const plan = props.plans[planKey];
    return plan?.limits?.[limitKey as keyof typeof plan.limits];
};
</script>

<template>
    <Head title="Pricing - GEO Analysis Plans | GeoSource.ai">
        <meta name="description" content="Simple, transparent pricing for GEO analysis. Start free with 3 scans per month, or upgrade to Pro at $39/month for comprehensive Generative Engine Optimization features." />
        <meta property="og:title" content="Pricing - GEO Analysis Plans | GeoSource.ai" />
        <meta property="og:description" content="Simple, transparent pricing for GEO analysis. Start free or upgrade to Pro for comprehensive AI search optimization features." />
        <meta property="og:type" content="website" />
        <meta property="og:url" content="https://geosource.ai/pricing" />
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:site" content="@geosource_ai" />
        <meta name="twitter:creator" content="@geosource_ai" />
        <meta name="twitter:title" content="Pricing - GEO Analysis Plans | GeoSource.ai" />
        <meta name="twitter:description" content="Simple, transparent pricing for GEO analysis. Start free or upgrade to Pro." />
        <link rel="canonical" href="https://geosource.ai/pricing" />
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
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
                    <Link
                        v-if="$page.props.auth.user"
                        :href="dashboard()"
                    >
                        <Button variant="outline">Dashboard</Button>
                    </Link>
                    <template v-else>
                        <Link :href="login()">
                            <Button variant="ghost">Log in</Button>
                        </Link>
                        <Link v-if="canRegister" :href="register()">
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
                                <Link :href="dashboard()" class="w-full">
                                    Dashboard
                                </Link>
                            </DropdownMenuItem>
                            <template v-else>
                                <DropdownMenuItem as-child>
                                    <Link :href="login()" class="w-full">
                                        Log in
                                    </Link>
                                </DropdownMenuItem>
                                <DropdownMenuItem v-if="canRegister" as-child>
                                    <Link :href="register()" class="w-full font-medium text-primary">
                                        Get Started
                                    </Link>
                                </DropdownMenuItem>
                            </template>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </div>
        </header>

        <main class="py-16">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="text-center">
                    <h1 class="text-4xl font-bold tracking-tight text-foreground sm:text-5xl">
                        Simple, transparent pricing
                    </h1>
                    <p class="mt-4 text-lg text-muted-foreground">
                        Choose the plan that fits your needs. Start free, upgrade anytime.
                    </p>
                </div>

                <!-- Plan Cards -->
                <div class="mx-auto mt-16 grid max-w-5xl gap-6 md:grid-cols-3">
                    <!-- Free Plan -->
                    <Card class="relative flex flex-col border-border">
                        <CardHeader class="pb-4">
                            <div class="flex items-center gap-2">
                                <div class="rounded-lg bg-muted p-2">
                                    <Sparkles class="h-5 w-5 text-muted-foreground" />
                                </div>
                                <CardTitle class="text-xl">{{ freeTier.name }}</CardTitle>
                            </div>
                            <CardDescription>{{ freeTier.description }}</CardDescription>
                        </CardHeader>
                        <CardContent class="flex flex-1 flex-col">
                            <div class="mb-6">
                                <div class="flex items-baseline">
                                    <span class="text-4xl font-bold tracking-tight text-foreground">$0</span>
                                    <span class="ml-1 text-lg text-muted-foreground">/month</span>
                                </div>
                            </div>
                            <ul class="mb-8 flex-1 space-y-3">
                                <li v-for="feature in freeTier.features" :key="feature" class="flex items-start gap-3">
                                    <div class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-primary/10">
                                        <Check class="h-3 w-3 text-primary" />
                                    </div>
                                    <span class="text-sm text-muted-foreground">{{ feature }}</span>
                                </li>
                            </ul>
                            <Button
                                variant="outline"
                                class="w-full py-6 text-base"
                                as-child
                            >
                                <Link :href="register()">
                                    Get Started Free
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>

                    <!-- Pro Plan -->
                    <Card
                        v-if="plans.pro"
                        class="relative flex flex-col border-primary ring-2 ring-primary"
                    >
                        <div class="absolute -top-3 left-1/2 -translate-x-1/2">
                            <span class="rounded-full bg-primary px-4 py-1 text-xs font-semibold text-primary-foreground">
                                Most Popular
                            </span>
                        </div>
                        <CardHeader class="pb-4">
                            <div class="flex items-center gap-2">
                                <div class="rounded-lg bg-blue-100 p-2 dark:bg-blue-950">
                                    <Zap class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                                </div>
                                <CardTitle class="text-xl">{{ plans.pro.name }}</CardTitle>
                            </div>
                            <CardDescription>{{ plans.pro.description }}</CardDescription>
                        </CardHeader>
                        <CardContent class="flex flex-1 flex-col">
                            <div class="mb-6">
                                <div class="flex items-baseline">
                                    <span class="text-4xl font-bold tracking-tight text-foreground">${{ plans.pro.price }}</span>
                                    <span class="ml-1 text-lg text-muted-foreground">/{{ plans.pro.interval }}</span>
                                </div>
                            </div>
                            <ul class="mb-8 flex-1 space-y-3">
                                <li v-for="feature in plans.pro.features" :key="feature" class="flex items-start gap-3">
                                    <div class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-950">
                                        <Check class="h-3 w-3 text-blue-600 dark:text-blue-400" />
                                    </div>
                                    <span class="text-sm text-muted-foreground">{{ feature }}</span>
                                </li>
                            </ul>
                            <Button
                                variant="default"
                                class="w-full py-6 text-base"
                                as-child
                            >
                                <Link :href="register()">
                                    Start with Pro
                                    <ArrowRight class="ml-2 h-4 w-4" />
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>

                    <!-- Agency Plan -->
                    <Card
                        v-if="plans.agency"
                        class="relative flex flex-col border-border opacity-75"
                    >
                        <div class="absolute -top-3 left-1/2 -translate-x-1/2">
                            <span class="rounded-full bg-muted px-4 py-1 text-xs font-semibold text-muted-foreground">
                                Coming Soon
                            </span>
                        </div>
                        <CardHeader class="pb-4">
                            <div class="flex items-center gap-2">
                                <div class="rounded-lg bg-muted p-2">
                                    <Building2 class="h-5 w-5 text-muted-foreground" />
                                </div>
                                <CardTitle class="text-xl">{{ plans.agency.name }}</CardTitle>
                            </div>
                            <CardDescription>{{ plans.agency.description }}</CardDescription>
                        </CardHeader>
                        <CardContent class="flex flex-1 flex-col">
                            <div class="mb-6">
                                <div class="flex items-baseline">
                                    <span class="text-4xl font-bold tracking-tight text-foreground">${{ plans.agency.price }}</span>
                                    <span class="ml-1 text-lg text-muted-foreground">/{{ plans.agency.interval }}</span>
                                </div>
                            </div>
                            <ul class="mb-8 flex-1 space-y-3">
                                <li v-for="feature in plans.agency.features" :key="feature" class="flex items-start gap-3">
                                    <div class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-muted">
                                        <Check class="h-3 w-3 text-muted-foreground" />
                                    </div>
                                    <span class="text-sm text-muted-foreground">{{ feature }}</span>
                                </li>
                            </ul>
                            <Button
                                variant="outline"
                                class="w-full py-6 text-base"
                                disabled
                            >
                                Coming Soon
                            </Button>
                        </CardContent>
                    </Card>
                </div>

                <!-- GEO Score Pillars Section -->
                <div class="mx-auto mt-20 w-full max-w-5xl">
                    <h2 class="mb-4 text-center text-2xl font-bold">GEO Score Pillars by Plan</h2>
                    <p class="mb-8 text-center text-muted-foreground">
                        Each plan unlocks additional scoring pillars for deeper AI optimization insights.
                        <Link href="/geo-score-explained" class="text-primary hover:underline">Learn more about GEO scoring →</Link>
                    </p>

                    <div class="grid gap-6 md:grid-cols-3">
                        <!-- Free Pillars -->
                        <Card>
                            <CardHeader class="pb-4">
                                <div class="flex items-center justify-between">
                                    <CardTitle class="text-lg">Free</CardTitle>
                                    <Badge variant="secondary">100 pts max</Badge>
                                </div>
                                <CardDescription>5 core pillars</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <ul class="space-y-2">
                                    <li v-for="pillar in freePillars" :key="pillar.name" class="flex items-center gap-2 text-sm">
                                        <component :is="pillar.icon" class="h-4 w-4 text-primary" />
                                        <span class="flex-1">{{ pillar.name }}</span>
                                        <span class="text-muted-foreground">{{ pillar.points }} pts</span>
                                    </li>
                                </ul>
                            </CardContent>
                        </Card>

                        <!-- Pro Pillars -->
                        <Card class="border-blue-500/50">
                            <CardHeader class="pb-4">
                                <div class="flex items-center justify-between">
                                    <CardTitle class="text-lg">Pro</CardTitle>
                                    <Badge class="bg-blue-500">135 pts max</Badge>
                                </div>
                                <CardDescription>All Free pillars + 3 more</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <ul class="space-y-2">
                                    <li v-for="pillar in freePillars" :key="pillar.name" class="flex items-center gap-2 text-sm text-muted-foreground">
                                        <Check class="h-4 w-4 text-muted-foreground/50" />
                                        <span class="flex-1">{{ pillar.name }}</span>
                                        <span>{{ pillar.points }} pts</span>
                                    </li>
                                    <li class="border-t pt-2 mt-2"></li>
                                    <li v-for="pillar in proPillars" :key="pillar.name" class="flex items-center gap-2 text-sm">
                                        <component :is="pillar.icon" class="h-4 w-4 text-blue-500" />
                                        <span class="flex-1 font-medium">{{ pillar.name }}</span>
                                        <span class="text-blue-500">+{{ pillar.points }} pts</span>
                                    </li>
                                </ul>
                            </CardContent>
                        </Card>

                        <!-- Agency Pillars -->
                        <Card class="border-purple-500/50">
                            <CardHeader class="pb-4">
                                <div class="flex items-center justify-between">
                                    <CardTitle class="text-lg">Agency</CardTitle>
                                    <Badge class="bg-purple-500">175 pts max</Badge>
                                </div>
                                <CardDescription>All Pro pillars + 4 more</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <ul class="space-y-2">
                                    <li class="flex items-center gap-2 text-sm text-muted-foreground">
                                        <Check class="h-4 w-4 text-muted-foreground/50" />
                                        <span class="flex-1">All Free pillars</span>
                                        <span>100 pts</span>
                                    </li>
                                    <li class="flex items-center gap-2 text-sm text-muted-foreground">
                                        <Check class="h-4 w-4 text-muted-foreground/50" />
                                        <span class="flex-1">All Pro pillars</span>
                                        <span>+35 pts</span>
                                    </li>
                                    <li class="border-t pt-2 mt-2"></li>
                                    <li v-for="pillar in agencyPillars" :key="pillar.name" class="flex items-center gap-2 text-sm">
                                        <component :is="pillar.icon" class="h-4 w-4 text-purple-500" />
                                        <span class="flex-1 font-medium">{{ pillar.name }}</span>
                                        <span class="text-purple-500">+{{ pillar.points }} pts</span>
                                    </li>
                                </ul>
                            </CardContent>
                        </Card>
                    </div>
                </div>

                <!-- Feature Comparison Table -->
                <div class="mx-auto mt-20 w-full max-w-5xl">
                    <h2 class="mb-8 text-center text-2xl font-bold">Compare Plans</h2>
                    <div class="overflow-x-auto rounded-lg border">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b bg-muted/50">
                                    <th class="px-6 py-4 text-left font-semibold">Feature</th>
                                    <th class="px-6 py-4 text-center font-semibold">Free</th>
                                    <th class="px-6 py-4 text-center font-semibold">Pro</th>
                                    <th class="px-6 py-4 text-center font-semibold text-primary">Agency</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template v-for="category in featureCategories" :key="category.name">
                                    <tr class="border-b bg-muted/30">
                                        <td colspan="4" class="px-6 py-3 text-sm font-semibold text-muted-foreground">
                                            {{ category.name }}
                                        </td>
                                    </tr>
                                    <tr v-for="feature in category.features" :key="feature.key" class="border-b">
                                        <td class="px-6 py-3 text-sm">{{ feature.name }}</td>
                                        <td class="px-6 py-3 text-center">
                                            <template v-if="feature.format === 'boolean'">
                                                <Check v-if="getPlanLimit('free', feature.key)" class="mx-auto h-5 w-5 text-green-600" />
                                                <X v-else class="mx-auto h-5 w-5 text-muted-foreground/40" />
                                            </template>
                                            <span v-else class="text-sm text-muted-foreground">
                                                {{ formatValue(getPlanLimit('free', feature.key), feature.format, 'free') }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-3 text-center">
                                            <template v-if="feature.format === 'boolean'">
                                                <Check v-if="getPlanLimit('pro', feature.key)" class="mx-auto h-5 w-5 text-green-600" />
                                                <X v-else class="mx-auto h-5 w-5 text-muted-foreground/40" />
                                            </template>
                                            <span v-else class="text-sm">
                                                {{ formatValue(getPlanLimit('pro', feature.key), feature.format, 'pro') }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-3 text-center">
                                            <template v-if="feature.format === 'boolean'">
                                                <Check v-if="getPlanLimit('agency', feature.key)" class="mx-auto h-5 w-5 text-green-600" />
                                                <X v-else class="mx-auto h-5 w-5 text-muted-foreground/40" />
                                            </template>
                                            <span v-else class="text-sm font-medium text-primary">
                                                {{ formatValue(getPlanLimit('agency', feature.key), feature.format, 'agency') }}
                                            </span>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- CTA Section -->
                <div class="mt-20 text-center">
                    <h2 class="text-2xl font-bold">Ready to optimize for AI search?</h2>
                    <p class="mt-4 text-muted-foreground">
                        Start with a free account and upgrade as you grow.
                    </p>
                    <div class="mt-8">
                        <Link :href="register()">
                            <Button size="lg" class="gap-2">
                                Get Started Free
                                <ArrowRight class="h-4 w-4" />
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="border-t py-12">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col items-center gap-6">
                    <div class="flex items-center gap-4">
                        <div class="flex items-center gap-2 rounded-lg bg-primary/10 px-4 py-2">
                            <Mail class="h-5 w-5 text-primary" />
                            <span class="text-sm font-medium">Need help?</span>
                            <a href="mailto:support@geosource.ai" class="text-sm font-semibold text-primary hover:underline">
                                support@geosource.ai
                            </a>
                        </div>
                        <a href="https://x.com/geosource_ai" target="_blank" rel="noopener noreferrer" class="flex items-center gap-2 rounded-lg bg-primary/10 px-4 py-2 text-sm font-semibold text-primary hover:bg-primary/20 transition-colors">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                            </svg>
                            <span>Follow us</span>
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
