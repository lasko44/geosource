<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Play,
    Settings,
    Trash2,
    CheckCircle2,
    XCircle,
    Clock,
    Loader2,
    ExternalLink,
    Quote,
    AlertCircle,
} from 'lucide-vue-next';
import { computed, ref, onMounted, onUnmounted } from 'vue';

import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { useDateFormat } from '@/composables/useDateFormat';
import { type BreadcrumbItem } from '@/types';

const { formatDate, formatRelative } = useDateFormat();

interface Citation {
    type: string;
    match?: string;
    url?: string;
    context?: string;
}

interface CitationCheckMetadata {
    model?: string;
    raw_citations?: string[];
    confidence?: number;
    grounding_metadata?: Record<string, unknown>;
}

interface CitationCheck {
    id: number;
    uuid: string;
    platform: string;
    status: string;
    progress_step: string | null;
    progress_percent: number;
    is_cited: boolean | null;
    ai_response: string | null;
    citations: Citation[] | null;
    metadata: CitationCheckMetadata | null;
    error_message: string | null;
    created_at: string;
    completed_at: string | null;
}

interface CitationAlert {
    id: number;
    type: string;
    platform: string;
    message: string;
    created_at: string;
}

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
    alerts: CitationAlert[];
}

interface Usage {
    can_perform_check: boolean;
    checks_remaining: number;
    checks_is_unlimited: boolean;
    available_frequencies: string[];
}

interface Props {
    query: CitationQuery;
    usage: Usage;
    platforms: Record<string, { name: string; color: string }>;
    availablePlatforms: string[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Citation Tracking', href: '/citations' },
    { title: props.query.query.substring(0, 30) + '...', href: `/citations/queries/${props.query.uuid}` },
];

// Polling for in-progress checks
const pendingChecks = ref<Set<string>>(new Set());
let pollInterval: ReturnType<typeof setInterval> | null = null;

const updatePendingChecks = () => {
    const pending = new Set<string>();
    props.query.checks.forEach(check => {
        if (check.status === 'pending' || check.status === 'processing') {
            pending.add(check.uuid);
        }
    });
    pendingChecks.value = pending;
};

const pollCheckStatus = async () => {
    for (const uuid of pendingChecks.value) {
        try {
            const response = await fetch(`/citations/checks/${uuid}/status`);
            const data = await response.json();

            if (data.status === 'completed' || data.status === 'failed') {
                pendingChecks.value.delete(uuid);
                if (pendingChecks.value.size === 0 && pollInterval) {
                    clearInterval(pollInterval);
                    pollInterval = null;
                }
                router.reload({ only: ['query'] });
            }
        } catch {
            // Silent fail
        }
    }
};

onMounted(() => {
    updatePendingChecks();
    if (pendingChecks.value.size > 0) {
        pollInterval = setInterval(pollCheckStatus, 2000);
    }
});

onUnmounted(() => {
    if (pollInterval) clearInterval(pollInterval);
});

// Run check
const runningCheck = ref<string | null>(null);

const runCheck = (platform: string) => {
    runningCheck.value = platform;
    router.post(`/citations/queries/${props.query.uuid}/check`, {
        platform,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            updatePendingChecks();
            if (!pollInterval) {
                pollInterval = setInterval(pollCheckStatus, 2000);
            }
        },
        onFinish: () => {
            runningCheck.value = null;
        },
    });
};

// Edit modal
const showEditModal = ref(false);
const editForm = useForm({
    query: props.query.query,
    domain: props.query.domain,
    brand: props.query.brand || '',
    frequency: props.query.frequency,
    is_active: props.query.is_active,
});

const saveChanges = () => {
    editForm.put(`/citations/queries/${props.query.uuid}`, {
        preserveScroll: true,
        onSuccess: () => {
            showEditModal.value = false;
        },
    });
};

// Delete
const deleting = ref(false);

const deleteQuery = () => {
    if (confirm('Are you sure you want to delete this query and all its check history?')) {
        deleting.value = true;
        router.delete(`/citations/queries/${props.query.uuid}`);
    }
};

// View response modal
const selectedCheck = ref<CitationCheck | null>(null);
const showResponseModal = ref(false);

