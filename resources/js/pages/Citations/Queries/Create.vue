<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Info } from 'lucide-vue-next';

import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { type BreadcrumbItem } from '@/types';

interface Usage {
    queries_remaining: number;
    queries_is_unlimited: boolean;
    can_create_query: boolean;
    available_frequencies: string[];
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

const form = useForm({
    query: '',
    domain: '',
    brand: '',
    frequency: 'manual',
});

const submit = () => {
    form.post('/citations/queries', {
        preserveScroll: true,
    });
};

const isFrequencyAvailable = (freq: string) => {
    return props.usage.available_frequencies.includes(freq);
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
