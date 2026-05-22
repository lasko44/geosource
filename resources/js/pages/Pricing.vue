<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Check, Sparkles, Coins, Building2, Globe, ArrowRight, Menu, Mail, BookOpen, Layers, Award, Code, MessageSquare, UserCheck, Quote, Bot, Clock, Type, HelpCircle, Image } from 'lucide-vue-next';

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

interface Props {
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

const fullPillars = [
    { name: 'Content Freshness', points: 10, icon: Clock },
    { name: 'Readability', points: 10, icon: Type },
    { name: 'Question Coverage', points: 10, icon: HelpCircle },
    { name: 'Multimedia', points: 10, icon: Image },
];

// Token packages
const tokenPackages = [
    { tokens: 50, price: 5, perToken: 0.10, savings: 0 },
    { tokens: 200, price: 15, perToken: 0.075, savings: 25, popular: true },
    { tokens: 500, price: 30, perToken: 0.06, savings: 40 },
    { tokens: 1000, price: 50, perToken: 0.05, savings: 50, bestValue: true },
];

// Token costs
const tokenCosts = {
    scans: [
        { name: 'Basic Scan (5 pillars)', tokens: 'FREE', highlight: true },
        { name: 'Pro Scan (8 pillars)', tokens: 5 },
        { name: 'Full Scan (12 pillars)', tokens: 10 },
    ],
    citations: [
        { name: 'DeepSeek', tokens: 1 },
        { name: 'Gemini', tokens: 2 },
        { name: 'Google Search', tokens: 2 },
        { name: 'YouTube', tokens: 2 },
        { name: 'Facebook', tokens: 2 },
        { name: 'Perplexity', tokens: 3 },
        { name: 'Claude', tokens: 3 },
        { name: 'ChatGPT', tokens: 5 },
    ],
    other: [
        { name: 'PDF Export', tokens: 2 },
        { name: 'Scheduled Scan (per run)', tokens: 5 },
    ],
};
</script>

<template>
    <Head title="Pricing - GEO Analysis Plans | GeoSource.ai">
        <meta name="description" content="Pay only for what you use with our token-based pricing. Start free with unlimited basic scans, then buy tokens for advanced features. No subscriptions required." />
        <meta property="og:title" content="Pricing - GEO Analysis Plans | GeoSource.ai" />
        <meta property="og:description" content="Pay only for what you use with our token-based pricing. Start free with unlimited basic scans." />
        <meta property="og:type" content="website" />
        <meta property="og:url" content="https://geosource.ai/pricing" />
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:site" content="@geosource_ai" />
        <meta name="twitter:creator" content="@geosource_ai" />
        <meta name="twitter:title" content="Pricing - GEO Analysis Plans | GeoSource.ai" />
        <meta name="twitter:description" content="Pay only for what you use. Start free with unlimited basic scans." />
        <link rel="canonical" href="https://geosource.ai/pricing" />
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
                        Pay only for what you use
                    </h1>
                    <p class="mt-4 text-lg text-muted-foreground">
                        No subscriptions. No monthly fees. Buy tokens when you need them.
                    </p>
                </div>

                <!-- Pricing Tiers -->
                <div class="mx-auto mt-16 grid max-w-5xl gap-6 md:grid-cols-3">
                    <!-- Free Tier -->
                    <Card class="relative flex flex-col border-border">
                        <CardHeader class="pb-4">
                            <div class="flex items-center gap-2">
                                <div class="rounded-lg bg-muted p-2">
                                    <Sparkles class="h-5 w-5 text-muted-foreground" />
                                </div>
                                <CardTitle class="text-xl">Free</CardTitle>
                            </div>
                            <CardDescription>Get started with basic GEO analysis</CardDescription>
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
                                <li class="flex items-start gap-3">
                                    <div class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-primary/10">
                                        <Check class="h-3 w-3 text-primary" />
                                    </div>
                                    <span class="text-sm text-muted-foreground">No account required</span>
                                </li>
                            </ul>
                            <Button
                                variant="outline"
                                class="w-full py-6 text-base"
                                as-child
                            >
                                <Link href="/">
                                    Try Free Scan
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>

