<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowLeft,
    CheckCircle2,
    XCircle,
    Clock,
    Loader2,
    ExternalLink,
    Trash2,
    RefreshCw,
    Globe,
    TrendingUp,
    Lightbulb,
    FileText,
} from 'lucide-vue-next';
import { computed, ref, onMounted, onUnmounted } from 'vue';

import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { useDateFormat } from '@/composables/useDateFormat';
import { type BreadcrumbItem } from '@/types';

const { formatDate, formatRelative } = useDateFormat();

interface Source {
    url: string;
    citation_count: number;
    platforms: string[];
    domain: string;
}

interface PlatformResponse {
    content: string | null;
    citations: string[];
    model: string;
    error?: string;
}

interface AnalysisResults {
    content_patterns?: string;
    source_authority?: string;
    common_themes?: string;
    citation_gaps?: string;
    key_insights?: string;
    raw_analysis?: string;
}

interface Scan {
    id: number;
    uuid: string;
    url: string;
    title: string | null;
    score: number | null;
    grade: string | null;
    status: string;
}

interface CitationResearch {
    id: number;
    uuid: string;
    topic: string;
    tier: string;
    status: string;
    progress_step: string | null;
    progress_percent: number;
    platforms_queried: string[] | null;
    platform_responses: Record<string, PlatformResponse> | null;
    sources_found: Source[] | null;
    analysis_results: AnalysisResults | null;
    report: string | null;
    error_message: string | null;
    tokens_amount: number | null;
    created_at: string;
    completed_at: string | null;
    scans?: Scan[];
}

interface Props {
    research: CitationResearch;
    usage: Record<string, unknown>;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Citation Tracking', href: '/citations' },
    { title: 'Research', href: '/citations/research' },
    { title: props.research.topic.substring(0, 30) + '...', href: `/citations/research/${props.research.uuid}` },
];

const showDeleteDialog = ref(false);
let pollInterval: ReturnType<typeof setInterval> | null = null;

const isProcessing = computed(() => {
    return props.research.status === 'pending' || props.research.status === 'processing';
});

const platformNames: Record<string, string> = {
    perplexity: 'Perplexity',
    openai: 'ChatGPT',
    claude: 'Claude',
    gemini: 'Gemini',
    deepseek: 'DeepSeek',
};

const getPlatformName = (platform: string) => {
    return platformNames[platform] || platform;
};

const getStatusIcon = (status: string) => {
    switch (status) {
        case 'completed':
            return CheckCircle2;
        case 'failed':
            return XCircle;
        case 'processing':
            return Loader2;
        default:
            return Clock;
    }
};

const getStatusColor = (status: string) => {
    switch (status) {
        case 'completed':
            return 'text-green-600';
        case 'failed':
            return 'text-red-600';
        case 'processing':
            return 'text-blue-600';
        default:
            return 'text-muted-foreground';
    }
};

const startPolling = () => {
    if (isProcessing.value && !pollInterval) {
        pollInterval = setInterval(() => {
            router.reload({ only: ['research'] });
        }, 3000);
    }
};

const stopPolling = () => {
    if (pollInterval) {
        clearInterval(pollInterval);
        pollInterval = null;
    }
};

onMounted(() => {
    startPolling();
});

onUnmounted(() => {
    stopPolling();
});

// Watch for status changes to stop polling
if (!isProcessing.value) {
    stopPolling();
}

const deleteResearch = () => {
    router.delete(`/citations/research/${props.research.uuid}`, {
        onSuccess: () => {
            showDeleteDialog.value = false;
        },
    });
};

const formatReportAsHtml = (report: string) => {
    // Convert markdown-like formatting to HTML
    return report
        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
        .replace(/\n/g, '<br>');
};
</script>

