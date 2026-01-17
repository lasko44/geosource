<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

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
        <div class="flex flex-col gap-8 p-6">
            <div class="text-center">
                <h1 class="text-3xl font-bold tracking-tight text-foreground">Choose your plan</h1>
                <p class="mt-2 text-muted-foreground">Select the plan that best fits your needs. Upgrade or downgrade anytime.</p>
            </div>

            <div class="mx-auto grid max-w-4xl gap-8 pt-4 md:grid-cols-2">
                <PlanCard
                    v-for="(plan, key) in plans"
                    :key="key"
                    :plan="{ ...plan, key: String(key) }"
                    :is-current="currentPlan !== null && plan.price_id === currentPlan"
                    :checkout-url="`/billing/checkout/${key}`"
                    :popular="key === 'agency'"
                />
            </div>

            <div class="mt-4 text-center">
                <Link href="/billing" class="text-sm text-muted-foreground hover:text-foreground">
                    &larr; Back to Billing
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
