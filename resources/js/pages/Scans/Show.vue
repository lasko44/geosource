<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ExternalLink,
    RefreshCw,
    Trash2,
    CheckCircle2,
    AlertCircle,
    ArrowLeft,
    FileText,
    Layers,
    Award,
    Code,
    MessageSquare
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { type BreadcrumbItem, type Scan, type PillarResult, type Recommendation } from '@/types';

interface Props {
    scan: Scan;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Scans', href: '/scans' },
    { title: props.scan.title || 'Scan Results', href: `/scans/${props.scan.id}` },
];

const rescanning = ref(false);
const deleting = ref(false);

const rescan = () => {
    rescanning.value = true;
    router.post(`/scans/${props.scan.id}/rescan`, {}, {
        onFinish: () => {
            rescanning.value = false;
        },
    });
};

const deleteScan = () => {
    if (confirm('Are you sure you want to delete this scan?')) {
        deleting.value = true;
        router.delete(`/scans/${props.scan.id}`);
    }
};

const getGradeColor = (grade: string) => {
    if (grade.startsWith('A')) return 'text-green-600 bg-green-100 border-green-200 dark:text-green-400 dark:bg-green-950 dark:border-green-800';
    if (grade.startsWith('B')) return 'text-blue-600 bg-blue-100 border-blue-200 dark:text-blue-400 dark:bg-blue-950 dark:border-blue-800';
    if (grade.startsWith('C')) return 'text-yellow-600 bg-yellow-100 border-yellow-200 dark:text-yellow-400 dark:bg-yellow-950 dark:border-yellow-800';
    if (grade.startsWith('D')) return 'text-orange-600 bg-orange-100 border-orange-200 dark:text-orange-400 dark:bg-orange-950 dark:border-orange-800';
    return 'text-red-600 bg-red-100 border-red-200 dark:text-red-400 dark:bg-red-950 dark:border-red-800';
};

const getScoreColor = (percentage: number) => {
    if (percentage >= 80) return 'bg-green-500';
    if (percentage >= 60) return 'bg-blue-500';
    if (percentage >= 40) return 'bg-yellow-500';
    return 'bg-red-500';
};

const getPriorityColor = (priority: string) => {
    if (priority === 'high') return 'text-red-600 bg-red-100 dark:text-red-400 dark:bg-red-900/50';
    if (priority === 'medium') return 'text-yellow-600 bg-yellow-100 dark:text-yellow-400 dark:bg-yellow-900/50';
    return 'text-green-600 bg-green-100 dark:text-green-400 dark:bg-green-900/50';
};

const pillarIcons: Record<string, any> = {
    definitions: FileText,
    structure: Layers,
    authority: Award,
    machine_readable: Code,
    answerability: MessageSquare,
};

const pillars = computed(() => {
    return Object.entries(props.scan.results?.pillars || {}).map(([key, value]) => ({
        key,
        ...(value as PillarResult),
        icon: pillarIcons[key] || FileText,
    }));
});

const recommendations = computed(() => {
    return Object.entries(props.scan.results?.recommendations || {}).map(([key, value]) => ({
        key,
        ...(value as Recommendation),
    }));
});

const summary = computed(() => props.scan.results?.summary);

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>

