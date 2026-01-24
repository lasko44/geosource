<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Calendar, Check, ArrowRight } from 'lucide-vue-next';

import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { type BreadcrumbItem } from '@/types';

interface PlanWithLimits {
    name: string;
    description: string;
    price: number;
    features: string[];
}

interface Props {
    plans: Record<string, PlanWithLimits>;
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Scheduled Scans', href: '/scheduled-scans' },
];

const features = [
    'Automate your GEO scans on daily, weekly, or monthly schedules',
    'Monitor content changes and track optimization progress over time',
    'Get notified when scan results change significantly',
    'Run scans during off-peak hours for better performance',
    'Track historical trends with scheduled monitoring',
];
</script>

<template>
    <Head title="Upgrade for Scheduled Scans" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col items-center justify-center gap-6 p-6">
            <Card class="max-w-2xl">
                <CardHeader class="text-center">
                    <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-primary/10">
                        <Calendar class="h-8 w-8 text-primary" />
                    </div>
                    <CardTitle class="text-2xl">Scheduled Scans</CardTitle>
                    <CardDescription class="text-base">
                        Automate your GEO monitoring with scheduled scans
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-6">
                    <p class="text-center text-muted-foreground">
                        Scheduled scans are available exclusively on the <strong>Agency</strong> plan.
                        Upgrade to unlock automated GEO monitoring for your content.
                    </p>

                    <div class="rounded-lg border bg-muted/50 p-4">
                        <h4 class="mb-3 font-medium">With Scheduled Scans you can:</h4>
                        <ul class="space-y-2">
                            <li v-for="feature in features" :key="feature" class="flex items-start gap-2">
                                <Check class="mt-0.5 h-4 w-4 shrink-0 text-green-600" />
                                <span class="text-sm">{{ feature }}</span>
                            </li>
                        </ul>
                    </div>

                    <div class="flex flex-col items-center gap-4 pt-4">
                        <Link href="/billing/plans">
                            <Button size="lg">
                                Upgrade to Agency
                                <ArrowRight class="ml-2 h-4 w-4" />
                            </Button>
                        </Link>
                        <Link href="/dashboard" class="text-sm text-muted-foreground hover:text-primary">
                            Return to Dashboard
                        </Link>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
