<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Info, Coins } from 'lucide-vue-next';
import { computed, watch } from 'vue';

import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Checkbox } from '@/components/ui/checkbox';
import { type BreadcrumbItem } from '@/types';

interface Usage {
    queries_remaining: number;
    queries_is_unlimited: boolean;
    can_create_query: boolean;
    available_frequencies: string[];
    token_balance: number;
    platform_costs: Record<string, number>;
}

interface Props {
    usage: Usage;
    platforms: Record<string, { name: string; color: string }>;
    frequencies: Record<string, string>;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Citation Tracking', href: '/citations' },
    { title: 'Create Query', href: '/citations/queries/create' },
];

// AI platforms that can be scheduled
const schedulablePlatforms = ['perplexity', 'openai', 'claude', 'gemini', 'deepseek'];

const form = useForm({
    query: '',
    domain: '',
    brand: '',
    frequency: 'manual',
    scheduled_platforms: [] as string[],
    monthly_token_budget: null as number | null,
});

// Calculate cost per scheduled run
const costPerRun = computed(() => {
    if (form.scheduled_platforms.length === 0) {
        // If no platforms selected, default to all AI platforms
        return schedulablePlatforms.reduce((sum, p) => sum + (props.usage.platform_costs?.[p] ?? 0), 0);
    }
    return form.scheduled_platforms.reduce((sum, p) => sum + (props.usage.platform_costs?.[p] ?? 0), 0);
});

// Estimate monthly cost based on frequency
const estimatedMonthlyCost = computed(() => {
    if (form.frequency === 'manual') return 0;
    const runsPerMonth = form.frequency === 'daily' ? 30 : 4; // daily or weekly
    return costPerRun.value * runsPerMonth;
});

// When frequency changes to manual, clear scheduling options
watch(() => form.frequency, (newFreq) => {
    if (newFreq === 'manual') {
        form.scheduled_platforms = [];
        form.monthly_token_budget = null;
    }
});

const togglePlatform = (platform: string) => {
    const index = form.scheduled_platforms.indexOf(platform);
    if (index === -1) {
        form.scheduled_platforms.push(platform);
    } else {
        form.scheduled_platforms.splice(index, 1);
    }
};

const submit = () => {
    form.post('/citations/queries', {
        preserveScroll: true,
    });
};

const isFrequencyAvailable = (freq: string) => {
    return props.usage.available_frequencies.includes(freq);
};

const getPlatformCost = (platform: string) => {
    return props.usage.platform_costs?.[platform] ?? 0;
};
</script>

