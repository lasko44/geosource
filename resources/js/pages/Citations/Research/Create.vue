<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Coins, Search, Sparkles } from 'lucide-vue-next';

import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { type BreadcrumbItem } from '@/types';

interface Tier {
    value: string;
    label: string;
    description: string;
    cost: number;
}

interface Usage {
    can_access: boolean;
    can_afford_research: boolean;
    can_afford_audit: boolean;
    token_balance: number;
    costs: {
        research: number;
        research_audit: number;
    };
}

interface Props {
    usage: Usage;
    tiers: Tier[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Citation Tracking', href: '/citations' },
    { title: 'Research', href: '/citations/research' },
    { title: 'New Research', href: '/citations/research/create' },
];

const form = useForm({
    topic: '',
    tier: 'research',
});

const selectedTier = () => {
    return props.tiers.find(t => t.value === form.tier);
};

const canAffordTier = (tier: string) => {
    if (tier === 'research_audit') {
        return props.usage.can_afford_audit;
    }
    return props.usage.can_afford_research;
};

const submit = () => {
    form.post('/citations/research', {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="New Citation Research" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6 max-w-2xl mx-auto">
            <!-- Header -->
            <div>
                <Link href="/citations/research">
                    <Button variant="ghost" size="sm">
                        <ArrowLeft class="mr-1 h-4 w-4" />
                        Back to Research
                    </Button>
                </Link>
                <h1 class="mt-2 text-2xl font-bold">New Citation Research</h1>
                <p class="text-muted-foreground">
                    Discover what content AI platforms cite for any topic.
                </p>
            </div>

            <!-- How it works -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Sparkles class="h-5 w-5 text-primary" />
                        How Citation Research Works
                    </CardTitle>
                </CardHeader>
                <CardContent class="space-y-3 text-sm text-muted-foreground">
                    <p>1. We query 5 AI platforms (Perplexity, ChatGPT, Claude, Gemini, DeepSeek) with your topic</p>
                    <p>2. We extract and deduplicate all sources they cite</p>
                    <p>3. We analyze patterns to identify what types of content get cited</p>
                    <p>4. You get actionable recommendations to improve your citation likelihood</p>
                </CardContent>
            </Card>

            <!-- Form -->
            <Card>
                <CardHeader>
                    <CardTitle>Research Details</CardTitle>
                    <CardDescription>
                        Enter the topic you want to research.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Topic -->
                        <div class="space-y-2">
                            <Label for="topic">Research Topic *</Label>
                            <Input
                                id="topic"
                                v-model="form.topic"
                                placeholder="e.g., best practices for API documentation"
                            />
                            <p class="text-xs text-muted-foreground">
                                Enter a topic or question you want to understand citation patterns for.
                            </p>
                            <p v-if="form.errors.topic" class="text-sm text-red-600">
                                {{ form.errors.topic }}
                            </p>
                        </div>

                        <!-- Tier Selection -->
                        <div class="space-y-3">
                            <Label>Research Tier</Label>
                            <div class="space-y-3">
                                <div
                                    v-for="tier in tiers"
                                    :key="tier.value"
                                    class="flex items-start space-x-3 p-4 rounded-lg border cursor-pointer hover:bg-muted/50 transition-colors"
                                    :class="{
                                        'border-primary bg-primary/5 ring-1 ring-primary': form.tier === tier.value,
                                        'opacity-50 cursor-not-allowed': !canAffordTier(tier.value)
                                    }"
                                    @click="canAffordTier(tier.value) && (form.tier = tier.value)"
                                >
                                    <div
                                        class="mt-0.5 h-4 w-4 rounded-full border-2 flex items-center justify-center flex-shrink-0"
                                        :class="{
                                            'border-primary': form.tier === tier.value,
                                            'border-muted-foreground': form.tier !== tier.value
                                        }"
                                    >
                                        <div
                                            v-if="form.tier === tier.value"
                                            class="h-2 w-2 rounded-full bg-primary"
                                        />
                                    </div>
                                    <div class="flex-1">
                                        <div class="flex items-center justify-between">
                                            <span class="font-medium">{{ tier.label }}</span>
                                            <span class="flex items-center gap-1 text-sm">
                                                <Coins class="h-4 w-4 text-primary" />
                                                {{ tier.cost }} tokens
                                            </span>
                                        </div>
                                        <p class="text-sm text-muted-foreground mt-1">
                                            {{ tier.description }}
                                        </p>
                                        <p v-if="!canAffordTier(tier.value)" class="text-sm text-red-600 mt-1">
                                            Insufficient tokens
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Cost Summary -->
                        <div class="rounded-lg bg-muted/50 p-4 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-muted-foreground">Research cost</span>
                                <span class="font-semibold flex items-center gap-1">
                                    <Coins class="h-4 w-4 text-primary" />
                                    {{ selectedTier()?.cost ?? 0 }} tokens
                                </span>
                            </div>
                            <div class="flex items-center justify-between pt-2 border-t">
                                <span class="text-sm text-muted-foreground">Your balance</span>
                                <span class="font-semibold">{{ usage.token_balance }} tokens</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-muted-foreground">After research</span>
                                <span class="font-semibold">
                                    {{ Math.max(0, usage.token_balance - (selectedTier()?.cost ?? 0)) }} tokens
                                </span>
                            </div>
                        </div>

                        <!-- Errors -->
                        <Alert v-if="form.errors.tokens" variant="destructive">
                            <AlertDescription>{{ form.errors.tokens }}</AlertDescription>
                        </Alert>
                        <Alert v-if="form.errors.rate_limit" variant="destructive">
                            <AlertDescription>{{ form.errors.rate_limit }}</AlertDescription>
                        </Alert>

                        <!-- Submit -->
                        <div class="flex justify-end gap-4">
                            <Link href="/citations/research">
                                <Button variant="outline" type="button">Cancel</Button>
                            </Link>
                            <Button
                                type="submit"
                                :disabled="form.processing || !canAffordTier(form.tier)"
                            >
                                <Search class="mr-2 h-4 w-4" />
                                {{ form.processing ? 'Starting...' : 'Start Research' }}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