<template>
    <Head :title="`Research: ${research.topic}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <!-- Header -->
            <div class="flex items-start justify-between">
                <div>
                    <Link href="/citations/research">
                        <Button variant="ghost" size="sm">
                            <ArrowLeft class="mr-1 h-4 w-4" />
                            Back to Research
                        </Button>
                    </Link>
                    <h1 class="mt-2 text-2xl font-bold">{{ research.topic }}</h1>
                    <div class="mt-2 flex items-center gap-3 text-sm text-muted-foreground">
                        <component
                            :is="getStatusIcon(research.status)"
                            class="h-4 w-4"
                            :class="[
                                getStatusColor(research.status),
                                research.status === 'processing' ? 'animate-spin' : ''
                            ]"
                        />
                        <span class="capitalize">{{ research.status }}</span>
                        <Badge variant="outline">
                            {{ research.tier === 'research_audit' ? 'Research + Audit' : 'Research' }}
                        </Badge>
                        <span>{{ formatRelative(research.created_at) }}</span>
                    </div>
                </div>
                <Button variant="outline" size="sm" @click="showDeleteDialog = true">
                    <Trash2 class="mr-2 h-4 w-4" />
                    Delete
                </Button>
            </div>

            <!-- Progress -->
            <Card v-if="isProcessing">
                <CardContent class="pt-6">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <Loader2 class="h-4 w-4 animate-spin text-primary" />
                                <span class="font-medium">{{ research.progress_step || 'Processing...' }}</span>
                            </div>
                            <span class="text-sm text-muted-foreground">{{ research.progress_percent }}%</span>
                        </div>
                        <div class="h-2 w-full bg-muted rounded-full overflow-hidden">
                            <div
                                class="h-full bg-primary transition-all duration-500"
                                :style="{ width: `${research.progress_percent}%` }"
                            />
                        </div>
                        <p class="text-sm text-muted-foreground">
                            This may take a few minutes. The page will update automatically.
                        </p>
                    </div>
                </CardContent>
            </Card>

            <!-- Error -->
            <Alert v-if="research.status === 'failed'" variant="destructive">
                <XCircle class="h-4 w-4" />
                <AlertTitle>Research Failed</AlertTitle>
                <AlertDescription>
                    {{ research.error_message || 'An error occurred during research. Your tokens have been refunded.' }}
                </AlertDescription>
            </Alert>

            <!-- Results -->
            <template v-if="research.status === 'completed'">
                <!-- Report -->
                <Card v-if="research.report">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <FileText class="h-5 w-5 text-primary" />
                            Research Report
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div
                            class="prose prose-sm dark:prose-invert max-w-none"
                            v-html="formatReportAsHtml(research.report)"
                        />
                    </CardContent>
                </Card>

                <!-- Top Sources -->
                <Card v-if="research.sources_found && research.sources_found.length > 0">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Globe class="h-5 w-5 text-primary" />
                            Top Cited Sources
                        </CardTitle>
                        <CardDescription>
                            Sources ranked by how many AI platforms cited them.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3">
                            <div
                                v-for="(source, index) in research.sources_found.slice(0, 10)"
                                :key="source.url"
                                class="flex items-start justify-between p-3 rounded-lg border"
                            >
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="text-lg font-bold text-muted-foreground">#{{ index + 1 }}</span>
                                        <a
                                            :href="source.url"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="font-medium text-primary hover:underline truncate"
                                        >
                                            {{ source.domain }}
                                            <ExternalLink class="inline h-3 w-3 ml-1" />
                                        </a>
                                    </div>
                                    <p class="text-sm text-muted-foreground mt-1 truncate">
                                        {{ source.url }}
                                    </p>
                                    <div class="mt-2 flex flex-wrap gap-1">
                                        <Badge
                                            v-for="platform in source.platforms"
                                            :key="platform"
                                            variant="secondary"
                                            class="text-xs"
                                        >
                                            {{ getPlatformName(platform) }}
                                        </Badge>
                                    </div>
                                </div>
                                <div class="text-right ml-4">
                                    <div class="text-2xl font-bold">{{ source.citation_count }}</div>
                                    <div class="text-xs text-muted-foreground">citations</div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Audit Scans -->
                <Card v-if="research.tier === 'research_audit' && research.scans && research.scans.length > 0">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <TrendingUp class="h-5 w-5 text-primary" />
                            GEO Audit Scans
                        </CardTitle>
                        <CardDescription>
                            GEO analysis of the top cited sources.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3">
                            <Link
                                v-for="scan in research.scans"
                                :key="scan.uuid"
                                :href="`/scans/${scan.uuid}`"
                                class="block"
                            >
                                <div class="flex items-center justify-between p-3 rounded-lg border hover:bg-muted/50 transition-colors">
                                    <div class="flex-1 min-w-0">
                                        <div class="font-medium truncate">
                                            {{ scan.title || scan.url }}
                                        </div>
                                        <div class="text-sm text-muted-foreground truncate">
                                            {{ scan.url }}
                                        </div>
                                    </div>
                                    <div class="ml-4 text-right">
                                        <template v-if="scan.status === 'completed'">
                                            <div class="text-2xl font-bold">{{ scan.score }}</div>
                                            <Badge :variant="scan.grade?.startsWith('A') ? 'default' : 'secondary'">
                                                {{ scan.grade }}
                                            </Badge>
                                        </template>
                                        <template v-else-if="scan.status === 'processing'">
                                            <Loader2 class="h-5 w-5 animate-spin text-primary" />
                                        </template>
                                        <template v-else>
                                            <Badge variant="outline">{{ scan.status }}</Badge>
                                        </template>
                                    </div>
                                </div>
                            </Link>
                        </div>
                    </CardContent>
                </Card>

                <!-- Platform Responses -->
                <Card v-if="research.platform_responses">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Lightbulb class="h-5 w-5 text-primary" />
                            Platform Responses
                        </CardTitle>
                        <CardDescription>
                            Raw responses from each AI platform.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <Collapsible
                            v-for="(response, platform) in research.platform_responses"
                            :key="platform"
                            class="border rounded-lg"
                        >
                            <CollapsibleTrigger class="flex items-center justify-between w-full p-4 hover:bg-muted/50 transition-colors">
                                <div class="flex items-center gap-2">
                                    <span class="font-medium">{{ getPlatformName(platform) }}</span>
                                    <Badge v-if="response.error" variant="destructive">Error</Badge>
                                    <Badge v-else variant="outline">{{ response.model }}</Badge>
                                </div>
                            </CollapsibleTrigger>
                            <CollapsibleContent class="px-4 pb-4">
                                <div v-if="response.error" class="text-red-600">
                                    {{ response.error }}
                                </div>
                                <div v-else>
                                    <pre class="whitespace-pre-wrap text-sm bg-muted p-4 rounded-lg overflow-auto max-h-96">{{ response.content }}</pre>
                                    <div v-if="response.citations && response.citations.length > 0" class="mt-4">
                                        <h4 class="font-semibold mb-2">Citations:</h4>
                                        <ul class="list-disc pl-4 space-y-1">
                                            <li v-for="citation in response.citations" :key="citation">
                                                <a
                                                    :href="citation"
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    class="text-primary hover:underline text-sm break-all"
                                                >
                                                    {{ citation }}
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </CollapsibleContent>
                        </Collapsible>
                    </CardContent>
                </Card>
            </template>

            <!-- Delete Dialog -->
            <Dialog v-model:open="showDeleteDialog">
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Delete Research</DialogTitle>
                        <DialogDescription>
                            Are you sure you want to delete this research? This action cannot be undone.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" @click="showDeleteDialog = false">
                            Cancel
                        </Button>
                        <Button variant="destructive" @click="deleteResearch">
                            Delete
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    </AppLayout>
</template>
