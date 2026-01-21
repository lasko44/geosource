<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { ExternalLink, Trash2, Globe, ArrowLeft, Search, X, ChevronUp, ChevronDown, Filter } from 'lucide-vue-next';
import { useDebounceFn } from '@vueuse/core';

import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { useDateFormat } from '@/composables/useDateFormat';
import { type BreadcrumbItem, type Scan } from '@/types';

const { formatDate } = useDateFormat();

interface PaginatedScans {
    data: Scan[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    next_page_url: string | null;
    prev_page_url: string | null;
}

interface Filters {
    search: string;
    status: string;
    grade: string;
    date_from: string;
    date_to: string;
    sort: string;
    direction: string;
    per_page: number;
}

interface Props {
    scans: PaginatedScans;
    filters: Filters;
    grades: string[];
}

const props = defineProps<Props>();

// Local filter state
const search = ref(props.filters.search);
const status = ref(props.filters.status);
const grade = ref(props.filters.grade);
const dateFrom = ref(props.filters.date_from);
const dateTo = ref(props.filters.date_to);
const sortField = ref(props.filters.sort);
const sortDirection = ref(props.filters.direction);
const perPage = ref(props.filters.per_page);

// Per page options
const perPageOptions = [10, 15, 20, 25, 30, 40, 50];

// Apply filters
const applyFilters = () => {
    router.get('/scans', {
        search: search.value || undefined,
        status: status.value || undefined,
        grade: grade.value || undefined,
        date_from: dateFrom.value || undefined,
        date_to: dateTo.value || undefined,
        sort: sortField.value,
        direction: sortDirection.value,
        per_page: perPage.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Debounced search
const debouncedSearch = useDebounceFn(() => {
    applyFilters();
}, 300);

watch(search, () => {
    debouncedSearch();
});

// Clear all filters
const clearFilters = () => {
    search.value = '';
    status.value = '';
    grade.value = '';
    dateFrom.value = '';
    dateTo.value = '';
    sortField.value = 'created_at';
    sortDirection.value = 'desc';
    applyFilters();
};

// Toggle sort for a column
const toggleSort = (field: string) => {
    if (sortField.value === field) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortField.value = field;
        sortDirection.value = 'desc';
    }
    applyFilters();
};

// Check if any filters are active
const hasActiveFilters = () => {
    return search.value || status.value || grade.value || dateFrom.value || dateTo.value;
};

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

            <!-- Filters -->
            <Card>
                <CardContent class="p-4">
                    <div class="flex flex-col gap-4">
                        <!-- Search and Quick Filters -->
                        <div class="flex flex-wrap items-center gap-3">
                            <!-- Search -->
                            <div class="relative flex-1 min-w-[200px] max-w-md">
                                <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    v-model="search"
                                    placeholder="Search by URL or title..."
                                    class="pl-9"
                                />
                            </div>

                            <!-- Status Filter -->
                            <select
                                v-model="status"
                                class="h-9 rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-ring"
                                @change="applyFilters"
                            >
                                <option value="">All Statuses</option>
                                <option value="completed">Completed</option>
                                <option value="pending">Pending</option>
                                <option value="processing">Processing</option>
                                <option value="failed">Failed</option>
                            </select>

                            <!-- Grade Filter -->
                            <select
                                v-model="grade"
                                class="h-9 rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-ring"
                                @change="applyFilters"
                            >
                                <option value="">All Grades</option>
                                <option v-for="g in grades" :key="g" :value="g">
                                    Grade {{ g }}
                                </option>
                            </select>

                            <!-- Date Range -->
                            <div class="flex items-center gap-2">
                                <Input
                                    v-model="dateFrom"
                                    type="date"
                                    class="w-[140px]"
                                    placeholder="From"
                                    @change="applyFilters"
                                />
                                <span class="text-muted-foreground">to</span>
                                <Input
                                    v-model="dateTo"
                                    type="date"
                                    class="w-[140px]"
                                    placeholder="To"
                                    @change="applyFilters"
                                />
                            </div>

                            <!-- Clear Filters -->
                            <Button
                                v-if="hasActiveFilters()"
                                variant="ghost"
                                size="sm"
                                @click="clearFilters"
                            >
                                <X class="mr-1 h-4 w-4" />
                                Clear
                            </Button>
                        </div>

                        <!-- Sort Options -->
                        <div class="flex items-center gap-2 text-sm text-muted-foreground">
                            <Filter class="h-4 w-4" />
                            <span>Sort by:</span>
                            <Button
                                v-for="col in [
                                    { field: 'created_at', label: 'Date' },
                                    { field: 'score', label: 'Score' },
                                    { field: 'grade', label: 'Grade' },
                                    { field: 'title', label: 'Title' },
                                ]"
                                :key="col.field"
                                variant="ghost"
                                size="sm"
                                :class="sortField === col.field ? 'bg-muted' : ''"
                                @click="toggleSort(col.field)"
                            >
                                {{ col.label }}
                                <ChevronUp
                                    v-if="sortField === col.field && sortDirection === 'asc'"
                                    class="ml-1 h-3 w-3"
                                />
                                <ChevronDown
                                    v-else-if="sortField === col.field && sortDirection === 'desc'"
                                    class="ml-1 h-3 w-3"
                                />
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Scans List -->
            <Card>
                <CardContent class="p-0">
                    <div v-if="scans.data.length === 0" class="py-12 text-center">
                        <Globe class="mx-auto h-12 w-12 text-muted-foreground/50" />
                        <template v-if="hasActiveFilters()">
                            <h3 class="mt-4 text-lg font-medium">No scans match your filters</h3>
                            <p class="mt-2 text-sm text-muted-foreground">
                                Try adjusting your search or filter criteria
                            </p>
                            <Button class="mt-4" variant="outline" @click="clearFilters">
                                Clear Filters
                            </Button>
                        </template>
                        <template v-else>
                            <h3 class="mt-4 text-lg font-medium">No scans yet</h3>
                            <p class="mt-2 text-sm text-muted-foreground">
                                Go to the dashboard to start scanning URLs
                            </p>
                            <Link href="/dashboard" class="mt-4 inline-block">
                                <Button>Go to Dashboard</Button>
                            </Link>
                        </template>
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
                                    <p v-if="scan.user" class="mt-1 text-xs text-muted-foreground">
                                        by {{ scan.user.name }}
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
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <p class="text-sm text-muted-foreground">
                        {{ scans.total }} total scans
                    </p>
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-muted-foreground">Show</span>
                        <select
                            v-model="perPage"
                            class="h-8 rounded-md border border-input bg-background px-2 py-1 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-ring"
                            @change="applyFilters"
                        >
                            <option v-for="option in perPageOptions" :key="option" :value="option">
                                {{ option }}
                            </option>
                        </select>
                        <span class="text-sm text-muted-foreground">per page</span>
                    </div>
                </div>
                <div v-if="scans.last_page > 1" class="flex items-center gap-4">
                    <p class="text-sm text-muted-foreground">
                        Page {{ scans.current_page }} of {{ scans.last_page }}
                    </p>
                    <div class="flex gap-2">
                        <Link
                            v-if="scans.prev_page_url"
                            :href="scans.prev_page_url"
                            preserve-scroll
                        >
                            <Button variant="outline" size="sm">Previous</Button>
                        </Link>
                        <Button v-else variant="outline" size="sm" disabled>Previous</Button>

                        <Link
                            v-if="scans.next_page_url"
                            :href="scans.next_page_url"
                            preserve-scroll
                        >
                            <Button variant="outline" size="sm">Next</Button>
                        </Link>
                        <Button v-else variant="outline" size="sm" disabled>Next</Button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
