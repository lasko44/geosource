<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Plus,
    Search,
    Bell,
    TrendingUp,
    CheckCircle2,
    XCircle,
    Clock,
    ExternalLink,
    BarChart3,
    RefreshCw,
    Loader2,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { useDateFormat } from '@/composables/useDateFormat';
import { type BreadcrumbItem } from '@/types';

const { formatDate, formatRelative } = useDateFormat();

interface CitationQuery {
    id: number;
    uuid: string;
    query: string;
    domain: string;
    brand: string | null;
    frequency: string;
    is_active: boolean;
    last_checked_at: string | null;
    next_check_at: string | null;
    checks: CitationCheck[];
}

interface CitationCheck {
    id: number;
    uuid: string;
    platform: string;
    status: string;
    is_cited: boolean | null;
    created_at: string;
    completed_at: string | null;
}

interface CitationAlert {
    id: number;
    type: string;
    platform: string;
    message: string;
    is_read: boolean;
    created_at: string;
}

interface Usage {
    can_access: boolean;
    queries_created: number;
    queries_limit: number;
    queries_remaining: number;
    queries_is_unlimited: boolean;
    can_create_query: boolean;
    checks_today: number;
    checks_limit: number;
    checks_remaining: number;
    checks_is_unlimited: boolean;
    can_perform_check: boolean;
    available_platforms: string[];
    available_frequencies: string[];
}

interface Props {
    queries: CitationQuery[];
    recentChecks: CitationCheck[];
    alerts: CitationAlert[];
    usage: Usage;
    platforms: Record<string, { name: string; color: string }>;
    currentTeam: { id: number; name: string } | null;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Citation Tracking', href: '/citations' },
];

const getPlatformColor = (platform: string) => {
    const colors: Record<string, string> = {
        perplexity: 'bg-teal-500',
        openai: 'bg-green-500',
        claude: 'bg-orange-500',
        gemini: 'bg-blue-500',
    };
    return colors[platform] || 'bg-gray-500';
};

const getPlatformName = (platform: string) => {
    return props.platforms[platform]?.name || platform;
};

const getFrequencyLabel = (frequency: string) => {
    const labels: Record<string, string> = {
        manual: 'Manual',
        daily: 'Daily',
        weekly: 'Weekly',
    };
    return labels[frequency] || frequency;
};

const getStatusIcon = (status: string, isCited: boolean | null) => {
    if (status === 'completed') {
        return isCited ? CheckCircle2 : XCircle;
    }
    if (status === 'processing' || status === 'pending') {
        return Loader2;
    }
    return Clock;
};

const getStatusColor = (status: string, isCited: boolean | null) => {
    if (status === 'completed') {
        return isCited
            ? 'text-green-600 dark:text-green-400'
            : 'text-red-600 dark:text-red-400';
    }
    if (status === 'processing' || status === 'pending') {
        return 'text-blue-600 dark:text-blue-400';
    }
    return 'text-gray-600 dark:text-gray-400';
};

const citedCount = computed(() => {
    return props.queries.filter(q =>
        q.checks.some(c => c.status === 'completed' && c.is_cited)
    ).length;
});

