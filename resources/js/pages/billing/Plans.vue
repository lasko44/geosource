<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Check, Sparkles, Coins, Building2, ArrowRight } from 'lucide-vue-next';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
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

// Token packages for reference
const tokenPackages = [
    { tokens: 50, price: 5, perToken: 0.10, savings: 0 },
    { tokens: 200, price: 15, perToken: 0.075, savings: 25, popular: true },
    { tokens: 500, price: 30, perToken: 0.06, savings: 40 },
    { tokens: 1000, price: 50, perToken: 0.05, savings: 50, bestValue: true },
];

// Token costs reference
const tokenCosts = [
    { name: 'Basic Scan (5 pillars)', tokens: 'FREE', highlight: true },
    { name: 'Pro Scan (8 pillars)', tokens: '5 tokens' },
    { name: 'Full Scan (12 pillars)', tokens: '10 tokens' },
    { name: 'AI Citations', tokens: '1-5 tokens' },
    { name: 'PDF Export', tokens: '2 tokens' },
    { name: 'Scheduled Scan', tokens: '5 tokens/run' },
];

const isCurrentPlan = (planKey: string) => {
    return props.usage?.plan_key === planKey;
};

const hasTeamPlan = () => {
    return props.usage?.plan_key === 'team' || props.usage?.plan_key === 'agency';
};
</script>

