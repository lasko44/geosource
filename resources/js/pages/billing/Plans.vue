<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

import HeadingSmall from '@/components/HeadingSmall.vue';
import PlanCard from '@/components/billing/PlanCard.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type Plan } from '@/types';

interface Props {
    plans: Record<string, Plan>;
    currentPlan: string | null;
}

defineProps<Props>();

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
</script>

<template>
    <Head title="Subscription Plans" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <HeadingSmall
                title="Subscription Plans"
                description="Choose the plan that best fits your needs"
            />

            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                <PlanCard
                    v-for="(plan, key) in plans"
                    :key="key"
                    :plan="{ ...plan, key: String(key) }"
                    :is-current="plan.price_id === currentPlan"
                    :checkout-url="`/billing/checkout/${key}`"
                />
            </div>

            <div class="mt-4">
                <Link href="/billing" class="text-sm text-muted-foreground hover:text-foreground">
                    &larr; Back to Billing
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
