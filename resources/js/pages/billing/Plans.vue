<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Check, X, Sparkles, Zap, Building2 } from 'lucide-vue-next';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type PlanWithLimits, type UsageSummary } from '@/types';

interface Props {
    plans: Record<string, PlanWithLimits>;
    currentPlan: string | null;
    usage: UsageSummary;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Billing',
        href: '/billing',
    },
    {
        title: 'Plans',
        href: '/billing/plans',
    },
];

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
        team_members: 1,
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

const isCurrentPlan = (planKey: string) => {
    return props.usage?.plan_key === planKey;
};

const planOrder = ['free', 'pro', 'agency'];
</script>

<template>
    <Head title="Subscription Plans" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-8 p-6">
            <div class="text-center">
                <h1 class="text-3xl font-bold tracking-tight text-foreground">Choose your plan</h1>
                <p class="mt-2 text-muted-foreground">Select the plan that best fits your needs. Upgrade or downgrade anytime.</p>
            </div>

            <!-- Plan Cards -->
            <div class="mx-auto grid max-w-5xl gap-6 pt-4 md:grid-cols-3">
                <!-- Free Plan -->
                <Card
                    class="relative flex flex-col"
                    :class="{
                        'border-primary ring-2 ring-primary': isCurrentPlan('free'),
                        'border-border': !isCurrentPlan('free'),
                    }"
                >
                    <div v-if="isCurrentPlan('free')" class="absolute -top-3 left-1/2 -translate-x-1/2">
                        <span class="rounded-full bg-green-500 px-4 py-1 text-xs font-semibold text-white">
                            Current Plan
                        </span>
                    </div>
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
                            v-if="isCurrentPlan('free')"
                            variant="outline"
                            class="w-full py-6 text-base"
                            disabled
                        >
                            Current Plan
                        </Button>
                        <Button
                            v-else
                            variant="outline"
                            class="w-full py-6 text-base"
                            disabled
                        >
                            Free Forever
                        </Button>
                    </CardContent>
                </Card>

                <!-- Pro Plan -->
                <Card
                    v-if="plans.pro"
                    class="relative flex flex-col"
                    :class="{
                        'border-green-500 ring-2 ring-green-500': isCurrentPlan('pro'),
                        'border-primary ring-2 ring-primary': !isCurrentPlan('pro'),
                    }"
                >
                    <div v-if="isCurrentPlan('pro')" class="absolute -top-3 left-1/2 -translate-x-1/2">
                        <span class="rounded-full bg-green-500 px-4 py-1 text-xs font-semibold text-white">
                            Current Plan
                        </span>
                    </div>
                    <div v-else class="absolute -top-3 left-1/2 -translate-x-1/2">
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
                            v-if="isCurrentPlan('pro')"
                            variant="outline"
                            class="w-full py-6 text-base"
                            disabled
                        >
                            Current Plan
                        </Button>
                        <Button
                            v-else
                            variant="default"
                            class="w-full py-6 text-base"
                            as-child
                        >
                            <Link href="/billing/checkout/pro">
                                Upgrade to Pro
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
            <div class="mx-auto w-full max-w-5xl pt-8">
                <h2 class="mb-6 text-center text-2xl font-bold">Compare Plans</h2>
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

            <div class="mt-4 text-center">
                <Link href="/billing" class="text-sm text-muted-foreground hover:text-foreground">
                    &larr; Back to Billing
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