const viewResponse = (check: CitationCheck) => {
    selectedCheck.value = check;
    showResponseModal.value = true;
};

// Helpers
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

const getLatestCheckForPlatform = (platform: string) => {
    return props.query.checks.find(c => c.platform === platform && c.status === 'completed');
};

const isCheckInProgress = (platform: string) => {
    return props.query.checks.some(c =>
        c.platform === platform && (c.status === 'pending' || c.status === 'processing')
    );
};
</script>

<template>
    <Head :title="`Query: ${query.query}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                <div>
                    <Link href="/citations">
                        <Button variant="ghost" size="sm">
                            <ArrowLeft class="mr-1 h-4 w-4" />
                            Back
                        </Button>
                    </Link>
                    <h1 class="mt-2 text-2xl font-bold">{{ query.query }}</h1>
                    <div class="mt-1 flex items-center gap-2 text-muted-foreground">
                        <ExternalLink class="h-4 w-4" />
                        <span>{{ query.domain }}</span>
                        <span v-if="query.brand" class="text-sm">
                            ({{ query.brand }})
                        </span>
                    </div>
                    <div class="mt-2 flex items-center gap-2">
                        <Badge :variant="query.is_active ? 'default' : 'secondary'">
                            {{ query.frequency === 'manual' ? 'Manual' : query.frequency }}
                        </Badge>
                        <span v-if="query.last_checked_at" class="text-sm text-muted-foreground">
                            Last checked {{ formatRelative(query.last_checked_at) }}
                        </span>
                    </div>
                </div>

                <div class="flex gap-2">
                    <Button variant="outline" @click="showEditModal = true">
                        <Settings class="mr-2 h-4 w-4" />
                        Settings
                    </Button>
                    <Button variant="destructive" @click="deleteQuery" :disabled="deleting">
                        <Trash2 class="mr-2 h-4 w-4" />
                        Delete
                    </Button>
                </div>
            </div>

            <!-- Platform Cards -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <Card v-for="platform in availablePlatforms" :key="platform">
                    <CardHeader class="pb-2">
                        <div class="flex items-center justify-between">
                            <CardTitle class="flex items-center gap-2 text-base">
                                <span
                                    class="w-3 h-3 rounded-full"
                                    :class="getPlatformColor(platform)"
                                />
                                {{ getPlatformName(platform) }}
                            </CardTitle>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <!-- Latest result -->
                        <div v-if="getLatestCheckForPlatform(platform)" class="mb-4">
                            <div class="flex items-center gap-2">
                                <component
                                    :is="getLatestCheckForPlatform(platform)?.is_cited ? CheckCircle2 : XCircle"
                                    class="h-5 w-5"
                                    :class="getLatestCheckForPlatform(platform)?.is_cited ? 'text-green-600' : 'text-red-600'"
                                />
                                <span class="font-medium">
                                    {{ getLatestCheckForPlatform(platform)?.is_cited ? 'Cited' : 'Not Cited' }}
                                </span>
                            </div>
                            <Button
                                variant="link"
                                size="sm"
                                class="p-0 h-auto text-xs"
                                @click="viewResponse(getLatestCheckForPlatform(platform)!)"
                            >
                                View response
                            </Button>
                        </div>
                        <div v-else-if="isCheckInProgress(platform)" class="mb-4">
                            <div class="flex items-center gap-2">
                                <Loader2 class="h-5 w-5 animate-spin text-blue-600" />
                                <span class="text-muted-foreground">Checking...</span>
                            </div>
                        </div>
                        <div v-else class="mb-4 text-sm text-muted-foreground">
                            No checks yet
                        </div>

                        <!-- Run check button -->
                        <Button
                            variant="outline"
                            size="sm"
                            class="w-full"
                            :disabled="!usage.can_perform_check || isCheckInProgress(platform) || runningCheck === platform"
                            @click="runCheck(platform)"
                        >
                            <Play v-if="runningCheck !== platform && !isCheckInProgress(platform)" class="mr-2 h-4 w-4" />
                            <Loader2 v-else class="mr-2 h-4 w-4 animate-spin" />
                            {{ isCheckInProgress(platform) ? 'Checking...' : 'Run Check' }}
                        </Button>
                    </CardContent>
                </Card>
            </div>

            <!-- Check quota warning -->
            <Alert v-if="!usage.can_perform_check">
                <AlertCircle class="h-4 w-4" />
                <AlertDescription>
                    You've reached your daily check limit. Checks reset at midnight.
                </AlertDescription>
            </Alert>

            <!-- Check History -->
            <div v-if="query.checks.length > 0">
                <h2 class="mb-4 text-lg font-semibold">Check History</h2>
                <Card>
                    <CardContent class="p-0">
                        <div class="divide-y">
                            <div
                                v-for="check in query.checks"
                                :key="check.id"
                                class="flex items-center justify-between p-4 hover:bg-muted/50 cursor-pointer"
                                @click="check.status === 'completed' && viewResponse(check)"
                            >
                                <div class="flex items-center gap-3">
                                    <span
                                        class="w-3 h-3 rounded-full"
                                        :class="getPlatformColor(check.platform)"
                                    />
                                    <div>
                                        <p class="font-medium">{{ getPlatformName(check.platform) }}</p>
                                        <p class="text-sm text-muted-foreground">
                                            {{ formatDate(check.created_at) }}
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <template v-if="check.status === 'completed'">
                                        <component
                                            :is="check.is_cited ? CheckCircle2 : XCircle"
                                            class="h-5 w-5"
                                            :class="check.is_cited ? 'text-green-600' : 'text-red-600'"
                                        />
                                        <span
                                            class="text-sm font-medium"
                                            :class="check.is_cited ? 'text-green-600' : 'text-red-600'"
                                        >
                                            {{ check.is_cited ? 'Cited' : 'Not Cited' }}
                                        </span>
                                    </template>
                                    <template v-else-if="check.status === 'failed'">
                                        <XCircle class="h-5 w-5 text-red-600" />
                                        <span class="text-sm text-red-600">Failed</span>
                                    </template>
                                    <template v-else>
                                        <Loader2 class="h-5 w-5 animate-spin text-blue-600" />
                                        <span class="text-sm text-muted-foreground">{{ check.progress_step }}</span>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Recent Alerts -->
            <div v-if="query.alerts.length > 0">
                <h2 class="mb-4 text-lg font-semibold">Recent Alerts</h2>
                <div class="space-y-2">
                    <Alert
                        v-for="alert in query.alerts.slice(0, 5)"
                        :key="alert.id"
                        :class="{
                            'border-green-300 bg-green-50 dark:border-green-800 dark:bg-green-950': alert.type === 'new_citation',
                            'border-red-300 bg-red-50 dark:border-red-800 dark:bg-red-950': alert.type === 'lost_citation',
                        }"
                    >
                        <AlertTitle>
                            {{ alert.type === 'new_citation' ? 'New Citation' : 'Lost Citation' }}
                            on {{ getPlatformName(alert.platform) }}
                        </AlertTitle>
                        <AlertDescription>
                            {{ alert.message }}
                            <span class="text-xs ml-2">{{ formatRelative(alert.created_at) }}</span>
                        </AlertDescription>
                    </Alert>
                </div>
            </div>
        </div>

        <!-- Edit Modal -->
        <Dialog v-model:open="showEditModal">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Edit Query</DialogTitle>
                    <DialogDescription>
                        Update your citation tracking query settings.
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="saveChanges" class="space-y-4">
                    <div class="space-y-2">
                        <Label for="edit-query">Search Query</Label>
                        <Input id="edit-query" v-model="editForm.query" />
                    </div>

                    <div class="space-y-2">
                        <Label for="edit-domain">Domain</Label>
                        <Input id="edit-domain" v-model="editForm.domain" />
                    </div>

                    <div class="space-y-2">
                        <Label for="edit-brand">Brand (optional)</Label>
                        <Input id="edit-brand" v-model="editForm.brand" />
                    </div>

                    <div class="space-y-2">
                        <Label for="edit-frequency">Frequency</Label>
                        <Select v-model="editForm.frequency">
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="manual">Manual only</SelectItem>
                                <SelectItem
                                    value="weekly"
                                    :disabled="!usage.available_frequencies.includes('weekly')"
                                >
                                    Weekly
                                </SelectItem>
                                <SelectItem
                                    value="daily"
                                    :disabled="!usage.available_frequencies.includes('daily')"
                                >
                                    Daily
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="flex items-center gap-2">
                        <Switch id="edit-active" v-model:checked="editForm.is_active" />
                        <Label for="edit-active">Active</Label>
                    </div>

                    <DialogFooter>
                        <Button variant="outline" type="button" @click="showEditModal = false">
                            Cancel
                        </Button>
                        <Button type="submit" :disabled="editForm.processing">
                            {{ editForm.processing ? 'Saving...' : 'Save Changes' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Response Modal -->
        <Dialog v-model:open="showResponseModal">
            <DialogContent class="max-w-3xl max-h-[80vh] overflow-hidden flex flex-col">
                <DialogHeader class="flex-shrink-0">
                    <DialogTitle class="flex items-center gap-2">
                        <span
                            class="w-3 h-3 rounded-full flex-shrink-0"
                            :class="getPlatformColor(selectedCheck?.platform || '')"
                        />
                        {{ getPlatformName(selectedCheck?.platform || '') }} Response
                    </DialogTitle>
                    <DialogDescription>
                        Checked {{ selectedCheck ? formatDate(selectedCheck.created_at) : '' }}
                    </DialogDescription>
                </DialogHeader>

                <div v-if="selectedCheck" class="space-y-4 overflow-y-auto flex-1 min-h-0">
                    <!-- Citation Status -->
                    <Alert
                        :class="{
                            'border-green-300 bg-green-50 dark:border-green-800 dark:bg-green-950': selectedCheck.is_cited,
                            'border-red-300 bg-red-50 dark:border-red-800 dark:bg-red-950': !selectedCheck.is_cited,
                        }"
                    >
                        <component
                            :is="selectedCheck.is_cited ? CheckCircle2 : XCircle"
                            class="h-4 w-4 flex-shrink-0"
                        />
                        <AlertTitle>
                            {{ selectedCheck.is_cited ? 'Your domain was cited!' : 'Your domain was not cited' }}
                        </AlertTitle>
                    </Alert>

                    <!-- Citations Found -->
                    <div v-if="selectedCheck.citations && selectedCheck.citations.length > 0">
                        <h4 class="font-medium mb-2 flex items-center gap-2">
                            <Quote class="h-4 w-4 flex-shrink-0" />
                            Citations Found
                        </h4>
                        <div class="space-y-2">
                            <div
                                v-for="(citation, index) in selectedCheck.citations"
                                :key="index"
                                class="bg-muted rounded p-3 text-sm overflow-hidden"
                            >
                                <Badge variant="secondary" class="mb-1">
                                    {{ citation.type.replace('_', ' ') }}
                                </Badge>
                                <p v-if="citation.match" class="font-medium break-words">{{ citation.match }}</p>
                                <p v-if="citation.url" class="text-blue-600 text-xs break-all">{{ citation.url }}</p>
                                <p v-if="citation.context" class="text-muted-foreground mt-1 text-xs break-words">
                                    "{{ citation.context }}"
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Source URLs -->
                    <div v-if="selectedCheck.metadata?.raw_citations && selectedCheck.metadata.raw_citations.length > 0">
                        <h4 class="font-medium mb-2 flex items-center gap-2">
                            <ExternalLink class="h-4 w-4 flex-shrink-0" />
                            Sources Cited ({{ selectedCheck.metadata.raw_citations.length }})
                        </h4>
                        <div class="bg-muted rounded p-3 space-y-2 max-h-48 overflow-y-auto">
                            <a
                                v-for="(url, index) in selectedCheck.metadata.raw_citations"
                                :key="index"
                                :href="url"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="block text-sm text-blue-600 hover:text-blue-800 hover:underline break-all"
                            >
                                {{ url }}
                            </a>
                        </div>
                    </div>

                    <!-- AI Response -->
                    <div>
                        <h4 class="font-medium mb-2">AI Response</h4>
                        <div class="bg-muted rounded p-4 text-sm whitespace-pre-wrap break-words max-h-64 overflow-y-auto">
                            {{ selectedCheck.ai_response || 'No response recorded' }}
                        </div>
                    </div>
                </div>

                <DialogFooter class="flex-shrink-0">
                    <Button @click="showResponseModal = false">Close</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
