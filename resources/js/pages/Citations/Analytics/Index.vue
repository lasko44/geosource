<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Plus,
    RefreshCw,
    Trash2,
    ExternalLink,
    TrendingUp,
    Users,
    Eye,
    Clock,
    AlertCircle,
    CheckCircle2,
    XCircle,
} from 'lucide-vue-next';
import { computed, ref, onUnmounted } from 'vue';

import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { useDateFormat } from '@/composables/useDateFormat';
import { type BreadcrumbItem } from '@/types';

const { formatDate, formatRelative } = useDateFormat();

const page = usePage();

// Get flash messages and errors
const flashSuccess = computed(() => page.props.flash?.success as string | undefined);
const errors = computed(() => page.props.errors as Record<string, string> | undefined);

interface GA4Connection {
    id: number;
    uuid: string;
    property_id: string;
    property_name: string;
    is_active: boolean;
    last_synced_at: string | null;
    sync_status: 'idle' | 'syncing' | 'completed' | 'failed';
    sync_error: string | null;
    created_at: string;
}

interface TrafficSummary {
    total_sessions: number;
    total_users: number;
    total_pageviews: number;
    by_source: {
        source: string;
        sessions: number;
        users: number;
        pageviews: number;
        percentage: number;
    }[];
}

interface TrafficTrend {
    date: string;
    sessions: number;
    users: number;
    pageviews: number;
}

interface Usage {
    can_access: boolean;
    connections_count: number;
    connections_limit: number;
    connections_remaining: number;
    connections_is_unlimited: boolean;
    can_create_connection: boolean;
}

interface Props {
    connections: GA4Connection[];
    trafficData: Record<number, {
        summary: TrafficSummary;
        trend: TrafficTrend[];
    }>;
    usage: Usage;
    aiSources: string[];
    currentTeam: { id: number; name: string } | null;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Citation Tracking', href: '/citations' },
    { title: 'GA4 Analytics', href: '/analytics/ga4' },
];

const syncing = ref<number | null>(null);
const deleting = ref<number | null>(null);
const syncStatuses = ref<Record<number, { status: string; error: string | null }>>({});
const pollIntervals = ref<Record<number, ReturnType<typeof setInterval>>>({});

// Initialize sync statuses from props
props.connections.forEach(conn => {
    syncStatuses.value[conn.id] = {
        status: conn.sync_status || 'idle',
        error: conn.sync_error,
    };
});

const pollSyncStatus = (connection: GA4Connection) => {
    // Clear any existing interval for this connection
    if (pollIntervals.value[connection.id]) {
        clearInterval(pollIntervals.value[connection.id]);
    }

    // Start polling
    pollIntervals.value[connection.id] = setInterval(async () => {
        try {
            const response = await fetch(`/analytics/ga4/${connection.uuid}/sync-status`);
            const data = await response.json();

            syncStatuses.value[connection.id] = {
                status: data.sync_status,
                error: data.sync_error,
            };

            // Stop polling if sync is complete or failed
            if (data.sync_status !== 'syncing') {
                clearInterval(pollIntervals.value[connection.id]);
                delete pollIntervals.value[connection.id];
                syncing.value = null;

                // Refresh the page data if sync completed successfully
                if (data.sync_status === 'completed') {
                    router.reload({ only: ['connections', 'trafficData'] });
                }
            }
        } catch (error) {
            console.error('Failed to poll sync status:', error);
        }
    }, 2000); // Poll every 2 seconds
};

const syncConnection = (connection: GA4Connection) => {
    syncing.value = connection.id;
    syncStatuses.value[connection.id] = { status: 'syncing', error: null };

    router.post(`/analytics/ga4/${connection.uuid}/sync`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            // Start polling for status updates
            pollSyncStatus(connection);
        },
        onError: () => {
            syncing.value = null;
            syncStatuses.value[connection.id] = { status: 'failed', error: 'Failed to start sync' };
        },
    });
};

const isSyncing = (connection: GA4Connection) => {
    return syncStatuses.value[connection.id]?.status === 'syncing' || syncing.value === connection.id;
};

const getSyncStatus = (connection: GA4Connection) => {
    return syncStatuses.value[connection.id]?.status || connection.sync_status || 'idle';
};

const getSyncError = (connection: GA4Connection) => {
    return syncStatuses.value[connection.id]?.error || connection.sync_error;
};

const deleteConnection = (connection: GA4Connection) => {
    if (confirm(`Are you sure you want to disconnect ${connection.property_name}?`)) {
        deleting.value = connection.id;
        router.delete(`/analytics/ga4/${connection.uuid}`);
    }
};

// Cleanup polling intervals on unmount
onUnmounted(() => {
    Object.values(pollIntervals.value).forEach(interval => clearInterval(interval));
});

