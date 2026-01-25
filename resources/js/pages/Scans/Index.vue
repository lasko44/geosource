<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import { ExternalLink, Trash2, Globe, ArrowLeft, Search, X, ChevronUp, ChevronDown, Filter, Layers, Clock, CheckCircle2, XCircle, Repeat, Loader2 } from 'lucide-vue-next';
import { useDebounceFn } from '@vueuse/core';

import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { useDateFormat } from '@/composables/useDateFormat';
import { type BreadcrumbItem, type Scan, type UsageSummary } from '@/types';

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
    usage: UsageSummary;
    canBulkScan: boolean;
    currentTeamId: number | null;
}

const props = defineProps<Props>();

// Single URL scan form
const scanForm = useForm({
    url: '',
    team_id: props.currentTeamId ?? null,
});

// Cooldown state for single URL scan
const cooldown = ref<{
    on_cooldown: boolean;
    minutes_remaining?: number;
    existing_scan_uuid?: string;
} | null>(null);
const checkingCooldown = ref(false);
let cooldownCheckTimeout: ReturnType<typeof setTimeout> | null = null;

const isOnCooldown = computed(() => cooldown.value?.on_cooldown === true);
const cooldownMinutes = computed(() => {
    const mins = cooldown.value?.minutes_remaining;
    return mins ? Math.ceil(mins) : 0;
});

// Check cooldown for URL
const isValidUrlForCheck = (url: string): boolean => {
    if (!url) return false;
    try {
        new URL(url);
        return true;
    } catch {
        try {
            new URL('https://' + url);
            return url.includes('.');
        } catch {
            return false;
        }
    }
};

const normalizeUrl = (url: string): string => {
    if (!url.startsWith('http://') && !url.startsWith('https://')) {
        return 'https://' + url;
    }
    return url;
};

const checkCooldown = async (url: string) => {
    if (!isValidUrlForCheck(url)) {
        cooldown.value = null;
        return;
    }

    const normalizedUrl = normalizeUrl(url);
    checkingCooldown.value = true;

    try {
        const response = await fetch('/scan/check-cooldown', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ url: normalizedUrl }),
        });
        if (response.ok) {
            cooldown.value = await response.json();
        } else {
            cooldown.value = null;
        }
    } catch {
        cooldown.value = null;
    } finally {
        checkingCooldown.value = false;
    }
};

watch(() => scanForm.url, (newUrl) => {
    if (cooldownCheckTimeout) {
        clearTimeout(cooldownCheckTimeout);
    }
    cooldown.value = null;

    if (newUrl && newUrl.length > 3) {
        cooldownCheckTimeout = setTimeout(() => {
            checkCooldown(newUrl);
        }, 500);
    }
});

const submitScan = () => {
    if (isOnCooldown.value) return;
    scanForm.team_id = props.currentTeamId ?? null;
    scanForm.post('/scan', {
        preserveScroll: true,
    });
};

const viewExistingScan = () => {
    if (cooldown.value?.existing_scan_uuid) {
        router.visit(`/scans/${cooldown.value.existing_scan_uuid}`);
    }
};

// Bulk scan
const bulkUrls = ref('');
const bulkProcessing = ref(false);
const bulkError = ref<string | null>(null);

interface BulkScanResult {
    uuid: string;
    url: string;
    title: string | null;
    status: 'pending' | 'processing' | 'completed' | 'failed';
    score: number | null;
    grade: string | null;
    error_message: string | null;
}

const bulkScans = ref<BulkScanResult[]>([]);
const bulkSkipped = ref({ cooldown: 0, invalid: 0 });
let bulkPollInterval: ReturnType<typeof setInterval> | null = null;

const bulkUrlCount = computed(() => {
    if (!bulkUrls.value.trim()) return 0;
    return bulkUrls.value.split('\n').filter(line => line.trim()).length;
});

const bulkCompleted = computed(() => bulkScans.value.filter(s => s.status === 'completed' || s.status === 'failed').length);
const bulkTotal = computed(() => bulkScans.value.length);
const bulkAllDone = computed(() => bulkTotal.value > 0 && bulkCompleted.value === bulkTotal.value);

