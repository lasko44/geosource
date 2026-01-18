<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ExternalLink, Trash2, Globe, ArrowLeft } from 'lucide-vue-next';

import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { type BreadcrumbItem, type Scan } from '@/types';

interface PaginatedScans {
    data: Scan[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    next_page_url: string | null;
    prev_page_url: string | null;
}

interface Props {
    scans: PaginatedScans;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'All Scans', href: '/scans' },
];

const deleteScan = (scan: Scan) => {
    if (confirm(`Delete scan for "${scan.title || scan.url}"?`)) {
        router.delete(`/scans/${scan.uuid}`, {
            preserveScroll: true,
        });
    }
};

const getGradeColor = (grade: string) => {
    if (grade.startsWith('A')) return 'text-green-600 bg-green-100 dark:text-green-400 dark:bg-green-950';
    if (grade.startsWith('B')) return 'text-blue-600 bg-blue-100 dark:text-blue-400 dark:bg-blue-950';
    if (grade.startsWith('C')) return 'text-yellow-600 bg-yellow-100 dark:text-yellow-400 dark:bg-yellow-950';
    if (grade.startsWith('D')) return 'text-orange-600 bg-orange-100 dark:text-orange-400 dark:bg-orange-950';
    return 'text-red-600 bg-red-100 dark:text-red-400 dark:bg-red-950';
};

const getScoreColor = (score: number) => {
    if (score >= 80) return 'text-green-600 dark:text-green-400';
    if (score >= 60) return 'text-blue-600 dark:text-blue-400';
    if (score >= 40) return 'text-yellow-600 dark:text-yellow-400';
    return 'text-red-600 dark:text-red-400';
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const truncateUrl = (url: string, maxLength = 60) => {
    if (url.length <= maxLength) return url;
    return url.substring(0, maxLength) + '...';
};
</script>

<template>
    <Head title="All Scans" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <Link href="/dashboard">
                            <Button variant="ghost" size="sm">
                                <ArrowLeft class="mr-1 h-4 w-4" />
                                Dashboard
                            </Button>
                        </Link>
                    </div>
                    <h1 class="mt-2 text-2xl font-bold">All Scans</h1>
                    <p class="text-sm text-muted-foreground">
                        {{ scans.total }} total scans
                    </p>
                </div>
            </div>

            <!-- Scans List -->
            <Card>
                <CardContent class="p-0">
                    <div v-if="scans.data.length === 0" class="py-12 text-center">
                        <Globe class="mx-auto h-12 w-12 text-muted-foreground/50" />
                        <h3 class="mt-4 text-lg font-medium">No scans yet</h3>
                        <p class="mt-2 text-sm text-muted-foreground">
                            Go to the dashboard to start scanning URLs
                        </p>
                        <Link href="/dashboard" class="mt-4 inline-block">
                            <Button>Go to Dashboard</Button>
                        </Link>
                    </div>

                    <div v-else class="divide-y">
                        <div
                            v-for="scan in scans.data"
                            :key="scan.id"
                            class="flex items-center justify-between p-4 transition-colors hover:bg-muted/50"
                        >
                            <Link
                                :href="`/scans/${scan.uuid}`"
                                class="flex flex-1 items-center gap-4"
                            >
                                <!-- Grade Badge -->
                                <div
                                    class="flex h-12 w-12 items-center justify-center rounded-full text-lg font-bold"
                                    :class="getGradeColor(scan.grade)"
                                >
                                    {{ scan.grade }}
                                </div>

                                <!-- Info -->
                                <div class="flex-1">
                                    <p class="font-medium">{{ scan.title || 'Untitled' }}</p>
                                    <p class="flex items-center gap-1 text-sm text-muted-foreground">
                                        <ExternalLink class="h-3 w-3" />
                                        {{ truncateUrl(scan.url) }}
                                    </p>
                                </div>

                                <!-- Score -->
                                <div class="text-right">
                                    <p class="text-2xl font-bold" :class="getScoreColor(scan.score)">
                                        {{ scan.score.toFixed(1) }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">/ 100</p>
                                </div>

                                <!-- Date -->
                                <div class="w-36 text-right text-sm text-muted-foreground">
                                    {{ formatDate(scan.created_at) }}
                                </div>
                            </Link>

                            <!-- Delete Button -->
                            <Button
                                variant="ghost"
                                size="icon"
                                class="ml-4 text-muted-foreground hover:text-destructive"
                                @click.prevent="deleteScan(scan)"
                            >
                                <Trash2 class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Pagination -->
            <div v-if="scans.last_page > 1" class="flex items-center justify-between">
                <p class="text-sm text-muted-foreground">
                    Page {{ scans.current_page }} of {{ scans.last_page }}
                </p>
                <div class="flex gap-2">
                    <Link
                        v-if="scans.prev_page_url"
                        :href="scans.prev_page_url"
                        preserve-scroll
                    >
                        <Button variant="outline">Previous</Button>
                    </Link>
                    <Button v-else variant="outline" disabled>Previous</Button>

                    <Link
                        v-if="scans.next_page_url"
                        :href="scans.next_page_url"
                        preserve-scroll
                    >
                        <Button variant="outline">Next</Button>
                    </Link>
                    <Button v-else variant="outline" disabled>Next</Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
