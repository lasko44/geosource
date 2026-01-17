<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { type Plan } from '@/types';

interface Props {
    plan: Plan & { key: string };
    isCurrent: boolean;
    checkoutUrl: string;
}

defineProps<Props>();
</script>

<template>
    <Card :class="{ 'border-primary ring-1 ring-primary': isCurrent }">
        <CardHeader>
            <div class="flex items-center justify-between">
                <CardTitle>{{ plan.name }}</CardTitle>
                <span v-if="isCurrent" class="rounded-full bg-primary/10 px-2 py-1 text-xs text-primary">
                    Current Plan
                </span>
            </div>
            <CardDescription>{{ plan.description }}</CardDescription>
        </CardHeader>
        <CardContent>
            <div class="mb-4">
                <span class="text-3xl font-bold">${{ plan.price }}</span>
                <span class="text-muted-foreground">/{{ plan.interval }}</span>
            </div>
            <ul class="space-y-2">
                <li v-for="feature in plan.features" :key="feature" class="flex items-center gap-2">
                    <svg class="h-4 w-4 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span class="text-sm">{{ feature }}</span>
                </li>
            </ul>
        </CardContent>
        <CardFooter>
            <Button v-if="isCurrent" variant="outline" class="w-full" disabled>
                Current Plan
            </Button>
            <Button v-else class="w-full" as-child>
                <Link :href="checkoutUrl">
                    Select Plan
                </Link>
            </Button>
        </CardFooter>
    </Card>
</template>
