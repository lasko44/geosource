<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Lock, Check, ArrowRight, Search } from 'lucide-vue-next';

import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { type BreadcrumbItem } from '@/types';

interface Plan {
    name: string;
    price: number;
    features: string[];
    limits: {
        citation_queries: number;
        citation_checks_per_day: number;
        citation_platforms: string[];
        ga4_connections: number;
    };
}

interface Props {
    plans: Record<string, Plan>;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Citation Tracking', href: '/citations' },
];

const citationFeatures = [
    'Track how AI platforms cite your content',
    'Monitor mentions across Perplexity, ChatGPT, Claude, and Gemini',
    'Set up automated daily or weekly checks',
    'Receive alerts when citation status changes',
    'Analyze AI responses for brand mentions',
    'Connect GA4 to track AI referral traffic',
];
</script>

<template>
    <Head title="Upgrade for Citation Tracking" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-8 p-6 max-w-3xl mx-auto">
            <!-- Header -->
            <div class="text-center">
                <div class="mx-auto w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center mb-4">
                    <Search class="h-8 w-8 text-primary" />
                </div>
                <h1 class="text-3xl font-bold">Citation Tracking</h1>
                <p class="mt-2 text-muted-foreground max-w-2xl mx-auto">
                    Citation tracking helps you understand how AI platforms reference your content.
                    This feature is available exclusively on the Agency plan.
                </p>
            </div>

            <!-- Features -->
            <Card>
                <CardHeader>
                    <CardTitle>What's included in Citation Tracking</CardTitle>
                </CardHeader>
                <CardContent>
                    <ul class="grid gap-3 md:grid-cols-2">
                        <li
                            v-for="feature in citationFeatures"
                            :key="feature"
                            class="flex items-start gap-2"
                        >
                            <Check class="h-5 w-5 text-green-600 shrink-0 mt-0.5" />
                            <span>{{ feature }}</span>
                        </li>
                    </ul>
                </CardContent>
            </Card>

            <!-- Agency Plan -->
            <Card class="border-primary">
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <CardTitle>Agency</CardTitle>
                        <span class="bg-primary text-primary-foreground text-xs px-2 py-1 rounded">
                            Required for Citation Tracking
                        </span>
                    </div>
                    <CardDescription>
                        <span class="text-3xl font-bold">${{ plans.agency?.price || 99 }}</span>
                        <span class="text-muted-foreground">/month</span>
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <ul class="space-y-2 mb-6">
                        <li class="flex items-center gap-2 text-sm">
                            <Check class="h-4 w-4 text-green-600" />
                            25 citation queries
                        </li>
                        <li class="flex items-center gap-2 text-sm">
                            <Check class="h-4 w-4 text-green-600" />
                            50 checks per day
                        </li>
                        <li class="flex items-center gap-2 text-sm">
                            <Check class="h-4 w-4 text-green-600" />
                            Daily & weekly automated monitoring
                        </li>
                        <li class="flex items-center gap-2 text-sm">
                            <Check class="h-4 w-4 text-green-600" />
                            All AI platforms (Perplexity, ChatGPT, Claude, Gemini)
                        </li>
                        <li class="flex items-center gap-2 text-sm">
                            <Check class="h-4 w-4 text-green-600" />
                            3 GA4 connections for AI traffic analytics
                        </li>
                        <li class="flex items-center gap-2 text-sm">
                            <Check class="h-4 w-4 text-green-600" />
                            Unlimited GEO scans
                        </li>
                        <li class="flex items-center gap-2 text-sm">
                            <Check class="h-4 w-4 text-green-600" />
                            White-label reports
                        </li>
                    </ul>
                    <Link href="/billing">
                        <Button class="w-full" variant="default">
                            Upgrade to Agency
                            <ArrowRight class="ml-2 h-4 w-4" />
                        </Button>
                    </Link>
                </CardContent>
            </Card>

            <!-- Back -->
            <div class="text-center">
                <Link href="/dashboard">
                    <Button variant="outline">Back to Dashboard</Button>
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