const getSourceColor = (source: string) => {
    const colors: Record<string, string> = {
        'perplexity.ai': 'bg-teal-500',
        'chat.openai.com': 'bg-green-500',
        'chatgpt.com': 'bg-green-500',
        'claude.ai': 'bg-orange-500',
        'gemini.google.com': 'bg-blue-500',
        'copilot.microsoft.com': 'bg-blue-600',
        'you.com': 'bg-purple-500',
        'phind.com': 'bg-pink-500',
    };
    return colors[source] || 'bg-gray-500';
};

const getSourceName = (source: string) => {
    const names: Record<string, string> = {
        'perplexity.ai': 'Perplexity',
        'chat.openai.com': 'ChatGPT',
        'chatgpt.com': 'ChatGPT',
        'claude.ai': 'Claude',
        'gemini.google.com': 'Gemini',
        'copilot.microsoft.com': 'Copilot',
        'you.com': 'You.com',
        'phind.com': 'Phind',
    };
    return names[source] || source;
};

const totalAITraffic = computed(() => {
    let sessions = 0;
    let users = 0;
    let pageviews = 0;

    Object.values(props.trafficData).forEach(data => {
        sessions += data.summary.total_sessions;
        users += data.summary.total_users;
        pageviews += data.summary.total_pageviews;
    });

    return { sessions, users, pageviews };
});
</script>