                    <!-- Token-Based (Pay As You Go) -->
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
                                    <span class="text-sm text-muted-foreground">PDF exports - 2 tokens</span>
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
                                <Link :href="register()">
                                    Get Started
                                    <ArrowRight class="ml-2 h-4 w-4" />
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>

                    <!-- Team Plan -->
                    <Card class="relative flex flex-col border-purple-500 ring-2 ring-purple-500">
                        <div class="absolute -top-3 left-1/2 -translate-x-1/2">
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
                                variant="default"
                                class="w-full py-6 text-base bg-purple-600 hover:bg-purple-700"
                                as-child
                            >
                                <Link :href="register()">
                                    Start Team Plan
                                    <ArrowRight class="ml-2 h-4 w-4" />
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>
                </div>

                <!-- Token Packages Section -->
                <div class="mx-auto mt-20 w-full max-w-5xl">
                    <h2 class="mb-4 text-center text-2xl font-bold">Token Packages</h2>
                    <p class="mb-8 text-center text-muted-foreground">
                        Buy in bulk and save up to 50%. Tokens never expire.
                    </p>

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
                </div>

                <!-- Token Costs Section -->
                <div class="mx-auto mt-20 w-full max-w-5xl">
                    <h2 class="mb-4 text-center text-2xl font-bold">What Do Tokens Cost?</h2>
                    <p class="mb-8 text-center text-muted-foreground">
                        Transparent pricing for every feature. Basic scans are always free.
                    </p>

                    <div class="grid gap-6 md:grid-cols-3">
                        <!-- Scans -->
                        <Card>
                            <CardHeader>
                                <CardTitle class="text-lg">Scans</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <ul class="space-y-3">
                                    <li
                                        v-for="item in tokenCosts.scans"
                                        :key="item.name"
                                        class="flex items-center justify-between"
                                    >
                                        <span class="text-sm">{{ item.name }}</span>
                                        <Badge :variant="item.highlight ? 'default' : 'secondary'" :class="{ 'bg-green-500': item.highlight }">
                                            {{ item.tokens }}
                                        </Badge>
                                    </li>
                                </ul>
                            </CardContent>
                        </Card>

                        <!-- Citations -->
                        <Card>
                            <CardHeader>
                                <CardTitle class="text-lg">AI Citations</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <ul class="space-y-3">
                                    <li
                                        v-for="item in tokenCosts.citations"
                                        :key="item.name"
                                        class="flex items-center justify-between"
                                    >
                                        <span class="text-sm">{{ item.name }}</span>
                                        <Badge variant="secondary">
                                            {{ item.tokens }} {{ item.tokens === 1 ? 'token' : 'tokens' }}
                                        </Badge>
                                    </li>
                                </ul>
                            </CardContent>
                        </Card>

                        <!-- Other -->
                        <Card>
                            <CardHeader>
                                <CardTitle class="text-lg">Other Features</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <ul class="space-y-3">
                                    <li
                                        v-for="item in tokenCosts.other"
                                        :key="item.name"
                                        class="flex items-center justify-between"
                                    >
                                        <span class="text-sm">{{ item.name }}</span>
                                        <Badge variant="secondary">
                                            {{ item.tokens }} tokens
                                        </Badge>
                                    </li>
                                </ul>
                            </CardContent>
                        </Card>
                    </div>
                </div>

                <!-- GEO Score Pillars Section -->
                <div class="mx-auto mt-20 w-full max-w-5xl">
                    <h2 class="mb-4 text-center text-2xl font-bold">GEO Score Pillars by Scan Type</h2>
                    <p class="mb-8 text-center text-muted-foreground">
                        Each scan type unlocks additional scoring pillars for deeper AI optimization insights.
                        <Link href="/geo-score-explained" class="text-primary hover:underline">Learn more about GEO scoring →</Link>
                    </p>

