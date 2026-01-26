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
    MessageSquare,
    Download,
    Loader2,
    UserCheck,
    Quote,
    Bot,
    Clock,
    BookOpen,
    HelpCircle,
    Image,
    Mail,
    Repeat,
    Ban,
    XOctagon,
    ChevronRight,
    XCircle,
    Info,
} from 'lucide-vue-next';
import { computed, ref, onMounted, onUnmounted, watch } from 'vue';

import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
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
import { useDateFormat } from '@/composables/useDateFormat';
import { getPillarExplanations as getPillarExplanationsFromModule } from '@/utils/pillar-explanations';
import { type BreadcrumbItem, type Scan, type PillarResult, type Recommendation } from '@/types';

const { formatDate } = useDateFormat();

interface CooldownInfo {
    minutes_remaining: number;
    available_at: string;
}

interface Props {
    scan: Scan;
    canExportPdf: boolean;
    canEmailReport: boolean;
    cooldown?: CooldownInfo | null;
}

const props = defineProps<Props>();

// Cooldown state management
const cooldownMinutes = ref(Math.ceil(props.cooldown?.minutes_remaining || 0));
const cooldownAvailableAt = ref(props.cooldown?.available_at ? new Date(props.cooldown.available_at) : null);
let cooldownInterval: ReturnType<typeof setInterval> | null = null;

const isOnCooldown = computed(() => cooldownMinutes.value > 0);

const updateCooldown = () => {
    if (cooldownAvailableAt.value) {
        const now = new Date();
        const diff = cooldownAvailableAt.value.getTime() - now.getTime();
        if (diff > 0) {
            cooldownMinutes.value = Math.ceil(diff / 60000);
        } else {
            cooldownMinutes.value = 0;
            cooldownAvailableAt.value = null;
            if (cooldownInterval) {
                clearInterval(cooldownInterval);
                cooldownInterval = null;
            }
        }
    }
};

// Start cooldown timer if needed
if (props.cooldown?.available_at) {
    cooldownInterval = setInterval(updateCooldown, 10000); // Update every 10 seconds
}

const scanStatus = ref(props.scan.status || 'completed');
const progressStep = ref(props.scan.progress_step || 'Initializing');
const progressPercent = ref(props.scan.progress_percent || 0);
const scanTitle = ref(props.scan.title);
const errorMessage = ref(props.scan.error_message);
const reloading = ref(false);
let pollInterval: ReturnType<typeof setInterval> | null = null;

// Watch for prop changes after router.reload() to sync local state
watch(() => props.scan.status, (newStatus) => {
    if (newStatus && newStatus !== scanStatus.value) {
        scanStatus.value = newStatus;
        progressStep.value = props.scan.progress_step || 'Completed';
        progressPercent.value = props.scan.progress_percent || 100;
        scanTitle.value = props.scan.title;
        errorMessage.value = props.scan.error_message;
        // Clear reloading state when props are updated
        if (newStatus === 'completed' || newStatus === 'failed') {
            reloading.value = false;
        }
    }
});

const isPending = computed(() => scanStatus.value === 'pending' || scanStatus.value === 'processing');
const isFailed = computed(() => scanStatus.value === 'failed');
const isCancelled = computed(() => scanStatus.value === 'cancelled');
const isCompleted = computed(() => scanStatus.value === 'completed' && props.scan.results !== null);
const isReloading = computed(() => reloading.value);

// Progress steps for visual display
const progressSteps = [
    { key: 'fetching', label: 'Fetching webpage', percent: 10 },
    { key: 'analyzing', label: 'Analyzing page structure', percent: 30 },
    { key: 'llms', label: 'Checking llms.txt', percent: 50 },
    { key: 'scoring', label: 'Scoring content', percent: 70 },
    { key: 'recommendations', label: 'Generating recommendations', percent: 90 },
];

const currentStepIndex = computed(() => {
    const percent = progressPercent.value;
    if (percent >= 90) return 4;
    if (percent >= 70) return 3;
    if (percent >= 50) return 2;
    if (percent >= 30) return 1;
    return 0;
});

