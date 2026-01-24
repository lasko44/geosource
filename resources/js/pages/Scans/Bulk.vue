<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Layers, ArrowLeft, AlertCircle } from 'lucide-vue-next';

import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { type BreadcrumbItem, type UsageSummary } from '@/types';

interface Props {
    currentTeamId: number | null;
    usage: UsageSummary;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Scans', href: '/scans' },
    { title: 'Bulk Scan', href: '/scans/bulk' },
];

const form = useForm({
    urls: '',
});

const submit = () => {
    form.post('/scans/bulk');
};

const urlCount = computed(() => {
    if (!form.urls.trim()) return 0;
    return form.urls.split('\n').filter(line => line.trim()).length;
});

const remainingScans = computed(() => {
    if (props.usage.is_unlimited) return 'Unlimited';
    return props.usage.scans_limit - props.usage.scans_used;
});

import { computed } from 'vue';
</script>

<template>
    <Head title="Bulk URL Scanning" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <div class="flex items-center gap-4">
                <Link href="/scans">
                    <Button variant="ghost" size="icon">
                        <ArrowLeft class="h-4 w-4" />
                    </Button>
                </Link>
                <div>
                    <h1 class="text-2xl font-bold">Bulk URL Scanning</h1>
                    <p class="text-muted-foreground">Scan multiple URLs at once</p>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <Card class="lg:col-span-2">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Layers class="h-5 w-5" />
                            Enter URLs
                        </CardTitle>
                        <CardDescription>
                            Paste one URL per line. Maximum 50 URLs per batch.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form @submit.prevent="submit" class="space-y-4">
                            <div class="space-y-2">
                                <Label for="urls">URLs to Scan</Label>
                                <Textarea
                                    id="urls"
                                    v-model="form.urls"
                                    placeholder="https://example.com/page1
https://example.com/page2
https://example.com/page3"
                                    class="min-h-[300px] font-mono text-sm"
                                />
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-muted-foreground">
                                        {{ urlCount }} URL{{ urlCount !== 1 ? 's' : '' }} entered
                                    </span>
                                    <span v-if="urlCount > 50" class="text-destructive">
                                        Maximum 50 URLs allowed
                                    </span>
                                </div>
                                <p v-if="form.errors.urls" class="text-sm text-destructive">{{ form.errors.urls }}</p>
                            </div>

                            <Alert v-if="form.errors.quota" variant="destructive">
                                <AlertCircle class="h-4 w-4" />
                                <AlertDescription>{{ form.errors.quota }}</AlertDescription>
                            </Alert>

                            <Alert v-if="form.errors.feature" variant="destructive">
                                <AlertCircle class="h-4 w-4" />
                                <AlertDescription>{{ form.errors.feature }}</AlertDescription>
                            </Alert>

                            <div class="flex gap-3">
                                <Button
                                    type="submit"
                                    :disabled="form.processing || urlCount === 0 || urlCount > 50"
                                >
                                    {{ form.processing ? 'Starting Scans...' : `Scan ${urlCount} URL${urlCount !== 1 ? 's' : ''}` }}
                                </Button>
                                <Link href="/scans">
                                    <Button variant="outline" type="button">Cancel</Button>
                                </Link>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <div class="space-y-4">
                    <Card>
                        <CardHeader>
                            <CardTitle>Scan Quota</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-3xl font-bold">
                                {{ remainingScans }}
                            </div>
                            <p class="text-sm text-muted-foreground">
                                {{ usage.is_unlimited ? 'scans available' : 'scans remaining this month' }}
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Tips</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-2 text-sm text-muted-foreground">
                            <p>Enter one URL per line</p>
                            <p>Include the full URL with https://</p>
                            <p>Duplicate URLs will be removed</p>
                            <p>Invalid URLs will be skipped</p>
                            <p>All scans run concurrently</p>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
