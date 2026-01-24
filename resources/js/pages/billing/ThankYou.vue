<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { CheckCircle, Calendar, Users, Sparkles, FileText, Quote, BarChart3, ArrowRight } from 'lucide-vue-next';
import { onMounted } from 'vue';

import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

interface Props {
    plan: {
        key: string;
        name: string;
        price: number;
    };
}

const props = defineProps<Props>();

// Features unlocked by plan
const planFeatures: Record<string, { icon: typeof CheckCircle; title: string; description: string; href: string }[]> = {
    pro: [
        { icon: FileText, title: 'PDF Export', description: 'Export your scan reports as PDFs', href: '/scans' },
        { icon: Users, title: 'Team Collaboration', description: 'Create a team and invite up to 5 members', href: '/teams/create' },
        { icon: Sparkles, title: '50 Scans/Month', description: 'Analyze more pages with your increased quota', href: '/scans' },
    ],
    agency: [
        { icon: Calendar, title: 'Scheduled Scans', description: 'Automate your scans on a daily, weekly, or monthly basis', href: '/scheduled-scans' },
        { icon: Quote, title: 'Citation Tracking', description: 'Monitor when AI engines cite your content', href: '/citations' },
        { icon: BarChart3, title: 'GA4 AI Traffic', description: 'Track AI-driven traffic to your site', href: '/analytics/ga4' },
        { icon: Users, title: 'Teams & White Label', description: 'Create up to 3 teams with custom branding', href: '/teams/create' },
        { icon: Sparkles, title: 'Unlimited Scans', description: 'No limits on how many pages you can analyze', href: '/scans' },
    ],
};

const features = planFeatures[props.plan.key] || planFeatures.pro;

onMounted(() => {
    // Fire Google Ads conversion tracking
    if (typeof window !== 'undefined' && window.gtag) {
        window.gtag('event', 'conversion', {
            'send_to': document.querySelector('meta[name="google-ads-conversion-id"]')?.getAttribute('content'),
            'value': props.plan.price,
            'currency': 'USD',
            'transaction_id': Date.now().toString(),
        });
    }

    // Fire GA4 purchase event
    if (typeof window !== 'undefined' && window.gtag) {
        window.gtag('event', 'purchase', {
            'value': props.plan.price,
            'currency': 'USD',
            'items': [{
                'item_id': props.plan.key,
                'item_name': props.plan.name,
                'price': props.plan.price,
                'quantity': 1,
            }],
        });
    }
});
</script>

<template>
    <AppLayout :breadcrumbs="[{ title: 'Billing', href: '/billing' }, { title: 'Welcome', href: '/billing/thank-you' }]">
        <Head title="Welcome!" />

        <div class="mx-auto max-w-3xl px-4 py-12">
            <!-- Success Header -->
            <div class="text-center mb-10">
                <div class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/30">
                    <CheckCircle class="h-12 w-12 text-green-600 dark:text-green-400" />
                </div>
                <h1 class="text-3xl font-bold tracking-tight mb-2">
                    Welcome to {{ plan.name }}!
                </h1>
                <p class="text-lg text-muted-foreground">
                    Your subscription is now active. Here's what you've unlocked:
                </p>
            </div>

            <!-- Features Grid -->
            <div class="grid gap-4 mb-10">
                <Card v-for="feature in features" :key="feature.title" class="group hover:border-primary/50 transition-colors">
                    <Link :href="feature.href" class="block">
                        <CardHeader class="pb-2">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 group-hover:bg-primary/20 transition-colors">
                                    <component :is="feature.icon" class="h-5 w-5 text-primary" />
                                </div>
                                <div class="flex-1">
                                    <CardTitle class="text-base flex items-center gap-2">
                                        {{ feature.title }}
                                        <ArrowRight class="h-4 w-4 opacity-0 -translate-x-2 group-hover:opacity-100 group-hover:translate-x-0 transition-all" />
                                    </CardTitle>
                                    <CardDescription>{{ feature.description }}</CardDescription>
                                </div>
                            </div>
                        </CardHeader>
                    </Link>
                </Card>
            </div>

            <!-- CTA Buttons -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <Button size="lg" as-child>
                    <Link href="/scans">
                        Start Scanning
                        <ArrowRight class="ml-2 h-4 w-4" />
                    </Link>
                </Button>
                <Button size="lg" variant="outline" as-child>
                    <Link href="/billing">
                        View Billing Details
                    </Link>
                </Button>
            </div>

            <!-- Help Text -->
            <p class="text-center text-sm text-muted-foreground mt-8">
                Need help getting started? Check out our
                <Link href="/help" class="text-primary hover:underline">Help Guide</Link>
                or contact support.
            </p>
        </div>
    </AppLayout>
</template>