const pollStatus = async () => {
    try {
        const response = await fetch(`/scans/${props.scan.uuid}/status`);
        const data = await response.json();

        // Only update UI state if scan is still in progress
        // When completed, we'll reload the page to get fresh data
        if (data.status === 'pending' || data.status === 'processing') {
            scanStatus.value = data.status;
            progressStep.value = data.progress_step || 'Processing';
            progressPercent.value = data.progress_percent || 0;
            if (data.title) {
                scanTitle.value = data.title;
            }
        }

        errorMessage.value = data.error_message;

        if (data.status === 'completed' || data.status === 'failed' || data.status === 'cancelled') {
            if (pollInterval) {
                clearInterval(pollInterval);
                pollInterval = null;
            }
            if (data.status === 'completed') {
                // Show loading state while reloading to prevent showing stale data
                reloading.value = true;
                progressStep.value = 'Loading results...';
                progressPercent.value = 100;
                router.reload();
            } else if (data.status === 'cancelled') {
                // For cancelled status, update immediately
                scanStatus.value = data.status;
            } else {
                // For failed status, update immediately
                scanStatus.value = data.status;
            }
        }
    } catch {
        // Silent fail, will retry on next interval
    }
};

onMounted(() => {
    if (isPending.value) {
        pollInterval = setInterval(pollStatus, 1000);
    }
});

onUnmounted(() => {
    if (pollInterval) {
        clearInterval(pollInterval);
    }
    if (cooldownInterval) {
        clearInterval(cooldownInterval);
    }
});

const exportPdf = () => {
    window.location.href = `/scans/${props.scan.uuid}/export/pdf`;
};

// Email report modal state
const showEmailModal = ref(false);
const emailAddress = ref('');
const sendingEmail = ref(false);
const emailError = ref('');
const emailSuccess = ref('');

const openEmailModal = () => {
    emailAddress.value = '';
    emailError.value = '';
    emailSuccess.value = '';
    showEmailModal.value = true;
};

const sendEmailReport = () => {
    sendingEmail.value = true;
    emailError.value = '';
    emailSuccess.value = '';

    router.post(`/scans/${props.scan.uuid}/email`, {
        email: emailAddress.value || undefined,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            emailSuccess.value = emailAddress.value
                ? `Report sent to ${emailAddress.value}`
                : 'Report sent to your email';
            setTimeout(() => {
                showEmailModal.value = false;
                emailSuccess.value = '';
            }, 2000);
        },
        onError: (errors) => {
            emailError.value = errors.email || 'Failed to send email. Please try again.';
        },
        onFinish: () => {
            sendingEmail.value = false;
        },
    });
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Scans', href: '/scans' },
    { title: props.scan.title || 'Scan Results', href: `/scans/${props.scan.uuid}` },
];

const rescanning = ref(false);
const rescanProgress = ref('');
const newScanUuid = ref<string | null>(null);
const deleting = ref(false);
const cancelling = ref(false);

const cancelScan = async () => {
    if (cancelling.value) return;
    cancelling.value = true;

    try {
        const response = await fetch(`/scans/${props.scan.uuid}/cancel`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
        });

        if (response.ok) {
            scanStatus.value = 'cancelled';
            if (pollInterval) {
                clearInterval(pollInterval);
                pollInterval = null;
            }
        }
    } catch (e) {
        // Silent fail
    } finally {
        cancelling.value = false;
    }
};

const pollNewScan = async () => {
    if (!newScanUuid.value) return;

    try {
        const response = await fetch(`/scans/${newScanUuid.value}/status`);
        const data = await response.json();

        if (data.status === 'pending' || data.status === 'processing') {
            rescanProgress.value = data.progress_step || 'Processing...';
            setTimeout(pollNewScan, 1000);
        } else if (data.status === 'completed') {
            // Scan complete, redirect to the new scan
            router.visit(`/scans/${newScanUuid.value}`);
        } else if (data.status === 'failed') {
            rescanProgress.value = 'Scan failed';
            rescanning.value = false;
            // Still redirect to show the error
            setTimeout(() => {
                router.visit(`/scans/${newScanUuid.value}`);
            }, 1000);
        }
    } catch {
        // Retry on error
        setTimeout(pollNewScan, 2000);
    }
};

