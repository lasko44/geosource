<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Calendar, Clock, Plus, Play, Pause, Pencil, Trash2, ExternalLink, RefreshCw } from 'lucide-vue-next';

import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import { useDateFormat } from '@/composables/useDateFormat';
import { type BreadcrumbItem } from '@/types';

const { formatDate, formatRelative } = useDateFormat();

interface ScheduledScan {
    id: number;
    uuid: string;
    url: string;
    name: string;
    frequency: 'daily' | 'weekly' | 'monthly';
    scheduled_time: string;
    day_of_week: number | null;
    day_of_month: number | null;
    is_active: boolean;
    last_run_at: string | null;
    next_run_at: string | null;
    total_runs: number;
    schedule_description: string;
    user: { name: string } | null;
    created_at: string;
}

interface RecentScheduledRun {
    uuid: string;
    url: string;
    title: string | null;
    score: number | null;
    grade: string | null;
    status: 'pending' | 'processing' | 'completed' | 'failed' | 'cancelled';
    scheduled_scan_name: string | null;
    user: { name: string } | null;
    created_at: string;
}

interface Props {
    scheduledScans: ScheduledScan[];
    recentScheduledRuns: RecentScheduledRun[];
    currentTeamId: number | null;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Scheduled Scans', href: '/scheduled-scans' },
];

const toggleActive = (scan: ScheduledScan) => {
    router.post(`/scheduled-scans/${scan.uuid}/toggle`, {}, {
        preserveScroll: true,
    });
};

const runNow = (scan: ScheduledScan) => {
    router.post(`/scheduled-scans/${scan.uuid}/run`);
};

const deleteScan = (scan: ScheduledScan) => {
    router.delete(`/scheduled-scans/${scan.uuid}`, {
        preserveScroll: true,
    });
};

const truncateUrl = (url: string, maxLength = 40) => {
    if (url.length <= maxLength) return url;
    return url.substring(0, maxLength) + '...';
};

const getFrequencyBadgeColor = (frequency: string) => {
    switch (frequency) {
        case 'daily': return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
        case 'weekly': return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200';
        case 'monthly': return 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200';
        default: return '';
    }
};

const getGradeColor = (grade: string) => {
    if (grade.startsWith('A')) return 'text-green-600 bg-green-100 dark:text-green-400 dark:bg-green-950';
    if (grade.startsWith('B')) return 'text-blue-600 bg-blue-100 dark:text-blue-400 dark:bg-blue-950';
    if (grade.startsWith('C')) return 'text-yellow-600 bg-yellow-100 dark:text-yellow-400 dark:bg-yellow-950';
    if (grade.startsWith('D')) return 'text-orange-600 bg-orange-100 dark:text-orange-400 dark:bg-orange-950';
    return 'text-red-600 bg-red-100 dark:text-red-400 dark:bg-red-950';
};

const getScoreColor = (grade: string) => {
    if (grade.startsWith('A')) return 'text-green-600 dark:text-green-400';
    if (grade.startsWith('B')) return 'text-blue-600 dark:text-blue-400';
    if (grade.startsWith('C')) return 'text-yellow-600 dark:text-yellow-400';
    if (grade.startsWith('D')) return 'text-orange-600 dark:text-orange-400';
    return 'text-red-600 dark:text-red-400';
};
</script>

