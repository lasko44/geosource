<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Lock, Check, ArrowRight, BarChart3 } from 'lucide-vue-next';

import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { type BreadcrumbItem } from '@/types';

interface Plan {
    name: string;
    price: number;
    features: string[];
    limits: {
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
    { title: 'GA4 Analytics', href: '/analytics/ga4' },
];

const ga4Features = [
    'Connect your Google Analytics 4 property',
    'Track referral traffic from AI platforms',
    'Monitor visits from Perplexity, ChatGPT, Claude, Gemini, and more',
    'View daily traffic trends and breakdowns',
    'Automatic daily data synchronization',
    'Historical data up to 30 days',
];
</script>

<template>
    <Head title="Upgrade for GA4 Analytics" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-8 p-6 max-w-4xl mx-auto">
            <!-- Header -->
            <div class="text-center">
                <div class="mx-auto w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center mb-4">
                    <BarChart3 class="h-8 w-8 text-primary" />
                </div>
                <h1 class="text-3xl font-bold">Upgrade to Access GA4 Analytics</h1>
                <p class="mt-2 text-muted-foreground max-w-2xl mx-auto">
                    GA4 integration lets you track how much traffic AI platforms are sending to your website.
                    This feature is available on the Agency plan.
                </p>
            </div>

            <!-- Features -->
            <Card>
                <CardHeader>
                    <CardTitle>What's included in GA4 Analytics</CardTitle>
                </CardHeader>
                <CardContent>
                    <ul class="grid gap-3 md:grid-cols-2">
                        <li
                            v-for="feature in ga4Features"
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
            <Card class="border-primary max-w-md mx-auto">
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <CardTitle>Agency</CardTitle>
                        <span class="bg-primary text-primary-foreground text-xs px-2 py-1 rounded">
                            Required for GA4
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
                            3 GA4 property connections
                        </li>
                        <li class="flex items-center gap-2 text-sm">
                            <Check class="h-4 w-4 text-green-600" />
                            All citation tracking features
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
                <Link href="/citations">
                    <Button variant="outline">Back to Citation Tracking</Button>
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