                    <div class="grid gap-6 md:grid-cols-3">
                        <!-- Basic Scan Pillars -->
                        <Card>
                            <CardHeader class="pb-4">
                                <div class="flex items-center justify-between">
                                    <CardTitle class="text-lg">Basic Scan</CardTitle>
                                    <Badge variant="secondary" class="bg-green-500 text-white">FREE</Badge>
                                </div>
                                <CardDescription>5 core pillars (100 pts max)</CardDescription>
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

                        <!-- Pro Scan Pillars -->
                        <Card class="border-blue-500/50">
                            <CardHeader class="pb-4">
                                <div class="flex items-center justify-between">
                                    <CardTitle class="text-lg">Pro Scan</CardTitle>
                                    <Badge class="bg-blue-500">5 tokens</Badge>
                                </div>
                                <CardDescription>All Basic + 3 more (135 pts max)</CardDescription>
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

                        <!-- Full Scan Pillars -->
                        <Card class="border-purple-500/50">
                            <CardHeader class="pb-4">
                                <div class="flex items-center justify-between">
                                    <CardTitle class="text-lg">Full Scan</CardTitle>
                                    <Badge class="bg-purple-500">10 tokens</Badge>
                                </div>
                                <CardDescription>All Pro + 4 more (175 pts max)</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <ul class="space-y-2">
                                    <li class="flex items-center gap-2 text-sm text-muted-foreground">
                                        <Check class="h-4 w-4 text-muted-foreground/50" />
                                        <span class="flex-1">All Basic pillars</span>
                                        <span>100 pts</span>
                                    </li>
                                    <li class="flex items-center gap-2 text-sm text-muted-foreground">
                                        <Check class="h-4 w-4 text-muted-foreground/50" />
                                        <span class="flex-1">All Pro pillars</span>
                                        <span>+35 pts</span>
                                    </li>
                                    <li class="border-t pt-2 mt-2"></li>
                                    <li v-for="pillar in fullPillars" :key="pillar.name" class="flex items-center gap-2 text-sm">
                                        <component :is="pillar.icon" class="h-4 w-4 text-purple-500" />
                                        <span class="flex-1 font-medium">{{ pillar.name }}</span>
                                        <span class="text-purple-500">+{{ pillar.points }} pts</span>
                                    </li>
                                </ul>
                            </CardContent>
                        </Card>
                    </div>
                </div>

                <!-- FAQ Section -->
                <div class="mx-auto mt-20 w-full max-w-3xl">
                    <h2 class="mb-8 text-center text-2xl font-bold">Frequently Asked Questions</h2>
                    <div class="space-y-4">
                        <Card>
                            <CardHeader>
                                <CardTitle class="text-base">Do tokens expire?</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p class="text-sm text-muted-foreground">
                                    No, your tokens never expire. Use them whenever you need them.
                                </p>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle class="text-base">Can I get a refund?</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p class="text-sm text-muted-foreground">
                                    Unused tokens can be refunded within 30 days of purchase. Contact support for assistance.
                                </p>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle class="text-base">What's included in the Team plan?</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p class="text-sm text-muted-foreground">
                                    The Team plan includes 1,000 tokens per month, team collaboration features, white-label reports, GA4 integration, and priority support. Additional tokens can be purchased at standard rates.
                                </p>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle class="text-base">Why is basic scanning free?</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p class="text-sm text-muted-foreground">
                                    We believe everyone should be able to check their GEO optimization basics. Basic scans help you understand how AI search engines see your content at no cost.
                                </p>
                            </CardContent>
                        </Card>
                    </div>
                </div>

                <!-- CTA Section -->
                <div class="mt-20 text-center">
                    <h2 class="text-2xl font-bold">Ready to optimize for AI search?</h2>
                    <p class="mt-4 text-muted-foreground">
                        Start with a free scan and upgrade when you need more.
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