<template>
    <Head title="GA4 Analytics - AI Referral Traffic" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <Link href="/citations">
                        <Button variant="ghost" size="sm">
                            <ArrowLeft class="mr-1 h-4 w-4" />
                            Back to Citations
                        </Button>
                    </Link>
                    <h1 class="mt-2 text-2xl font-bold">GA4 Analytics</h1>
                    <p class="text-muted-foreground">
                        Track referral traffic from AI platforms
                        <span v-if="currentTeam" class="text-primary">
                            for {{ currentTeam.name }}
                        </span>
                    </p>
                </div>

                <div v-if="usage.can_create_connection">
                    <a href="/analytics/ga4/connect">
                        <Button>
                            <Plus class="mr-2 h-4 w-4" />
                            Connect GA4 Property
                        </Button>
                    </a>
                </div>
            </div>

            <!-- Success message (hide if it's just the sync started message) -->
            <Alert v-if="flashSuccess && !flashSuccess.includes('sync has been started')" variant="default" class="border-green-500 bg-green-50 dark:bg-green-950">
                <CheckCircle2 class="h-4 w-4 text-green-600" />
                <AlertDescription class="text-green-800 dark:text-green-200">
                    {{ flashSuccess }}
                </AlertDescription>
            </Alert>

            <!-- Error messages -->
            <Alert v-if="errors && Object.keys(errors).length > 0" variant="destructive">
                <XCircle class="h-4 w-4" />
                <AlertTitle>Connection Error</AlertTitle>
                <AlertDescription>
                    <ul class="list-disc list-inside">
                        <li v-for="(error, key) in errors" :key="key">{{ error }}</li>
                    </ul>
                </AlertDescription>
            </Alert>

            <!-- Usage info -->
            <Alert v-if="!usage.can_create_connection && connections.length === 0">
                <AlertCircle class="h-4 w-4" />
                <AlertDescription>
                    You've reached your GA4 connection limit. Upgrade your plan to connect more properties.
                </AlertDescription>
            </Alert>

            <!-- No connections -->
            <Card v-if="connections.length === 0">
                <CardContent class="text-center py-12">
                    <TrendingUp class="mx-auto h-12 w-12 text-muted-foreground" />
                    <h3 class="mt-4 text-lg font-medium">No GA4 Properties Connected</h3>
                    <p class="mt-2 text-muted-foreground max-w-md mx-auto">
                        Connect your Google Analytics 4 property to track referral traffic from AI platforms
                        like Perplexity, ChatGPT, Claude, and more.
                    </p>
                    <a v-if="usage.can_create_connection" href="/analytics/ga4/connect" class="mt-4 inline-block">
                        <Button>
                            <Plus class="mr-2 h-4 w-4" />
                            Connect GA4 Property
                        </Button>
                    </a>
                </CardContent>
            </Card>

            <!-- Connected properties with data -->
            <template v-else>
                <!-- Total AI Traffic Summary -->
                <div class="grid gap-4 md:grid-cols-3">
                    <Card>
                        <CardHeader class="pb-2">
                            <CardDescription>Total AI Sessions (30 days)</CardDescription>
                            <CardTitle class="text-2xl flex items-center gap-2">
                                <TrendingUp class="h-5 w-5 text-green-600" />
                                {{ totalAITraffic.sessions.toLocaleString() }}
                            </CardTitle>
                        </CardHeader>
                    </Card>

                    <Card>
                        <CardHeader class="pb-2">
                            <CardDescription>Total AI Users (30 days)</CardDescription>
                            <CardTitle class="text-2xl flex items-center gap-2">
                                <Users class="h-5 w-5 text-blue-600" />
                                {{ totalAITraffic.users.toLocaleString() }}
                            </CardTitle>
                        </CardHeader>
                    </Card>

                    <Card>
                        <CardHeader class="pb-2">
                            <CardDescription>Total AI Pageviews (30 days)</CardDescription>
                            <CardTitle class="text-2xl flex items-center gap-2">
                                <Eye class="h-5 w-5 text-purple-600" />
                                {{ totalAITraffic.pageviews.toLocaleString() }}
                            </CardTitle>
                        </CardHeader>
                    </Card>
                </div>

                <!-- Connections -->
                <div v-for="connection in connections" :key="connection.id" class="space-y-4">
                    <Card>
                        <CardHeader>
                            <div class="flex items-center justify-between">
                                <div>
                                    <CardTitle class="flex items-center gap-2">
                                        {{ connection.property_name }}
                                        <Badge :variant="connection.is_active ? 'default' : 'secondary'">
                                            {{ connection.is_active ? 'Active' : 'Inactive' }}
                                        </Badge>
                                    </CardTitle>
                                    <CardDescription class="flex items-center gap-2 mt-1">
                                        <span>{{ connection.property_id }}</span>
                                        <span v-if="connection.last_synced_at" class="flex items-center gap-1">
                                            <Clock class="h-3 w-3" />
                                            Last synced {{ formatRelative(connection.last_synced_at) }}
                                        </span>
                                    </CardDescription>
                                </div>
                                <div class="flex items-center gap-2">
                                    <!-- Sync Status Badge -->
                                    <Badge
                                        v-if="isSyncing(connection)"
                                        variant="secondary"
                                        class="gap-1"
                                    >
                                        <RefreshCw class="h-3 w-3 animate-spin" />
                                        Syncing...
                                    </Badge>
                                    <Badge
                                        v-else-if="getSyncStatus(connection) === 'failed'"
                                        variant="destructive"
                                        class="gap-1"
                                        :title="getSyncError(connection) || 'Sync failed'"
                                    >
                                        <XCircle class="h-3 w-3" />
                                        Failed
                                    </Badge>
                                    <Badge
                                        v-else-if="getSyncStatus(connection) === 'completed'"
                                        variant="default"
                                        class="gap-1 bg-green-600"
                                    >
                                        <CheckCircle2 class="h-3 w-3" />
                                        Synced
                                    </Badge>

                                    <Button
                                        variant="outline"
                                        size="sm"
                                        :disabled="isSyncing(connection) || !connection.is_active"
                                        @click="syncConnection(connection)"
                                    >
                                        <RefreshCw
                                            class="mr-2 h-4 w-4"
                                            :class="{ 'animate-spin': isSyncing(connection) }"
                                        />
                                        {{ isSyncing(connection) ? 'Syncing...' : 'Sync' }}
                                    </Button>
                                    <Button
                                        variant="destructive"
                                        size="sm"
                                        :disabled="deleting === connection.id || isSyncing(connection)"
                                        @click="deleteConnection(connection)"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <!-- Traffic by source -->
                            <div v-if="trafficData[connection.id]?.summary.by_source.length > 0">
                                <h4 class="font-medium mb-3">AI Platform Traffic (Last 30 Days)</h4>
                                <div class="space-y-3">
                                    <div
                                        v-for="source in trafficData[connection.id].summary.by_source"
                                        :key="source.source"
                                        class="flex items-center gap-3"
                                    >
                                        <span
                                            class="w-3 h-3 rounded-full shrink-0"
                                            :class="getSourceColor(source.source)"
                                        />
                                        <span class="w-24 font-medium">{{ getSourceName(source.source) }}</span>
                                        <div class="flex-1 bg-muted rounded-full h-2 overflow-hidden">
                                            <div
                                                class="h-full rounded-full"
                                                :class="getSourceColor(source.source)"
                                                :style="{ width: `${source.percentage}%` }"
                                            />
                                        </div>
                                        <span class="text-sm text-muted-foreground w-20 text-right">
                                            {{ source.sessions }} sessions
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-center py-8 text-muted-foreground">
                                <TrendingUp class="mx-auto h-8 w-8 mb-2" />
                                <p>No AI referral traffic recorded yet.</p>
                                <p class="text-sm">Data will appear after the next sync.</p>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- AI Sources Tracked -->
                <Card>
                    <CardHeader>
                        <CardTitle>AI Platforms Tracked</CardTitle>
                        <CardDescription>
                            We monitor referral traffic from these AI platforms
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="flex flex-wrap gap-2">
                            <Badge
                                v-for="source in aiSources"
                                :key="source"
                                variant="secondary"
                                class="flex items-center gap-1"
                            >
                                <span
                                    class="w-2 h-2 rounded-full"
                                    :class="getSourceColor(source)"
                                />
                                {{ getSourceName(source) }}
                            </Badge>
                        </div>
                    </CardContent>
                </Card>
            </template>
        </div>
    </AppLayout>
</template>