const submitBulkScan = async () => {
    bulkProcessing.value = true;
    bulkError.value = null;
    bulkScans.value = [];

    try {
        const response = await fetch('/scans/bulk', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify({ urls: bulkUrls.value }),
        });

        const data = await response.json();

        if (!response.ok) {
            bulkError.value = data.errors?.urls || data.errors?.quota || data.errors?.feature || data.message || 'Failed to start scans';
            bulkProcessing.value = false;
            return;
        }

        bulkScans.value = data.scans;
        bulkSkipped.value = data.skipped;
        bulkUrls.value = '';

        // Start polling for status
        startBulkPolling();
    } catch (e) {
        bulkError.value = 'Failed to start scans. Please try again.';
        bulkProcessing.value = false;
    }
};

const startBulkPolling = () => {
    if (bulkPollInterval) clearInterval(bulkPollInterval);

    bulkPollInterval = setInterval(async () => {
        const pendingUuids = bulkScans.value
            .filter(s => s.status === 'pending' || s.status === 'processing')
            .map(s => s.uuid);

        if (pendingUuids.length === 0) {
            if (bulkPollInterval) clearInterval(bulkPollInterval);
            bulkProcessing.value = false;
            // Refresh the page to show new scans in the list
            router.reload({ only: ['scans'] });
            return;
        }

        try {
            const response = await fetch('/scans/bulk/status', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({ uuids: pendingUuids }),
            });

            if (response.ok) {
                const data = await response.json();
                data.scans.forEach((updated: BulkScanResult) => {
                    const idx = bulkScans.value.findIndex(s => s.uuid === updated.uuid);
                    if (idx !== -1) {
                        bulkScans.value[idx] = updated;
                    }
                });
            }
        } catch (e) {
            // Silently ignore polling errors
        }
    }, 2000);
};

const resetBulkForm = () => {
    if (bulkPollInterval) clearInterval(bulkPollInterval);
    bulkScans.value = [];
    bulkSkipped.value = { cooldown: 0, invalid: 0 };
    bulkProcessing.value = false;
    bulkError.value = null;
};

const getGradeColorBulk = (grade: string | null) => {
    if (!grade) return 'bg-muted text-muted-foreground';
    if (grade.startsWith('A')) return 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300';
    if (grade.startsWith('B')) return 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300';
    if (grade.startsWith('C')) return 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300';
    if (grade.startsWith('D')) return 'bg-orange-100 text-orange-700 dark:bg-orange-900 dark:text-orange-300';
    return 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300';
};

const getScoreColorBulk = (score: number) => {
    if (score >= 80) return 'text-green-600 dark:text-green-400';
    if (score >= 60) return 'text-blue-600 dark:text-blue-400';
    if (score >= 40) return 'text-yellow-600 dark:text-yellow-400';
    return 'text-red-600 dark:text-red-400';
};

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