<template>
    <Head :title="`Scan: ${scan.title || scan.url}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <Link href="/dashboard">
                            <Button variant="ghost" size="sm">
                                <ArrowLeft class="mr-1 h-4 w-4" />
                                Back
                            </Button>
                        </Link>
                    </div>
                    <h1 class="mt-2 text-2xl font-bold">{{ scan.title || 'Scan Results' }}</h1>
                    <a
                        :href="scan.url"
                        target="_blank"
                        class="mt-1 flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
                    >
                        <ExternalLink class="h-3 w-3" />
                        {{ scan.url }}
                    </a>
                    <p class="mt-1 text-xs text-muted-foreground">
                        Scanned {{ formatDate(scan.created_at) }}
                    </p>
                </div>

                <div class="flex gap-2">
                    <Button variant="outline" @click="rescan" :disabled="rescanning">
                        <RefreshCw class="mr-2 h-4 w-4" :class="{ 'animate-spin': rescanning }" />
                        {{ rescanning ? 'Rescanning...' : 'Rescan' }}
                    </Button>
                    <Button variant="destructive" @click="deleteScan" :disabled="deleting">
                        <Trash2 class="mr-2 h-4 w-4" />
                        Delete
                    </Button>
                </div>
            </div>

            <!-- Main Score Card -->
            <Card class="overflow-hidden">
                <div class="flex flex-col md:flex-row">
                    <!-- Score Display -->
                    <div class="flex flex-col items-center justify-center border-b bg-muted/30 p-8 md:border-b-0 md:border-r md:px-16">
                        <div
                            class="flex h-32 w-32 items-center justify-center rounded-full border-4 text-5xl font-bold"
                            :class="getGradeColor(scan.grade)"
                        >
                            {{ scan.grade }}
                        </div>
                        <p class="mt-4 text-4xl font-bold">{{ scan.score.toFixed(1) }}</p>
                        <p class="text-sm text-muted-foreground">out of {{ scan.results?.max_score || 100 }}</p>
                    </div>

                    <!-- Summary -->
                    <div class="flex-1 p-6">
                        <h3 class="text-lg font-semibold">Summary</h3>
                        <p class="mt-2 text-muted-foreground">{{ summary?.overall }}</p>

                        <div class="mt-6 grid gap-4 md:grid-cols-2">
                            <div>
                                <h4 class="flex items-center gap-2 text-sm font-medium text-green-600">
                                    <CheckCircle2 class="h-4 w-4" />
                                    Strengths
                                </h4>
                                <ul class="mt-2 space-y-1">
                                    <li v-for="strength in summary?.strengths" :key="strength" class="text-sm text-muted-foreground">
                                        {{ strength }}
                                    </li>
                                </ul>
                            </div>
                            <div>
                                <h4 class="flex items-center gap-2 text-sm font-medium text-orange-600">
                                    <AlertCircle class="h-4 w-4" />
                                    Areas to Improve
                                </h4>
                                <ul class="mt-2 space-y-1">
                                    <li v-for="weakness in summary?.weaknesses" :key="weakness" class="text-sm text-muted-foreground">
                                        {{ weakness }}
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </Card>

            <!-- Pillar Scores -->
            <div>
                <h2 class="mb-4 text-xl font-semibold">Score Breakdown</h2>
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <Card v-for="pillar in pillars" :key="pillar.key">
                        <CardHeader class="pb-2">
                            <div class="flex items-center justify-between">
                                <CardTitle class="flex items-center gap-2 text-base">
                                    <component :is="pillar.icon" class="h-5 w-5 text-muted-foreground" />
                                    {{ pillar.name }}
                                </CardTitle>
                                <span class="text-2xl font-bold">{{ pillar.score.toFixed(1) }}</span>
                            </div>
                            <CardDescription>out of {{ pillar.max_score }}</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <!-- Progress Bar -->
                            <div class="mt-2 h-3 w-full overflow-hidden rounded-full bg-muted">
                                <div
                                    class="h-full rounded-full transition-all"
                                    :class="getScoreColor(pillar.percentage)"
                                    :style="{ width: `${pillar.percentage}%` }"
                                />
                            </div>
                            <p class="mt-2 text-right text-sm font-medium">{{ pillar.percentage.toFixed(0) }}%</p>

                            <!-- Breakdown if available -->
                            <div v-if="pillar.breakdown" class="mt-4 space-y-2 border-t pt-4">
                                <div
                                    v-for="(value, key) in pillar.breakdown"
                                    :key="key"
                                    class="flex items-center justify-between text-sm"
                                >
                                    <span class="text-muted-foreground">{{ String(key).replace(/_/g, ' ') }}</span>
                                    <span class="font-medium">{{ typeof value === 'number' ? value.toFixed(1) : value }}</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- Recommendations -->
            <div v-if="recommendations.length > 0">
                <h2 class="mb-4 text-xl font-semibold">Recommendations</h2>
                <div class="space-y-4">
                    <Alert
                        v-for="rec in recommendations"
                        :key="rec.key"
                        :class="{
                            'border-red-300 bg-red-50 dark:border-red-800 dark:bg-red-950': rec.priority === 'high',
                            'border-yellow-300 bg-yellow-50 dark:border-yellow-800 dark:bg-yellow-950': rec.priority === 'medium',
                            'border-green-300 bg-green-50 dark:border-green-800 dark:bg-green-950': rec.priority === 'low',
                        }"
                    >
                        <AlertTitle class="flex items-center gap-2 text-foreground">
                            {{ rec.pillar }}
                            <span
                                class="rounded-full px-2 py-0.5 text-xs font-medium"
                                :class="getPriorityColor(rec.priority)"
                            >
                                {{ rec.priority }} priority
                            </span>
                            <span class="ml-auto text-sm text-muted-foreground">{{ rec.current_score }}</span>
                        </AlertTitle>
                        <AlertDescription class="text-muted-foreground">
                            <ul class="mt-2 list-inside list-disc space-y-1">
                                <li v-for="(action, index) in rec.actions" :key="index">
                                    {{ action }}
                                </li>
                            </ul>
                        </AlertDescription>
                    </Alert>
                </div>
            </div>

            <!-- Raw Details (Collapsible) -->
            <details class="group">
                <summary class="cursor-pointer text-sm text-muted-foreground hover:text-foreground">
                    View raw scan data
                </summary>
                <Card class="mt-4">
                    <CardContent class="pt-6">
                        <pre class="max-h-96 overflow-auto rounded bg-muted p-4 text-xs">{{ JSON.stringify(scan.results, null, 2) }}</pre>
                    </CardContent>
                </Card>
            </details>
        </div>
    </AppLayout>
</template>