const markAlertRead = (alertId: number) => {
    router.post('/citations/alerts/mark-read', {
        alert_ids: [alertId],
    }, {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Citation Tracking" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Citation Tracking</h1>
                    <p class="text-muted-foreground">
                        Monitor how AI platforms cite your content
                        <span v-if="currentTeam" class="text-primary">
                            for {{ currentTeam.name }}
                        </span>
                    </p>
                </div>

                <div class="flex gap-2">
                    <Link href="/analytics/ga4">
                        <Button variant="outline">
                            <BarChart3 class="mr-2 h-4 w-4" />
                            GA4 Analytics
                        </Button>
                    </Link>
                    <Link v-if="usage.can_create_query" href="/citations/queries/create">
                        <Button>
                            <Plus class="mr-2 h-4 w-4" />
                            New Query
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Usage Stats -->
            <div class="grid gap-4 md:grid-cols-4">
                <Card>
                    <CardHeader class="pb-2">
                        <CardDescription>Queries</CardDescription>
                        <CardTitle class="text-2xl">
                            {{ usage.queries_created }}
                            <span v-if="!usage.queries_is_unlimited" class="text-base font-normal text-muted-foreground">
                                / {{ usage.queries_limit }}
                            </span>
                        </CardTitle>
                    </CardHeader>
                </Card>

                <Card>
                    <CardHeader class="pb-2">
                        <CardDescription>Checks Today</CardDescription>
                        <CardTitle class="text-2xl">
                            {{ usage.checks_today }}
                            <span v-if="!usage.checks_is_unlimited" class="text-base font-normal text-muted-foreground">
                                / {{ usage.checks_limit }}
                            </span>
                        </CardTitle>
                    </CardHeader>
                </Card>

                <Card>
                    <CardHeader class="pb-2">
                        <CardDescription>Queries Cited</CardDescription>
                        <CardTitle class="text-2xl text-green-600">
                            {{ citedCount }}
                            <span class="text-base font-normal text-muted-foreground">
                                / {{ queries.length }}
                            </span>
                        </CardTitle>
                    </CardHeader>
                </Card>

                <Card>
                    <CardHeader class="pb-2">
                        <CardDescription>Platforms</CardDescription>
                        <CardTitle class="text-2xl">
                            {{ usage.available_platforms.length }}
                        </CardTitle>
                    </CardHeader>
                </Card>
            </div>

            <!-- Alerts -->
            <div v-if="alerts.length > 0" class="space-y-2">
                <h2 class="text-lg font-semibold">Recent Alerts</h2>
                <Alert
                    v-for="alert in alerts.slice(0, 3)"
                    :key="alert.id"
                    :class="{
                        'border-green-300 bg-green-50 dark:border-green-800 dark:bg-green-950': alert.type === 'new_citation',
                        'border-red-300 bg-red-50 dark:border-red-800 dark:bg-red-950': alert.type === 'lost_citation',
                    }"
                >
                    <Bell class="h-4 w-4" />
                    <AlertTitle class="flex items-center justify-between">
                        {{ alert.type === 'new_citation' ? 'New Citation' : 'Lost Citation' }}
                        <Button
                            variant="ghost"
                            size="sm"
                            @click="markAlertRead(alert.id)"
                        >
                            Dismiss
                        </Button>
                    </AlertTitle>
                    <AlertDescription>
                        {{ alert.message }}
                        <span class="text-xs text-muted-foreground ml-2">
                            {{ formatRelative(alert.created_at) }}
                        </span>
                    </AlertDescription>
                </Alert>
            </div>

            <!-- Queries List -->
            <div>
                <h2 class="mb-4 text-lg font-semibold">Your Queries</h2>

                <div v-if="queries.length === 0" class="text-center py-12">
                    <Search class="mx-auto h-12 w-12 text-muted-foreground" />
                    <h3 class="mt-4 text-lg font-medium">No queries yet</h3>
                    <p class="mt-2 text-muted-foreground">
                        Create your first query to start tracking citations.
                    </p>
                    <Link v-if="usage.can_create_query" href="/citations/queries/create" class="mt-4 inline-block">
                        <Button>
                            <Plus class="mr-2 h-4 w-4" />
                            Create Query
                        </Button>
                    </Link>
                </div>

                <div v-else class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <Card
                        v-for="query in queries"
                        :key="query.id"
                        class="hover:border-primary/50 transition-colors cursor-pointer"
                        @click="router.visit(`/citations/queries/${query.uuid}`)"
                    >
                        <CardHeader class="pb-2">
                            <div class="flex items-start justify-between">
                                <div class="flex-1 min-w-0">
                                    <CardTitle class="text-base truncate">
                                        {{ query.query }}
                                    </CardTitle>
                                    <CardDescription class="flex items-center gap-1 mt-1">
                                        <ExternalLink class="h-3 w-3" />
                                        {{ query.domain }}
                                    </CardDescription>
                                </div>
                                <Badge :variant="query.is_active ? 'default' : 'secondary'">
                                    {{ getFrequencyLabel(query.frequency) }}
                                </Badge>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <!-- Latest check results by platform -->
                            <div class="flex flex-wrap gap-2 mt-2">
                                <div
                                    v-for="platform in usage.available_platforms"
                                    :key="platform"
                                    class="flex items-center gap-1 text-sm"
                                >
                                    <span
                                        class="w-2 h-2 rounded-full"
                                        :class="getPlatformColor(platform)"
                                    />
                                    <span class="text-muted-foreground">{{ getPlatformName(platform) }}</span>
                                    <component
                                        :is="getStatusIcon(
                                            query.checks.find(c => c.platform === platform)?.status || 'none',
                                            query.checks.find(c => c.platform === platform)?.is_cited ?? null
                                        )"
                                        class="h-4 w-4"
                                        :class="[
                                            getStatusColor(
                                                query.checks.find(c => c.platform === platform)?.status || 'none',
                                                query.checks.find(c => c.platform === platform)?.is_cited ?? null
                                            ),
                                            { 'animate-spin': query.checks.find(c => c.platform === platform)?.status === 'processing' }
                                        ]"
                                    />
                                </div>
                            </div>

                            <p v-if="query.last_checked_at" class="text-xs text-muted-foreground mt-3">
                                Last checked {{ formatRelative(query.last_checked_at) }}
                            </p>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- Recent Checks -->
            <div v-if="recentChecks.length > 0">
                <h2 class="mb-4 text-lg font-semibold">Recent Checks</h2>
                <Card>
                    <CardContent class="p-0">
                        <div class="divide-y">
                            <div
                                v-for="check in recentChecks"
                                :key="check.id"
                                class="flex items-center justify-between p-4"
                            >
                                <div class="flex items-center gap-3">
                                    <span
                                        class="w-3 h-3 rounded-full"
                                        :class="getPlatformColor(check.platform)"
                                    />
                                    <div>
                                        <p class="font-medium">{{ getPlatformName(check.platform) }}</p>
                                        <p class="text-sm text-muted-foreground">
                                            {{ formatRelative(check.created_at) }}
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <component
                                        :is="getStatusIcon(check.status, check.is_cited)"
                                        class="h-5 w-5"
                                        :class="[
                                            getStatusColor(check.status, check.is_cited),
                                            { 'animate-spin': check.status === 'processing' }
                                        ]"
                                    />
                                    <span
                                        v-if="check.status === 'completed'"
                                        class="text-sm font-medium"
                                        :class="check.is_cited ? 'text-green-600' : 'text-red-600'"
                                    >
                                        {{ check.is_cited ? 'Cited' : 'Not Cited' }}
                                    </span>
                                    <span
                                        v-else
                                        class="text-sm text-muted-foreground"
                                    >
                                        {{ check.status }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