const getScoreColorByGrade = (grade: string) => {
    if (grade.startsWith('A')) return 'text-green-600 dark:text-green-400';
    if (grade.startsWith('B')) return 'text-blue-600 dark:text-blue-400';
    if (grade.startsWith('C')) return 'text-yellow-600 dark:text-yellow-400';
    if (grade.startsWith('D')) return 'text-orange-600 dark:text-orange-400';
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

            <!-- New Scan Section -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Globe class="h-5 w-5 text-primary" />
                        New Scan
                    </CardTitle>
                    <CardDescription>
                        {{ canBulkScan ? 'Scan a single URL or multiple URLs at once' : 'Enter a URL to analyze its GEO score' }}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <Tabs :default-value="canBulkScan ? 'single' : 'single'" class="w-full">
                        <TabsList v-if="canBulkScan" class="mb-4">
                            <TabsTrigger value="single">
                                <Globe class="mr-2 h-4 w-4" />
                                Single URL
                            </TabsTrigger>
                            <TabsTrigger value="bulk">
                                <Layers class="mr-2 h-4 w-4" />
                                Bulk Scan
                            </TabsTrigger>
                        </TabsList>

                        <!-- Single URL Scan -->
                        <TabsContent value="single" class="mt-0">
                            <form @submit.prevent="submitScan" class="space-y-4">
                                <div class="flex gap-3">
                                    <div class="flex-1">
                                        <Input
                                            v-model="scanForm.url"
                                            type="url"
                                            placeholder="https://example.com/page"
                                            class="h-11"
                                            :disabled="scanForm.processing || !usage.can_scan"
                                        />
                                    </div>
                                    <Button
                                        type="submit"
                                        :disabled="scanForm.processing || !scanForm.url || !usage.can_scan || isOnCooldown || checkingCooldown"
                                        class="h-11 px-6"
                                    >
                                        <Clock v-if="isOnCooldown" class="mr-2 h-4 w-4" />
                                        <svg v-else-if="scanForm.processing || checkingCooldown" class="mr-2 h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                                        </svg>
                                        <template v-if="isOnCooldown">
                                            {{ cooldownMinutes }}m cooldown
                                        </template>
                                        <template v-else-if="checkingCooldown">
                                            Checking...
                                        </template>
                                        <template v-else>
                                            {{ scanForm.processing ? 'Scanning...' : 'Scan URL' }}
                                        </template>
                                    </Button>
                                </div>

                                <Alert v-if="scanForm.errors.url" variant="destructive">
                                    <AlertDescription>{{ scanForm.errors.url }}</AlertDescription>
                                </Alert>
                                <Alert v-if="scanForm.errors.cooldown" class="border-amber-500/50 bg-amber-50 text-amber-800 dark:border-amber-500/30 dark:bg-amber-950/50 dark:text-amber-200">
                                    <Clock class="h-4 w-4 text-amber-600 dark:text-amber-400" />
                                    <AlertDescription>{{ scanForm.errors.cooldown }}</AlertDescription>
                                </Alert>
                                <Alert v-else-if="isOnCooldown" class="border-amber-500/50 bg-amber-50 text-amber-800 dark:border-amber-500/30 dark:bg-amber-950/50 dark:text-amber-200">
                                    <Clock class="h-4 w-4 text-amber-600 dark:text-amber-400" />
                                    <AlertDescription class="flex items-center justify-between gap-4">
                                        <span>This URL was scanned recently. Please wait {{ cooldownMinutes }} {{ cooldownMinutes === 1 ? 'minute' : 'minutes' }} before scanning again.</span>
                                        <Button v-if="cooldown?.existing_scan_uuid" variant="outline" size="sm" @click="viewExistingScan" class="shrink-0">
                                            View Existing Scan
                                        </Button>
                                    </AlertDescription>
                                </Alert>
                                <Alert v-if="scanForm.errors.limit" variant="destructive">
                                    <AlertDescription>
                                        {{ scanForm.errors.limit }}
                                        <Link href="/billing/plans" class="ml-2 underline">Upgrade now</Link>
                                    </AlertDescription>
                                </Alert>
                                <Alert v-if="!usage.can_scan && !scanForm.errors.limit" variant="destructive">
                                    <AlertDescription>
                                        You've reached your monthly scan limit.
                                        <Link href="/billing/plans" class="ml-1 underline">Upgrade your plan</Link> to continue scanning.
                                    </AlertDescription>
                                </Alert>
                            </form>
                        </TabsContent>

                        <!-- Bulk URL Scan (Agency only) -->
                        <TabsContent v-if="canBulkScan" value="bulk" class="mt-0">
                            <!-- Progress State - Show scan results as they complete -->
                            <div v-if="bulkScans.length > 0" class="space-y-4">
                                <!-- Progress Header -->
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h3 class="font-semibold">
                                            {{ bulkAllDone ? 'All Scans Complete' : 'Scanning in Progress...' }}
                                        </h3>
                                        <p class="text-sm text-muted-foreground">
                                            {{ bulkCompleted }} of {{ bulkTotal }} completed
                                            <template v-if="bulkSkipped.cooldown > 0 || bulkSkipped.invalid > 0">
                                                <span class="text-muted-foreground">
                                                    ({{ bulkSkipped.cooldown > 0 ? `${bulkSkipped.cooldown} on cooldown` : '' }}{{ bulkSkipped.cooldown > 0 && bulkSkipped.invalid > 0 ? ', ' : '' }}{{ bulkSkipped.invalid > 0 ? `${bulkSkipped.invalid} invalid` : '' }})
                                                </span>
                                            </template>
                                        </p>
                                    </div>
                                    <div v-if="!bulkAllDone" class="flex items-center gap-2 text-sm text-muted-foreground">
                                        <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                                        </svg>
                                        Processing...
                                    </div>
                                    <Button v-else variant="outline" size="sm" @click="resetBulkForm">
                                        Scan More URLs
                                    </Button>
                                </div>

                                <!-- Progress Bar -->
                                <div class="h-2 overflow-hidden rounded-full bg-muted">
                                    <div
                                        class="h-full bg-primary transition-all duration-500"
                                        :style="{ width: `${(bulkCompleted / bulkTotal) * 100}%` }"
                                    />
                                </div>

                                <!-- Scan Results List -->
                                <div class="max-h-[300px] space-y-2 overflow-y-auto">
                                    <div
                                        v-for="scan in bulkScans"
                                        :key="scan.uuid"
                                        class="flex items-center gap-3 rounded-lg border p-3"
                                        :class="scan.status === 'completed' ? 'bg-background' : scan.status === 'failed' ? 'bg-red-50 dark:bg-red-950/20' : 'bg-muted/50'"
                                    >
                                        <!-- Status Icon -->
                                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full" :class="getGradeColorBulk(scan.grade)">
                                            <template v-if="scan.status === 'completed'">
                                                <span class="text-sm font-bold">{{ scan.grade }}</span>
                                            </template>
                                            <template v-else-if="scan.status === 'failed'">
                                                <XCircle class="h-5 w-5 text-red-600" />
                                            </template>
                                            <template v-else>
                                                <svg class="h-5 w-5 animate-spin text-muted-foreground" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                                                </svg>
                                            </template>
                                        </div>

                                        <!-- URL & Title -->
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate text-sm font-medium">
                                                {{ scan.title || scan.url }}
                                            </p>
                                            <p class="truncate text-xs text-muted-foreground">
                                                {{ scan.url }}
                                            </p>
                                        </div>

                                        <!-- Score & Action -->
                                        <div class="flex items-center gap-3">
                                            <template v-if="scan.status === 'completed'">
                                                <span class="text-lg font-bold" :class="getScoreColorBulk(scan.score || 0)">
                                                    {{ scan.score?.toFixed(1) }}
                                                </span>
                                                <Link :href="`/scans/${scan.uuid}`">
                                                    <Button variant="ghost" size="sm">
                                                        <ExternalLink class="h-4 w-4" />
                                                    </Button>
                                                </Link>
                                            </template>
                                            <template v-else-if="scan.status === 'failed'">
                                                <span class="text-xs text-red-600">Failed</span>
                                            </template>
                                            <template v-else>
                                                <span class="text-xs text-muted-foreground">Scanning...</span>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Form State -->
                            <form v-else @submit.prevent="submitBulkScan" class="space-y-4">
                                <div class="space-y-2">
                                    <Label for="bulk-urls">URLs to Scan (one per line, max 50)</Label>
                                    <Textarea
                                        id="bulk-urls"
                                        v-model="bulkUrls"
                                        placeholder="https://example.com/page1
https://example.com/page2
https://example.com/page3"
                                        class="min-h-[150px] font-mono text-sm"
                                        :disabled="bulkProcessing"
                                    />
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-muted-foreground">
                                            {{ bulkUrlCount }} URL{{ bulkUrlCount !== 1 ? 's' : '' }} entered
                                        </span>
                                        <span v-if="bulkUrlCount > 50" class="text-destructive">
                                            Maximum 50 URLs allowed
                                        </span>
                                    </div>
                                </div>

                                <Alert v-if="bulkError" variant="destructive">
                                    <AlertDescription>{{ bulkError }}</AlertDescription>
                                </Alert>

                                <Button
                                    type="submit"
                                    :disabled="bulkProcessing || bulkUrlCount === 0 || bulkUrlCount > 50"
                                >
                                    <svg v-if="bulkProcessing" class="mr-2 h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                                    </svg>
                                    {{ bulkProcessing ? 'Starting...' : `Scan ${bulkUrlCount} URL${bulkUrlCount !== 1 ? 's' : ''}` }}
                                </Button>
                            </form>
                        </TabsContent>
                    </Tabs>

                    <!-- Upgrade prompt for non-Agency users -->
                    <div v-if="!canBulkScan" class="mt-4 rounded-lg border border-dashed p-4">
                        <div class="flex items-center gap-3">
                            <Layers class="h-8 w-8 text-muted-foreground" />
                            <div class="flex-1">
                                <p class="font-medium">Need to scan multiple URLs?</p>
                                <p class="text-sm text-muted-foreground">Upgrade to Agency for bulk URL scanning (up to 50 at once)</p>
                            </div>
                            <Link href="/billing/plans">
                                <Button variant="outline" size="sm">Upgrade</Button>
                            </Link>
                        </div>
                    </div>
                </CardContent>
            </Card>

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
                                Enter a URL above to start scanning
                            </p>
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
                                    v-if="scan.status === 'completed' && scan.grade"
                                    class="flex h-12 w-12 items-center justify-center rounded-full text-lg font-bold"
                                    :class="getGradeColor(scan.grade)"
                                >
                                    {{ scan.grade }}
                                </div>
                                <div
                                    v-else-if="scan.status === 'failed'"
                                    class="flex h-12 w-12 items-center justify-center rounded-full bg-red-100 text-red-600 dark:bg-red-950 dark:text-red-400"
                                >
                                    <XCircle class="h-6 w-6" />
                                </div>
                                <div
                                    v-else-if="scan.status === 'cancelled'"
                                    class="flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400"
                                >
                                    <XCircle class="h-6 w-6" />
                                </div>
                                <div
                                    v-else
                                    class="flex h-12 w-12 items-center justify-center rounded-full bg-muted"
                                >
                                    <Loader2 class="h-6 w-6 animate-spin text-muted-foreground" />
                                </div>

                                <!-- Info -->
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        <p class="font-medium">{{ scan.title || 'Untitled' }}</p>
                                        <span
                                            v-if="scan.scheduled_scan_id"
                                            class="inline-flex items-center gap-1 rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-900 dark:text-blue-300"
                                        >
                                            <Repeat class="h-3 w-3" />
                                            Scheduled
                                        </span>
                                    </div>
                                    <p class="flex items-center gap-1 text-sm text-muted-foreground">
                                        <ExternalLink class="h-3 w-3" />
                                        {{ truncateUrl(scan.url) }}
                                    </p>
                                    <p v-if="scan.user" class="mt-1 text-xs text-muted-foreground">
                                        by {{ scan.user.name }}
                                    </p>
                                </div>

                                <!-- Score -->
                                <div v-if="scan.status === 'completed' && scan.score !== null" class="text-right">
                                    <p class="text-2xl font-bold" :class="getScoreColorByGrade(scan.grade)">
                                        {{ scan.score.toFixed(1) }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">/ {{ scan.results?.max_score ?? 100 }}</p>
                                </div>
                                <div v-else-if="scan.status === 'failed'" class="text-right">
                                    <p class="text-sm font-medium text-red-600 dark:text-red-400">Failed</p>
                                </div>
                                <div v-else-if="scan.status === 'cancelled'" class="text-right">
                                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Cancelled</p>
                                </div>
                                <div v-else class="text-right">
                                    <p class="text-sm font-medium text-muted-foreground capitalize">{{ scan.status || 'pending' }}</p>
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