<template>
    <Head title="Plans & Pricing" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-8 p-6">
            <div class="text-center">
                <h1 class="text-3xl font-bold tracking-tight text-foreground">Plans & Pricing</h1>
                <p class="mt-2 text-muted-foreground">Pay only for what you use. Buy tokens or subscribe to the Team plan.</p>
            </div>

            <!-- Pricing Options -->
            <div class="mx-auto grid max-w-5xl gap-6 pt-4 md:grid-cols-3">
                <!-- Free Tier -->
                <Card
                    class="relative flex flex-col"
                    :class="{
                        'border-green-500 ring-2 ring-green-500': isCurrentPlan('free'),
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
                            <CardTitle class="text-xl">Free</CardTitle>
                        </div>
                        <CardDescription>Basic GEO analysis forever</CardDescription>
                    </CardHeader>
                    <CardContent class="flex flex-1 flex-col">
                        <div class="mb-6">
                            <div class="flex items-baseline">
                                <span class="text-4xl font-bold tracking-tight text-foreground">$0</span>
                                <span class="ml-1 text-lg text-muted-foreground">forever</span>
                            </div>
                        </div>
                        <ul class="mb-8 flex-1 space-y-3">
                            <li class="flex items-start gap-3">
                                <div class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-primary/10">
                                    <Check class="h-3 w-3 text-primary" />
                                </div>
                                <span class="text-sm text-muted-foreground">Unlimited basic scans (5 pillars)</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <div class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-primary/10">
                                    <Check class="h-3 w-3 text-primary" />
                                </div>
                                <span class="text-sm text-muted-foreground">Basic GEO score (100 pts max)</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <div class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-primary/10">
                                    <Check class="h-3 w-3 text-primary" />
                                </div>
                                <span class="text-sm text-muted-foreground">Top 3 recommendations</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <div class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-primary/10">
                                    <Check class="h-3 w-3 text-primary" />
                                </div>
                                <span class="text-sm text-muted-foreground">7-day scan history</span>
                            </li>
                        </ul>
                        <Button
                            variant="outline"
                            class="w-full py-6 text-base"
                            disabled
                        >
                            {{ isCurrentPlan('free') ? 'Current Plan' : 'Always Available' }}
                        </Button>
                    </CardContent>
                </Card>

                <!-- Pay As You Go (Tokens) -->
                <Card class="relative flex flex-col border-primary ring-2 ring-primary">
                    <div class="absolute -top-3 left-1/2 -translate-x-1/2">
                        <span class="rounded-full bg-primary px-4 py-1 text-xs font-semibold text-primary-foreground">
                            Most Flexible
                        </span>
                    </div>
                    <CardHeader class="pb-4">
                        <div class="flex items-center gap-2">
                            <div class="rounded-lg bg-blue-100 p-2 dark:bg-blue-500/10">
                                <Coins class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                            </div>
                            <CardTitle class="text-xl">Pay As You Go</CardTitle>
                        </div>
                        <CardDescription>Buy tokens, use them anytime</CardDescription>
                    </CardHeader>
                    <CardContent class="flex flex-1 flex-col">
                        <div class="mb-6">
                            <div class="flex items-baseline">
                                <span class="text-4xl font-bold tracking-tight text-foreground">$0.05</span>
                                <span class="ml-1 text-lg text-muted-foreground">per token</span>
                            </div>
                            <p class="mt-1 text-sm text-muted-foreground">Starting at $5 for 50 tokens</p>
                        </div>
                        <ul class="mb-8 flex-1 space-y-3">
                            <li class="flex items-start gap-3">
                                <div class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-500/10">
                                    <Check class="h-3 w-3 text-blue-600 dark:text-blue-400" />
                                </div>
                                <span class="text-sm text-muted-foreground">Pro scans (8 pillars) - 5 tokens</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <div class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-500/10">
                                    <Check class="h-3 w-3 text-blue-600 dark:text-blue-400" />
                                </div>
                                <span class="text-sm text-muted-foreground">Full scans (12 pillars) - 10 tokens</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <div class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-500/10">
                                    <Check class="h-3 w-3 text-blue-600 dark:text-blue-400" />
                                </div>
                                <span class="text-sm text-muted-foreground">AI citation tracking (1-5 tokens)</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <div class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-500/10">
                                    <Check class="h-3 w-3 text-blue-600 dark:text-blue-400" />
                                </div>
                                <span class="text-sm text-muted-foreground">Tokens never expire</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <div class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-500/10">
                                    <Check class="h-3 w-3 text-blue-600 dark:text-blue-400" />
                                </div>
                                <span class="text-sm text-muted-foreground">Bulk discounts up to 50% off</span>
                            </li>
                        </ul>
                        <Button
                            variant="default"
                            class="w-full py-6 text-base"
                            as-child
                        >
                            <Link href="/tokens">
                                Buy Tokens
                                <ArrowRight class="ml-2 h-4 w-4" />
                            </Link>
                        </Button>
                    </CardContent>
                </Card>

                <!-- Team Plan -->
                <Card
                    class="relative flex flex-col"
                    :class="{
                        'border-green-500 ring-2 ring-green-500': hasTeamPlan(),
                        'border-purple-500 ring-2 ring-purple-500': !hasTeamPlan(),
                    }"
                >
                    <div v-if="hasTeamPlan()" class="absolute -top-3 left-1/2 -translate-x-1/2">
                        <span class="rounded-full bg-green-500 px-4 py-1 text-xs font-semibold text-white">
                            Current Plan
                        </span>
                    </div>
                    <div v-else class="absolute -top-3 left-1/2 -translate-x-1/2">
                        <span class="rounded-full bg-purple-500 px-4 py-1 text-xs font-semibold text-white">
                            For Teams
                        </span>
                    </div>
                    <CardHeader class="pb-4">
                        <div class="flex items-center gap-2">
                            <div class="rounded-lg bg-purple-100 p-2 dark:bg-purple-500/10">
                                <Building2 class="h-5 w-5 text-purple-600 dark:text-purple-400" />
                            </div>
                            <CardTitle class="text-xl">Team</CardTitle>
                        </div>
                        <CardDescription>For agencies and growing teams</CardDescription>
                    </CardHeader>
                    <CardContent class="flex flex-1 flex-col">
                        <div class="mb-6">
                            <div class="flex items-baseline">
                                <span class="text-4xl font-bold tracking-tight text-foreground">$99</span>
                                <span class="ml-1 text-lg text-muted-foreground">/month</span>
                            </div>
                            <p class="mt-1 text-sm text-muted-foreground">Includes 1,000 tokens/month</p>
                        </div>
                        <ul class="mb-8 flex-1 space-y-3">
                            <li class="flex items-start gap-3">
                                <div class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-purple-100 dark:bg-purple-500/10">
                                    <Check class="h-3 w-3 text-purple-600 dark:text-purple-400" />
                                </div>
                                <span class="text-sm text-muted-foreground">Everything in Pay As You Go</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <div class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-purple-100 dark:bg-purple-500/10">
                                    <Check class="h-3 w-3 text-purple-600 dark:text-purple-400" />
                                </div>
                                <span class="text-sm text-muted-foreground">1,000 tokens included monthly</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <div class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-purple-100 dark:bg-purple-500/10">
                                    <Check class="h-3 w-3 text-purple-600 dark:text-purple-400" />
                                </div>
                                <span class="text-sm text-muted-foreground">3 teams with 5 members each</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <div class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-purple-100 dark:bg-purple-500/10">
                                    <Check class="h-3 w-3 text-purple-600 dark:text-purple-400" />
                                </div>
                                <span class="text-sm text-muted-foreground">White-label reports</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <div class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-purple-100 dark:bg-purple-500/10">
                                    <Check class="h-3 w-3 text-purple-600 dark:text-purple-400" />
                                </div>
                                <span class="text-sm text-muted-foreground">GA4 AI Traffic Analytics</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <div class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-purple-100 dark:bg-purple-500/10">
                                    <Check class="h-3 w-3 text-purple-600 dark:text-purple-400" />
                                </div>
                                <span class="text-sm text-muted-foreground">Priority support</span>
                            </li>
                        </ul>
                        <Button
                            v-if="hasTeamPlan()"
                            variant="outline"
                            class="w-full py-6 text-base"
                            disabled
                        >
                            Current Plan
                        </Button>
                        <Button
                            v-else
                            variant="default"
                            class="w-full py-6 text-base bg-purple-600 hover:bg-purple-700"
                            as-child
                        >
                            <Link href="/billing/checkout/team">
                                Upgrade to Team
                                <ArrowRight class="ml-2 h-4 w-4" />
                            </Link>
                        </Button>
                    </CardContent>
                </Card>
            </div>

            <!-- Token Packages Quick Reference -->
            <div class="mx-auto w-full max-w-5xl pt-8">
                <h2 class="mb-6 text-center text-2xl font-bold">Token Packages</h2>
                <div class="grid gap-4 md:grid-cols-4">
                    <Card
                        v-for="pkg in tokenPackages"
                        :key="pkg.tokens"
                        class="relative text-center"
                        :class="{
                            'border-primary ring-2 ring-primary': pkg.popular,
                            'border-green-500 ring-2 ring-green-500': pkg.bestValue && !pkg.popular,
                        }"
                    >
                        <Badge
                            v-if="pkg.popular"
                            class="absolute -top-3 left-1/2 -translate-x-1/2"
                        >
                            Most Popular
                        </Badge>
                        <Badge
                            v-else-if="pkg.bestValue"
                            class="absolute -top-3 left-1/2 -translate-x-1/2 bg-green-500"
                        >
                            Best Value
                        </Badge>
                        <CardHeader class="pb-2">
                            <CardTitle class="text-3xl">{{ pkg.tokens }}</CardTitle>
                            <CardDescription>tokens</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <p class="text-2xl font-bold">${{ pkg.price }}</p>
                            <p class="text-sm text-muted-foreground">${{ pkg.perToken.toFixed(3) }}/token</p>
                            <p v-if="pkg.savings > 0" class="mt-1 text-sm font-medium text-green-600 dark:text-green-400">
                                Save {{ pkg.savings }}%
                            </p>
                        </CardContent>
                    </Card>
                </div>
                <div class="mt-6 text-center">
                    <Button as-child>
                        <Link href="/tokens">
                            Buy Tokens Now
                            <ArrowRight class="ml-2 h-4 w-4" />
                        </Link>
                    </Button>
                </div>
            </div>

            <!-- Token Costs Quick Reference -->
            <div class="mx-auto w-full max-w-3xl pt-8">
                <h2 class="mb-6 text-center text-2xl font-bold">What Do Tokens Cost?</h2>
                <Card>
                    <CardContent class="pt-6">
                        <div class="grid gap-3 md:grid-cols-2">
                            <div
                                v-for="item in tokenCosts"
                                :key="item.name"
                                class="flex items-center justify-between rounded-lg border p-3"
                            >
                                <span class="text-sm">{{ item.name }}</span>
                                <Badge :variant="item.highlight ? 'default' : 'secondary'" :class="{ 'bg-green-500': item.highlight }">
                                    {{ item.tokens }}
                                </Badge>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div class="mt-4 text-center">
                <Link href="/billing" class="text-sm text-muted-foreground hover:text-foreground">
                    &larr; Back to Billing
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
