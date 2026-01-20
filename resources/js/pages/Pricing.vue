<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Check, X, Sparkles, Zap, Building2, Globe, ArrowRight, Menu } from 'lucide-vue-next';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
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

// Define the free tier for display
const freeTier = {
    name: 'Free',
    description: 'Get started with basic GEO analysis',
    price: 0,
    interval: 'month' as const,
    features: [
        '3 scans per month',
        'Basic GEO score',
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
        csv_export: false,
        bulk_scanning: false,
    },
};

// Feature comparison data
const featureCategories = [
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
        name: 'Analysis',
        features: [
            { name: 'GEO Score breakdown', key: 'full_breakdown', format: 'text' },
            { name: 'Recommendations shown', key: 'recommendations_shown', format: 'number' },
            { name: 'Competitor tracking', key: 'competitor_tracking', format: 'domains' },
        ],
    },
    {
        name: 'Export & Integration',
        features: [
            { name: 'CSV export', key: 'csv_export', format: 'boolean' },
            { name: 'PDF export', key: 'pdf_export', format: 'boolean' },
            { name: 'API access', key: 'api_access', format: 'boolean' },
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

const formatValue = (value: number | boolean | undefined, format: string, planKey: string) => {
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
    if (format === 'text') {
        if (planKey === 'free') return 'Basic';
        return 'Full';
    }
    return value;
};

const getPlanLimit = (planKey: string, limitKey: string) => {
    if (planKey === 'free') {
        return freeTier.limits[limitKey as keyof typeof freeTier.limits];
    }
    const plan = props.plans[planKey];
    return plan?.limits?.[limitKey as keyof typeof plan.limits];
};
</script>

<template>
    <Head title="Pricing - GeoSource.ai">
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
                <div class="flex flex-col items-center justify-between gap-4 sm:flex-row">
                    <div class="flex items-center gap-2">
                        <Globe class="h-6 w-6 text-primary" />
                        <span class="font-semibold">GeoSource.ai</span>
                    </div>
                    <p class="text-sm text-muted-foreground">
                        &copy; {{ new Date().getFullYear() }} GeoSource.ai. All rights reserved.
                    </p>
                </div>
            </div>
        </footer>
    </div>
</template>