<template>
    <Head title="Scheduled Scans" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Scheduled Scans</h1>
                    <p class="text-muted-foreground">Automate your GEO scans on a recurring schedule</p>
                </div>
                <Link href="/scheduled-scans/create">
                    <Button>
                        <Plus class="mr-2 h-4 w-4" />
                        New Schedule
                    </Button>
                </Link>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Calendar class="h-5 w-5" />
                        Your Scheduled Scans
                    </CardTitle>
                    <CardDescription>
                        Manage your automated scanning schedules
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="scheduledScans.length === 0" class="py-12 text-center">
                        <Calendar class="mx-auto h-12 w-12 text-muted-foreground/50" />
                        <h3 class="mt-4 text-lg font-medium">No scheduled scans yet</h3>
                        <p class="mt-2 text-sm text-muted-foreground">
                            Create a schedule to automatically run GEO scans on your URLs
                        </p>
                        <Link href="/scheduled-scans/create" class="mt-4 inline-block">
                            <Button>
                                <Plus class="mr-2 h-4 w-4" />
                                Create Schedule
                            </Button>
                        </Link>
                    </div>

                    <Table v-else>
                        <TableHeader>
                            <TableRow>
                                <TableHead>URL</TableHead>
                                <TableHead>Schedule</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Last Run</TableHead>
                                <TableHead>Next Run</TableHead>
                                <TableHead>Runs</TableHead>
                                <TableHead class="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="scan in scheduledScans" :key="scan.uuid">
                                <TableCell>
                                    <div>
                                        <p class="font-medium">{{ scan.name }}</p>
                                        <a
                                            :href="scan.url"
                                            target="_blank"
                                            class="flex items-center gap-1 text-xs text-muted-foreground hover:text-primary"
                                        >
                                            {{ truncateUrl(scan.url) }}
                                            <ExternalLink class="h-3 w-3" />
                                        </a>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <div class="flex flex-col gap-1">
                                        <Badge :class="getFrequencyBadgeColor(scan.frequency)" variant="secondary">
                                            {{ scan.frequency }}
                                        </Badge>
                                        <span class="text-xs text-muted-foreground">{{ scan.schedule_description }}</span>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <Badge :variant="scan.is_active ? 'default' : 'secondary'">
                                        {{ scan.is_active ? 'Active' : 'Paused' }}
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    <span v-if="scan.last_run_at" class="text-sm">
                                        {{ formatRelative(scan.last_run_at) }}
                                    </span>
                                    <span v-else class="text-sm text-muted-foreground">Never</span>
                                </TableCell>
                                <TableCell>
                                    <span v-if="scan.is_active && scan.next_run_at" class="text-sm">
                                        {{ formatRelative(scan.next_run_at) }}
                                    </span>
                                    <span v-else class="text-sm text-muted-foreground">-</span>
                                </TableCell>
                                <TableCell>
                                    <span class="text-sm">{{ scan.total_runs }}</span>
                                </TableCell>
                                <TableCell class="text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            @click="runNow(scan)"
                                            title="Run now"
                                        >
                                            <RefreshCw class="h-4 w-4" />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            @click="toggleActive(scan)"
                                            :title="scan.is_active ? 'Pause' : 'Activate'"
                                        >
                                            <Pause v-if="scan.is_active" class="h-4 w-4" />
                                            <Play v-else class="h-4 w-4" />
                                        </Button>
                                        <Link :href="`/scheduled-scans/${scan.uuid}/edit`">
                                            <Button variant="ghost" size="icon" title="Edit">
                                                <Pencil class="h-4 w-4" />
                                            </Button>
                                        </Link>
                                        <AlertDialog>
                                            <AlertDialogTrigger as-child>
                                                <Button variant="ghost" size="icon" title="Delete">
                                                    <Trash2 class="h-4 w-4 text-destructive" />
                                                </Button>
                                            </AlertDialogTrigger>
                                            <AlertDialogContent>
                                                <AlertDialogHeader>
                                                    <AlertDialogTitle>Delete Scheduled Scan</AlertDialogTitle>
                                                    <AlertDialogDescription>
                                                        Are you sure you want to delete this scheduled scan? This action cannot be undone.
                                                    </AlertDialogDescription>
                                                </AlertDialogHeader>
                                                <AlertDialogFooter>
                                                    <AlertDialogCancel>Cancel</AlertDialogCancel>
                                                    <AlertDialogAction @click="deleteScan(scan)" class="bg-destructive text-destructive-foreground hover:bg-destructive/90">
                                                        Delete
                                                    </AlertDialogAction>
                                                </AlertDialogFooter>
                                            </AlertDialogContent>
                                        </AlertDialog>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            <!-- Recent Scheduled Scan Results -->
            <Card v-if="recentScheduledRuns.length > 0">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Clock class="h-5 w-5" />
                        Recent Scheduled Scan Results
                    </CardTitle>
                    <CardDescription>
                        The last 10 scans that ran from your schedules
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="space-y-3">
                        <Link
                            v-for="scan in recentScheduledRuns"
                            :key="scan.uuid"
                            :href="`/scans/${scan.uuid}`"
                            class="flex items-center justify-between rounded-lg border p-4 transition-colors hover:bg-muted/50"
                        >
                            <div class="flex items-center gap-4">
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
                                    <span class="text-xs font-medium">FAIL</span>
                                </div>
                                <div
                                    v-else-if="scan.status === 'cancelled'"
                                    class="flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400"
                                >
                                    <span class="text-xs font-medium">X</span>
                                </div>
                                <div
                                    v-else
                                    class="flex h-12 w-12 items-center justify-center rounded-full bg-muted"
                                >
                                    <RefreshCw class="h-5 w-5 animate-spin text-muted-foreground" />
                                </div>

                                <!-- Info -->
                                <div class="flex-1">
                                    <p class="font-medium">{{ scan.title || 'Untitled' }}</p>
                                    <p class="flex items-center gap-1 text-sm text-muted-foreground">
                                        <ExternalLink class="h-3 w-3" />
                                        {{ truncateUrl(scan.url) }}
                                    </p>
                                    <p v-if="scan.scheduled_scan_name" class="mt-1 text-xs text-muted-foreground">
                                        from <span class="font-medium">{{ scan.scheduled_scan_name }}</span>
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-4">
                                <!-- Score -->
                                <div v-if="scan.status === 'completed' && scan.score !== null" class="text-right">
                                    <p class="text-2xl font-bold" :class="getScoreColor(scan.grade || '')">
                                        {{ scan.score.toFixed(1) }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">/ 100</p>
                                </div>
                                <div v-else-if="scan.status === 'failed'" class="text-right">
                                    <Badge variant="destructive">Failed</Badge>
                                </div>
                                <div v-else-if="scan.status === 'cancelled'" class="text-right">
                                    <Badge variant="secondary">Cancelled</Badge>
                                </div>
                                <div v-else class="text-right">
                                    <Badge variant="secondary">{{ scan.status }}</Badge>
                                </div>

                                <!-- Date -->
                                <div class="w-28 text-right text-sm text-muted-foreground">
                                    {{ formatRelative(scan.created_at) }}
                                </div>
                            </div>
                        </Link>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
