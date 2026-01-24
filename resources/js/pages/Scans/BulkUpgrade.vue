<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Layers, Check, ArrowRight } from 'lucide-vue-next';

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
    { title: 'Scans', href: '/scans' },
    { title: 'Bulk Scan', href: '/scans/bulk' },
];

const features = [
    'Scan up to 50 URLs at once',
    'Perfect for auditing entire websites or sections',
    'All scans run concurrently for fast results',
    'Track progress across multiple pages simultaneously',
    'Export bulk scan results to PDF',
];
</script>

<template>
    <Head title="Upgrade for Bulk Scanning" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col items-center justify-center gap-6 p-6">
            <Card class="max-w-2xl">
                <CardHeader class="text-center">
                    <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-primary/10">
                        <Layers class="h-8 w-8 text-primary" />
                    </div>
                    <CardTitle class="text-2xl">Bulk URL Scanning</CardTitle>
                    <CardDescription class="text-base">
                        Scan multiple URLs at once for comprehensive site audits
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-6">
                    <p class="text-center text-muted-foreground">
                        Bulk URL scanning is available exclusively on the <strong>Agency</strong> plan.
                        Upgrade to scan up to 50 URLs at once.
                    </p>

                    <div class="rounded-lg border bg-muted/50 p-4">
                        <h4 class="mb-3 font-medium">With Bulk Scanning you can:</h4>
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
