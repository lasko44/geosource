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
} from 'lucide-vue-next';
import { computed, ref, onMounted, onUnmounted } from 'vue';

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
import { type BreadcrumbItem, type Scan, type PillarResult, type Recommendation } from '@/types';

const { formatDate } = useDateFormat();

interface Props {
    scan: Scan;
    canExportPdf: boolean;
    canEmailReport: boolean;
}

const props = defineProps<Props>();

const scanStatus = ref(props.scan.status || 'completed');
const progressStep = ref(props.scan.progress_step || 'Initializing');
const progressPercent = ref(props.scan.progress_percent || 0);
const scanTitle = ref(props.scan.title);
const errorMessage = ref(props.scan.error_message);
let pollInterval: ReturnType<typeof setInterval> | null = null;

const isPending = computed(() => scanStatus.value === 'pending' || scanStatus.value === 'processing');
const isFailed = computed(() => scanStatus.value === 'failed');
const isCompleted = computed(() => scanStatus.value === 'completed');

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
        scanStatus.value = data.status;
        progressStep.value = data.progress_step || 'Processing';
        progressPercent.value = data.progress_percent || 0;
        errorMessage.value = data.error_message;
        if (data.title) {
            scanTitle.value = data.title;
        }

        if (data.status === 'completed' || data.status === 'failed') {
            if (pollInterval) {
                clearInterval(pollInterval);
                pollInterval = null;
            }
            if (data.status === 'completed') {
                router.reload();
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
const deleting = ref(false);

const rescan = () => {
    rescanning.value = true;
    router.post(`/scans/${props.scan.uuid}/rescan`, {}, {
        onFinish: () => {
            rescanning.value = false;
        },
    });
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
                        <Button variant="outline" @click="rescan" :disabled="rescanning">
                            <RefreshCw class="mr-2 h-4 w-4" :class="{ 'animate-spin': rescanning }" />
                            {{ rescanning ? 'Rescanning...' : 'Rescan' }}
                        </Button>
                    </template>
                    <Button v-if="!isPending" variant="destructive" @click="deleteScan" :disabled="deleting">
                        <Trash2 class="mr-2 h-4 w-4" />
                        Delete
                    </Button>
                </div>
            </div>

            <!-- Pending/Processing State -->
            <Card v-if="isPending" class="overflow-hidden">
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
                            <RefreshCw class="mr-2 h-4 w-4" :class="{ 'animate-spin': rescanning }" />
                            {{ rescanning ? 'Retrying...' : 'Try Again' }}
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
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <Card v-for="pillar in pillars" :key="pillar.key">
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

            <!-- Recommendations (only show when completed) -->
            <div v-if="isCompleted && recommendations.length > 0">
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
                                v-if="rec.tierBadge"
                                class="rounded-full px-2 py-0.5 text-xs font-medium"
                                :class="rec.tierBadge.class"
                            >
                                {{ rec.tierBadge.label }}
                            </span>
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
    </AppLayout>
</template>