const rescan = async () => {
    rescanning.value = true;
    rescanProgress.value = 'Starting rescan...';
    newScanUuid.value = null;

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const response = await fetch(`/scans/${props.scan.uuid}/rescan`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken || '',
            },
        });

        const data = await response.json();

        if (data.uuid) {
            newScanUuid.value = data.uuid;
            rescanProgress.value = 'Scanning...';
            pollNewScan();
        } else if (data.error) {
            rescanProgress.value = '';
            rescanning.value = false;
            alert(data.error);
        } else {
            // Fallback: if we got a redirect response or unexpected format
            rescanProgress.value = '';
            rescanning.value = false;
            router.reload();
        }
    } catch {
        rescanProgress.value = '';
        rescanning.value = false;
        router.reload();
    }
};

const deleteScan = () => {
    if (confirm('Are you sure you want to delete this scan?')) {
        deleting.value = true;
        router.delete(`/scans/${props.scan.uuid}`);
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
    // Base pillars (Free)
    definitions: FileText,
    structure: Layers,
    authority: Award,
    machine_readable: Code,
    answerability: MessageSquare,
    // Pro pillars
    eeat: UserCheck,
    citations: Quote,
    ai_accessibility: Bot,
    // Agency pillars
    freshness: Clock,
    readability: BookOpen,
    question_coverage: HelpCircle,
    multimedia: Image,
};

const pillarResources: Record<string, Array<{ title: string; url: string }>> = {
    definitions: [
        { title: 'What Is GEO?', url: '/blog/what-is-geo-complete-guide' },
        { title: '10 Ways to Optimize Content for AI', url: '/blog/10-ways-optimize-content-chatgpt-perplexity' },
    ],
    structure: [
        { title: 'Why SSR Matters for GEO', url: '/blog/why-server-side-rendering-ssr-matters-for-geo-and-ai-visibility' },
        { title: '10 Ways to Optimize Content for AI', url: '/blog/10-ways-optimize-content-chatgpt-perplexity' },
    ],
    authority: [
        { title: 'How AI Search Engines Cite Sources', url: '/blog/how-ai-search-engines-cite-sources' },
        { title: 'GEO vs SEO: Key Differences', url: '/blog/geo-vs-seo-key-differences' },
    ],
    machine_readable: [
        { title: 'Why SSR Matters for GEO', url: '/blog/why-server-side-rendering-ssr-matters-for-geo-and-ai-visibility' },
        { title: 'What Is GEO?', url: '/blog/what-is-geo-complete-guide' },
    ],
    answerability: [
        { title: 'How AI Search Engines Cite Sources', url: '/blog/how-ai-search-engines-cite-sources' },
        { title: '10 Ways to Optimize Content for AI', url: '/blog/10-ways-optimize-content-chatgpt-perplexity' },
    ],
    eeat: [
        { title: 'How AI Search Engines Cite Sources', url: '/blog/how-ai-search-engines-cite-sources' },
        { title: 'GEO vs SEO: Key Differences', url: '/blog/geo-vs-seo-key-differences' },
    ],
    citations: [
        { title: 'How AI Search Engines Cite Sources', url: '/blog/how-ai-search-engines-cite-sources' },
        { title: '10 Ways to Optimize Content for AI', url: '/blog/10-ways-optimize-content-chatgpt-perplexity' },
    ],
    ai_accessibility: [
        { title: 'Why SSR Matters for GEO', url: '/blog/why-server-side-rendering-ssr-matters-for-geo-and-ai-visibility' },
        { title: 'The Rise of AI Search', url: '/blog/rise-of-ai-search-content-creators' },
    ],
    freshness: [
        { title: 'GEO vs SEO: Key Differences', url: '/blog/geo-vs-seo-key-differences' },
        { title: 'The Rise of AI Search', url: '/blog/rise-of-ai-search-content-creators' },
    ],
    readability: [
        { title: '10 Ways to Optimize Content for AI', url: '/blog/10-ways-optimize-content-chatgpt-perplexity' },
        { title: 'What Is GEO?', url: '/blog/what-is-geo-complete-guide' },
    ],
    question_coverage: [
        { title: 'How AI Search Engines Cite Sources', url: '/blog/how-ai-search-engines-cite-sources' },
        { title: '10 Ways to Optimize Content for AI', url: '/blog/10-ways-optimize-content-chatgpt-perplexity' },
    ],
    multimedia: [
        { title: 'GEO vs SEO: Key Differences', url: '/blog/geo-vs-seo-key-differences' },
        { title: 'What Is GEO?', url: '/blog/what-is-geo-complete-guide' },
    ],
};

const getTierBadge = (tier: string) => {
    if (tier === 'pro') return { label: 'Pro', class: 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300' };
    if (tier === 'agency') return { label: 'Agency', class: 'bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-300' };
    return null;
};

const pillars = computed(() => {
    return Object.entries(props.scan.results?.pillars || {}).map(([key, value]) => {
        const pillarData = value as PillarResult;
        return {
            key,
            ...pillarData,
            icon: pillarIcons[key] || FileText,
            tierBadge: getTierBadge(pillarData.tier || 'free'),
        };
    });
});

const recommendations = computed(() => {
    return Object.entries(props.scan.results?.recommendations || {}).map(([key, value]) => {
        const recData = value as Recommendation;
        return {
            key,
            ...recData,
            tierBadge: getTierBadge(recData.tier || 'free'),
        };
    });
});

const summary = computed(() => props.scan.results?.summary);

// Pillar explanation dialog
const showPillarDialog = ref(false);
const selectedPillar = ref<any>(null);

const openPillarDetails = (pillar: any) => {
    selectedPillar.value = pillar;
    showPillarDialog.value = true;
};

// Generate explanation items for why a pillar didn't score 100%
// Uses the refactored module for code splitting
const getPillarExplanations = (pillar: any): Array<{ label: string; achieved: boolean; points: string; tip?: string }> => {
    const details = pillar.details || {};
    const breakdown = details.breakdown || pillar.breakdown || {};
    return getPillarExplanationsFromModule(pillar.key, details, breakdown);
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
                    <div class="mt-2 flex items-center gap-2">
                        <h1 class="text-2xl font-bold">{{ scan.title || 'Scan Results' }}</h1>
                        <span
                            v-if="scan.scheduled_scan_id"
                            class="inline-flex items-center gap-1 rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-900 dark:text-blue-300"
                        >
                            <Repeat class="h-3 w-3" />
                            Scheduled
                        </span>
                    </div>
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
                        <span v-if="scan.user"> by {{ scan.user.name }}</span>
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <template v-if="isCompleted">
                        <Button v-if="canExportPdf" variant="outline" @click="exportPdf">
                            <Download class="mr-2 h-4 w-4" />
                            Export PDF
                        </Button>
                        <Button v-if="canEmailReport" variant="outline" @click="openEmailModal">
                            <Mail class="mr-2 h-4 w-4" />
                            Email Report
                        </Button>
                        <Button
                            variant="outline"
                            @click="rescan"
                            :disabled="rescanning || isOnCooldown"
                            :title="isOnCooldown ? `Available in ${cooldownMinutes} ${cooldownMinutes === 1 ? 'minute' : 'minutes'}` : ''"
                        >
                            <Clock v-if="isOnCooldown" class="mr-2 h-4 w-4" />
                            <Loader2 v-else-if="rescanning" class="mr-2 h-4 w-4 animate-spin" />
                            <RefreshCw v-else class="mr-2 h-4 w-4" />
                            <template v-if="isOnCooldown">
                                Rescan in {{ cooldownMinutes }}m
                            </template>
                            <template v-else-if="rescanning">
                                {{ rescanProgress || 'Rescanning...' }}
                            </template>
                            <template v-else>
                                Rescan
                            </template>
                        </Button>
                    </template>
                    <Button v-if="!isPending" variant="destructive" @click="deleteScan" :disabled="deleting">
                        <Trash2 class="mr-2 h-4 w-4" />
                        Delete
                    </Button>
                </div>
            </div>

            <!-- Pending/Processing State (also shown while reloading to prevent stale data flash) -->
            <Card v-if="isPending || isReloading" class="overflow-hidden">
                <CardContent class="py-12">
                    <div class="mx-auto max-w-md">
                        <!-- Title and URL -->
                        <div class="mb-8 text-center">
                            <h3 class="text-xl font-semibold">{{ scanTitle || 'Scanning...' }}</h3>
                            <p class="mt-1 text-sm text-muted-foreground truncate">{{ scan.url }}</p>
                        </div>

                        <!-- Progress Bar -->
                        <div class="mb-8">
                            <div class="flex justify-between text-sm mb-2">
                                <span class="font-medium">{{ progressStep }}</span>
                                <span class="text-muted-foreground">{{ progressPercent }}%</span>
                            </div>
                            <div class="h-2 w-full overflow-hidden rounded-full bg-muted">
                                <div
                                    class="h-full rounded-full bg-primary transition-all duration-500"
                                    :style="{ width: `${progressPercent}%` }"
                                />
                            </div>
                        </div>

                        <!-- Step Indicators -->
                        <div class="space-y-3">
                            <div
                                v-for="(step, index) in progressSteps"
                                :key="step.key"
                                class="flex items-center gap-3"
                            >
                                <!-- Step Icon -->
                                <div
                                    class="flex h-8 w-8 items-center justify-center rounded-full border-2 transition-all"
                                    :class="{
                                        'border-primary bg-primary text-primary-foreground': index < currentStepIndex,
                                        'border-primary bg-primary/10 text-primary': index === currentStepIndex,
                                        'border-muted bg-muted/50 text-muted-foreground': index > currentStepIndex,
                                    }"
                                >
                                    <CheckCircle2 v-if="index < currentStepIndex" class="h-4 w-4" />
                                    <Loader2 v-else-if="index === currentStepIndex" class="h-4 w-4 animate-spin" />
                                    <span v-else class="text-xs font-medium">{{ index + 1 }}</span>
                                </div>

                                <!-- Step Label -->
                                <span
                                    class="text-sm transition-colors"
                                    :class="{
                                        'font-medium text-foreground': index <= currentStepIndex,
                                        'text-muted-foreground': index > currentStepIndex,
                                    }"
                                >
                                    {{ step.label }}
                                </span>
                            </div>
                        </div>

                        <!-- Cancel Button -->
                        <div v-if="!isReloading" class="mt-8 text-center">
                            <Button
                                variant="outline"
                                @click="cancelScan"
                                :disabled="cancelling"
                            >
                                <Loader2 v-if="cancelling" class="mr-2 h-4 w-4 animate-spin" />
                                <Ban v-else class="mr-2 h-4 w-4" />
                                {{ cancelling ? 'Cancelling...' : 'Cancel Scan' }}
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Failed State -->
            <Alert v-else-if="isFailed" variant="destructive" class="border-red-300 bg-red-50 dark:border-red-800 dark:bg-red-950">
                <AlertCircle class="h-5 w-5" />
                <AlertTitle>Scan Failed</AlertTitle>
                <AlertDescription>
                    {{ errorMessage || 'An unexpected error occurred while scanning the URL.' }}
                    <div class="mt-4">
                        <Button variant="outline" @click="rescan" :disabled="rescanning">
                            <Loader2 v-if="rescanning" class="mr-2 h-4 w-4 animate-spin" />
                            <RefreshCw v-else class="mr-2 h-4 w-4" />
                            {{ rescanning ? (rescanProgress || 'Retrying...') : 'Try Again' }}
                        </Button>
                    </div>
                </AlertDescription>
            </Alert>

            <!-- Cancelled State -->
            <Alert v-else-if="isCancelled" class="border-gray-300 bg-gray-50 dark:border-gray-700 dark:bg-gray-900">
                <XOctagon class="h-5 w-5 text-gray-500" />
                <AlertTitle>Scan Cancelled</AlertTitle>
                <AlertDescription>
                    This scan was cancelled and does not count towards your quota.
                    <div class="mt-4">
                        <Button variant="outline" @click="rescan" :disabled="rescanning">
                            <Loader2 v-if="rescanning" class="mr-2 h-4 w-4 animate-spin" />
                            <RefreshCw v-else class="mr-2 h-4 w-4" />
                            {{ rescanning ? (rescanProgress || 'Scanning...') : 'Scan Again' }}
                        </Button>
                    </div>
                </AlertDescription>
            </Alert>

            <!-- Main Score Card (only show when completed) -->
            <Card v-else class="overflow-hidden">
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

            <!-- Pillar Scores (only show when completed) -->
            <div v-if="isCompleted">
                <h2 class="mb-4 text-xl font-semibold">Score Breakdown</h2>
                <p class="mb-4 text-sm text-muted-foreground">Click any card to see why you didn't score 100%</p>
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <Card
                        v-for="pillar in pillars"
                        :key="pillar.key"
                        class="cursor-pointer transition-all hover:border-primary/50 hover:shadow-md"
                        @click="openPillarDetails(pillar)"
                    >
                        <CardHeader class="pb-2">
                            <div class="flex items-center justify-between">
                                <CardTitle class="flex items-center gap-2 text-base">
                                    <component :is="pillar.icon" class="h-5 w-5 text-muted-foreground" />
                                    {{ pillar.name }}
                                    <span
                                        v-if="pillar.tierBadge"
                                        class="rounded-full px-2 py-0.5 text-xs font-medium"
                                        :class="pillar.tierBadge.class"
                                    >
                                        {{ pillar.tierBadge.label }}
                                    </span>
                                </CardTitle>
                                <div class="flex items-center gap-2">
                                    <span class="text-2xl font-bold">{{ pillar.score.toFixed(1) }}</span>
                                    <ChevronRight class="h-5 w-5 text-muted-foreground" />
                                </div>
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
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- Raw Details (Collapsible, only show when completed) -->
            <details v-if="isCompleted" class="group">
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

        <!-- Email Report Modal -->
        <Dialog v-model:open="showEmailModal">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Email Report</DialogTitle>
                    <DialogDescription>
                        Send the GEO scan report as a PDF attachment. Leave blank to send to your registered email.
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-4 py-4">
                    <div class="space-y-2">
                        <Label for="email">Email Address (optional)</Label>
                        <Input
                            id="email"
                            v-model="emailAddress"
                            type="email"
                            placeholder="recipient@example.com"
                            :disabled="sendingEmail"
                        />
                        <p class="text-xs text-muted-foreground">
                            Leave empty to send to your account email.
                        </p>
                    </div>

                    <!-- Error message -->
                    <Alert v-if="emailError" variant="destructive">
                        <AlertCircle class="h-4 w-4" />
                        <AlertDescription>{{ emailError }}</AlertDescription>
                    </Alert>

                    <!-- Success message -->
                    <Alert v-if="emailSuccess" class="border-green-500 bg-green-50 dark:bg-green-950">
                        <CheckCircle2 class="h-4 w-4 text-green-600" />
                        <AlertDescription class="text-green-600">{{ emailSuccess }}</AlertDescription>
                    </Alert>
                </div>

                <DialogFooter>
                    <Button variant="outline" @click="showEmailModal = false" :disabled="sendingEmail">
                        Cancel
                    </Button>
                    <Button @click="sendEmailReport" :disabled="sendingEmail">
                        <Loader2 v-if="sendingEmail" class="mr-2 h-4 w-4 animate-spin" />
                        <Mail v-else class="mr-2 h-4 w-4" />
                        {{ sendingEmail ? 'Sending...' : 'Send Report' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Pillar Details Modal -->
        <Dialog v-model:open="showPillarDialog">
            <DialogContent class="sm:max-w-lg max-h-[85vh] overflow-y-auto">
                <DialogHeader v-if="selectedPillar">
                    <DialogTitle class="flex items-center gap-3">
                        <component :is="selectedPillar.icon" class="h-6 w-6 text-muted-foreground" />
                        {{ selectedPillar.name }}
                        <span
                            v-if="selectedPillar.tierBadge"
                            class="rounded-full px-2 py-0.5 text-xs font-medium"
                            :class="selectedPillar.tierBadge.class"
                        >
                            {{ selectedPillar.tierBadge.label }}
                        </span>
                    </DialogTitle>
                    <DialogDescription>
                        <div class="flex items-center justify-between mt-2">
                            <span>Score breakdown and improvement tips</span>
                            <span class="text-lg font-bold text-foreground">
                                {{ selectedPillar.score.toFixed(1) }} / {{ selectedPillar.max_score }}
                                <span class="text-sm font-normal text-muted-foreground ml-1">({{ selectedPillar.percentage.toFixed(0) }}%)</span>
                            </span>
                        </div>
                        <!-- Progress Bar -->
                        <div class="mt-3 h-2 w-full overflow-hidden rounded-full bg-muted">
                            <div
                                class="h-full rounded-full transition-all"
                                :class="getScoreColor(selectedPillar.percentage)"
                                :style="{ width: `${selectedPillar.percentage}%` }"
                            />
                        </div>
                    </DialogDescription>
                </DialogHeader>

                <div v-if="selectedPillar" class="space-y-3 py-4">
                    <div
                        v-for="(item, index) in getPillarExplanations(selectedPillar)"
                        :key="index"
                        class="rounded-lg border p-3"
                        :class="item.achieved ? 'border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-950/50' : 'border-orange-200 bg-orange-50 dark:border-orange-800 dark:bg-orange-950/50'"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-start gap-2">
                                <CheckCircle2 v-if="item.achieved" class="h-5 w-5 text-green-600 dark:text-green-400 mt-0.5 shrink-0" />
                                <XCircle v-else class="h-5 w-5 text-orange-600 dark:text-orange-400 mt-0.5 shrink-0" />
                                <div>
                                    <p class="font-medium text-sm" :class="item.achieved ? 'text-green-800 dark:text-green-200' : 'text-orange-800 dark:text-orange-200'">
                                        {{ item.label }}
                                    </p>
                                    <p v-if="item.tip" class="text-xs mt-1 text-orange-700 dark:text-orange-300">
                                        <Info class="h-3 w-3 inline mr-1" />
                                        {{ item.tip }}
                                    </p>
                                </div>
                            </div>
                            <span
                                class="text-xs font-mono px-2 py-1 rounded shrink-0"
                                :class="item.achieved ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300' : 'bg-orange-100 text-orange-700 dark:bg-orange-900 dark:text-orange-300'"
                            >
                                {{ item.points }}
                            </span>
                        </div>
                    </div>

                    <!-- Perfect score message -->
                    <div v-if="selectedPillar.percentage >= 100" class="rounded-lg border border-green-300 bg-green-100 p-4 dark:border-green-700 dark:bg-green-900/50">
                        <div class="flex items-center gap-2">
                            <CheckCircle2 class="h-5 w-5 text-green-600 dark:text-green-400" />
                            <p class="font-medium text-green-800 dark:text-green-200">Perfect score! Great job optimizing this pillar.</p>
                        </div>
                    </div>

                    <!-- Resource Links -->
                    <div v-if="pillarResources[selectedPillar.key]?.length" class="rounded-lg border bg-muted/30 p-4">
                        <p class="text-sm font-medium mb-3 flex items-center gap-2">
                            <BookOpen class="h-4 w-4" />
                            Learn more about {{ selectedPillar.name }}
                        </p>
                        <div class="flex flex-wrap gap-2">
                            <Link
                                v-for="(resource, idx) in pillarResources[selectedPillar.key]"
                                :key="idx"
                                :href="resource.url"
                                class="inline-flex items-center gap-1.5 rounded-md bg-primary/10 px-3 py-1.5 text-sm font-medium text-primary hover:bg-primary/20 transition-colors"
                            >
                                {{ resource.title }}
                                <ExternalLink class="h-3.5 w-3.5" />
                            </Link>
                        </div>
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" @click="showPillarDialog = false">
                        Close
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
