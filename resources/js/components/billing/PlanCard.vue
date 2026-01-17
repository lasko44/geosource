<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

import { Button } from '@/components/ui/button';
import { type Plan } from '@/types';

interface Props {
    plan: Plan & { key: string };
    isCurrent: boolean;
    checkoutUrl: string;
    popular?: boolean;
}

defineProps<Props>();
</script>

<template>
    <div
        class="relative flex flex-col rounded-2xl border bg-card p-8 shadow-sm transition-all hover:shadow-md"
        :class="{
            'border-primary ring-2 ring-primary': isCurrent || popular,
            'border-border': !isCurrent && !popular,
        }"
    >
        <!-- Popular badge -->
        <div
            v-if="popular && !isCurrent"
            class="absolute -top-3 left-1/2 -translate-x-1/2"
        >
            <span class="rounded-full bg-primary px-4 py-1 text-xs font-semibold text-primary-foreground">
                Most Popular
            </span>
        </div>

        <!-- Current plan badge -->
        <div
            v-if="isCurrent"
            class="absolute -top-3 left-1/2 -translate-x-1/2"
        >
            <span class="rounded-full bg-green-500 px-4 py-1 text-xs font-semibold text-white">
                Current Plan
            </span>
        </div>

        <!-- Plan name -->
        <div class="mb-4">
            <h3 class="text-xl font-semibold text-foreground">{{ plan.name }}</h3>
            <p class="mt-1 text-sm text-muted-foreground">{{ plan.description }}</p>
        </div>

        <!-- Price -->
        <div class="mb-6">
            <div class="flex items-baseline">
                <span class="text-4xl font-bold tracking-tight text-foreground">${{ plan.price }}</span>
                <span class="ml-1 text-lg text-muted-foreground">/{{ plan.interval }}</span>
            </div>
        </div>

        <!-- Features -->
        <ul class="mb-8 flex-1 space-y-3">
            <li v-for="feature in plan.features" :key="feature" class="flex items-start gap-3">
                <div class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-primary/10">
                    <svg class="h-3 w-3 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <span class="text-sm text-muted-foreground">{{ feature }}</span>
            </li>
        </ul>

        <!-- CTA Button -->
        <div class="mt-auto">
            <Button
                v-if="isCurrent"
                variant="outline"
                class="w-full py-6 text-base"
                disabled
            >
                Current Plan
            </Button>
            <Button
                v-else
                :variant="popular ? 'default' : 'outline'"
                class="w-full py-6 text-base"
                as-child
            >
                <Link :href="checkoutUrl">
                    Get Started
                </Link>
            </Button>
        </div>
    </div>
</template>