<template>
    <Head title="Create Citation Query" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6 max-w-2xl mx-auto">
            <!-- Header -->
            <div>
                <Link href="/citations">
                    <Button variant="ghost" size="sm">
                        <ArrowLeft class="mr-1 h-4 w-4" />
                        Back to Citations
                    </Button>
                </Link>
                <h1 class="mt-2 text-2xl font-bold">Create Citation Query</h1>
                <p class="text-muted-foreground">
                    Set up a query to check if AI platforms cite your content.
                </p>
            </div>

            <!-- Usage Warning -->
            <Alert v-if="!usage.can_create_query">
                <Info class="h-4 w-4" />
                <AlertDescription>
                    You've reached your query limit. Please upgrade your plan or delete existing queries.
                </AlertDescription>
            </Alert>

            <!-- Form -->
            <Card>
                <CardHeader>
                    <CardTitle>Query Details</CardTitle>
                    <CardDescription>
                        Enter the search query and the domain/brand you want to track.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Query -->
                        <div class="space-y-2">
                            <Label for="query">Search Query *</Label>
                            <Input
                                id="query"
                                v-model="form.query"
                                placeholder="e.g., best project management software"
                                :disabled="!usage.can_create_query"
                            />
                            <p class="text-xs text-muted-foreground">
                                The question or search term to ask AI platforms.
                            </p>
                            <p v-if="form.errors.query" class="text-sm text-red-600">
                                {{ form.errors.query }}
                            </p>
                        </div>

                        <!-- Domain -->
                        <div class="space-y-2">
                            <Label for="domain">Domain to Track *</Label>
                            <Input
                                id="domain"
                                v-model="form.domain"
                                placeholder="e.g., example.com"
                                :disabled="!usage.can_create_query"
                            />
                            <p class="text-xs text-muted-foreground">
                                We'll check if this domain appears in AI responses.
                            </p>
                            <p v-if="form.errors.domain" class="text-sm text-red-600">
                                {{ form.errors.domain }}
                            </p>
                        </div>

                        <!-- Brand -->
                        <div class="space-y-2">
                            <Label for="brand">Brand Name (optional)</Label>
                            <Input
                                id="brand"
                                v-model="form.brand"
                                placeholder="e.g., Acme Inc"
                                :disabled="!usage.can_create_query"
                            />
                            <p class="text-xs text-muted-foreground">
                                We'll also check for brand name mentions.
                            </p>
                        </div>

                        <!-- Frequency -->
                        <div class="space-y-2">
                            <Label for="frequency">Check Frequency</Label>
                            <Select v-model="form.frequency" :disabled="!usage.can_create_query">
                                <SelectTrigger>
                                    <SelectValue placeholder="Select frequency" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="(label, value) in frequencies"
                                        :key="value"
                                        :value="value"
                                        :disabled="!isFrequencyAvailable(value)"
                                    >
                                        {{ label }}
                                        <span v-if="!isFrequencyAvailable(value)" class="text-muted-foreground ml-2">
                                            (Upgrade required)
                                        </span>
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p class="text-xs text-muted-foreground">
                                How often to automatically check this query.
                            </p>
                        </div>

                        <!-- Scheduling Options (only show for non-manual) -->
                        <template v-if="form.frequency !== 'manual'">
                            <!-- Platform Selection -->
                            <div class="space-y-3">
                                <Label>Platforms to Check</Label>
                                <p class="text-xs text-muted-foreground">
                                    Select which AI platforms to check on schedule. Leave empty to check all.
                                </p>
                                <div class="grid grid-cols-2 gap-3">
                                    <div
                                        v-for="platform in schedulablePlatforms"
                                        :key="platform"
                                        class="flex items-center space-x-2 p-3 rounded-lg border cursor-pointer hover:bg-muted/50"
                                        :class="{ 'border-primary bg-primary/5': form.scheduled_platforms.includes(platform) }"
                                        @click="togglePlatform(platform)"
                                    >
                                        <Checkbox
                                            :checked="form.scheduled_platforms.includes(platform)"
                                            @update:checked="togglePlatform(platform)"
                                        />
                                        <div class="flex-1">
                                            <span class="font-medium">{{ platforms[platform]?.name || platform }}</span>
                                        </div>
                                        <span class="text-xs text-muted-foreground">
                                            {{ getPlatformCost(platform) }} tokens
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Cost Summary -->
                            <div class="rounded-lg bg-muted/50 p-4 space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-muted-foreground">Cost per scheduled run</span>
                                    <span class="font-semibold flex items-center gap-1">
                                        <Coins class="h-4 w-4 text-primary" />
                                        {{ costPerRun }} tokens
                                    </span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-muted-foreground">
                                        Estimated monthly cost ({{ form.frequency === 'daily' ? '~30 runs' : '~4 runs' }})
                                    </span>
                                    <span class="font-semibold flex items-center gap-1">
                                        <Coins class="h-4 w-4 text-primary" />
                                        ~{{ estimatedMonthlyCost }} tokens
                                    </span>
                                </div>
                                <div class="flex items-center justify-between pt-2 border-t">
                                    <span class="text-sm text-muted-foreground">Your current balance</span>
                                    <span class="font-semibold">{{ usage.token_balance }} tokens</span>
                                </div>
                            </div>

                            <!-- Monthly Budget -->
                            <div class="space-y-2">
                                <Label for="budget">Monthly Token Budget (optional)</Label>
                                <Input
                                    id="budget"
                                    type="number"
                                    v-model.number="form.monthly_token_budget"
                                    placeholder="e.g., 100"
                                    min="0"
                                    :disabled="!usage.can_create_query"
                                />
                                <p class="text-xs text-muted-foreground">
                                    Set a maximum number of tokens to spend per month on this query. Leave empty for no limit.
                                </p>
                            </div>
                        </template>

                        <!-- Error -->
                        <Alert v-if="form.errors.limit" variant="destructive">
                            <AlertDescription>{{ form.errors.limit }}</AlertDescription>
                        </Alert>

                        <!-- Submit -->
                        <div class="flex justify-end gap-4">
                            <Link href="/citations">
                                <Button variant="outline" type="button">Cancel</Button>
                            </Link>
                            <Button
                                type="submit"
                                :disabled="form.processing || !usage.can_create_query"
                            >
                                {{ form.processing ? 'Creating...' : 'Create Query' }}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
