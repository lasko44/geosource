<script setup lang="ts">
import { Head, Link, useForm, usePage, router } from '@inertiajs/vue3';
import { Globe, TrendingUp, Target, Calendar, ExternalLink, Zap, ArrowRight, Users, Crown, Plus, Building2, User, ChevronDown, Quote, CheckCircle2, XCircle, Clock, Layers, Repeat, Loader2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

import AppLayout from '@/layouts/AppLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Progress } from '@/components/ui/progress';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useDateFormat } from '@/composables/useDateFormat';
import { type BreadcrumbItem, type Scan, type DashboardStats, type UsageSummary, type PlanWithLimits, type TeamBranding } from '@/types';

const { formatDate } = useDateFormat();

// Get team branding from shared props
const teamBranding = computed(() => usePage().props.teamBranding as TeamBranding | null);

interface Team {
    id: number;
    name: string;
    slug: string;
    is_owner: boolean;
    members_count: number;
    role: string;
}

interface CurrentTeam {
    id: number;
    name: string;
    slug: string;
}

interface CitationQuery {
    id: number;
    uuid: string;
    query: string;
    domain: string;
    is_cited: boolean | null;
    last_checked_at: string | null;
}

interface CitationData {
    queries: CitationQuery[];
    stats: {
        total_queries: number;
        cited_count: number;
        total_checks: number;
        citation_rate: number;
    };
}

interface Props {
    recentScans: Scan[];
    stats: DashboardStats;
    usage: UsageSummary;
    showUpgradePrompt: boolean;
    plans: Record<string, PlanWithLimits>;
    teams: Team[] | null;
    currentTeamId: number | null;
    currentTeam: CurrentTeam | null;
    hasPersonalOption: boolean;
    citationData: CitationData | null;
    canBulkScan: boolean;
}

const props = defineProps<Props>();
const page = usePage();
const canCreateTeams = computed(() => page.props.canCreateTeams);

const switchTeam = (teamId: number | 'personal') => {
    router.get('/dashboard', { team: teamId }, { preserveState: false });
};

const currentContextName = computed(() => {
    if (props.currentTeam) {
        return props.currentTeam.name;
    }
    return 'Personal';
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

const form = useForm({
    url: '',
    team_id: props.currentTeamId ?? null,
});

// Bulk scan form
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

// Cooldown state
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

// Check if URL looks valid enough to check cooldown
const isValidUrlForCheck = (url: string): boolean => {
    if (!url) return false;
    // Check if it's a valid URL pattern
    try {
        new URL(url);
        return true;
    } catch {
        // Also allow URLs without protocol that look like domains
        try {
            new URL('https://' + url);
            return url.includes('.');
        } catch {
            return false;
        }
    }
};

// Normalize URL for cooldown check
const normalizeUrl = (url: string): string => {
    if (!url.startsWith('http://') && !url.startsWith('https://')) {
        return 'https://' + url;
    }
    return url;
};

// Check cooldown when URL changes (debounced)
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

// Debounced URL watcher
watch(() => form.url, (newUrl) => {
    if (cooldownCheckTimeout) {
        clearTimeout(cooldownCheckTimeout);
    }
    // Reset cooldown when URL changes
    cooldown.value = null;

    if (newUrl && newUrl.length > 3) {
        cooldownCheckTimeout = setTimeout(() => {
            checkCooldown(newUrl);
        }, 500);
    }
});

const submit = () => {
    if (isOnCooldown.value) return;
    // Ensure team_id is synced with current context before submitting
    form.team_id = props.currentTeamId ?? null;
    form.post('/scan', {
        preserveScroll: true,
    });
};

const viewExistingScan = () => {
    if (cooldown.value?.existing_scan_uuid) {
        router.visit(`/scans/${cooldown.value.existing_scan_uuid}`);
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

const getScoreColor = (score: number) => {
    if (score >= 80) return 'text-green-600 dark:text-green-400';
    if (score >= 60) return 'text-blue-600 dark:text-blue-400';
    if (score >= 40) return 'text-yellow-600 dark:text-yellow-400';
    return 'text-red-600 dark:text-red-400';
};

const truncateUrl = (url: string, maxLength = 50) => {
    if (url.length <= maxLength) return url;
    return url.substring(0, maxLength) + '...';
};

const usagePercentage = () => {
    if (props.usage.is_unlimited) return 0;
    if (props.usage.scans_limit === 0) return 100;
    return Math.min(100, Math.round((props.usage.scans_used / props.usage.scans_limit) * 100));
};

const getUsageColor = () => {
    const pct = usagePercentage();
    if (pct >= 90) return 'text-red-600 dark:text-red-400';
    if (pct >= 70) return 'text-yellow-600 dark:text-yellow-400';
    return 'text-green-600 dark:text-green-400';
};

const getProgressColor = () => {
    const pct = usagePercentage();
    if (pct >= 90) return '[&>div]:bg-red-500';
    if (pct >= 70) return '[&>div]:bg-yellow-500';
    return '[&>div]:bg-green-500';
};
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <!-- Team Branding Banner -->
            <div
                v-if="teamBranding?.enabled"
                class="flex items-center gap-4 rounded-lg p-4 text-white"
                :style="{
                    background: `linear-gradient(135deg, ${teamBranding.primaryColor} 0%, ${teamBranding.secondaryColor} 100%)`
                }"
            >
                <img
                    v-if="teamBranding.logoUrl"
                    :src="teamBranding.logoUrl"
                    :alt="teamBranding.companyName"
                    class="h-10 w-auto object-contain bg-white/20 rounded p-1"
                />
                <div
                    v-else
                    class="flex h-10 w-10 items-center justify-center rounded bg-white/20 text-xl font-bold"
                >
                    {{ teamBranding.companyName.charAt(0).toUpperCase() }}
                </div>
                <div>
                    <h2 class="font-semibold">{{ teamBranding.companyName }}</h2>
                    <p class="text-sm opacity-90">{{ teamBranding.teamName }} Dashboard</p>
                </div>
            </div>

            <!-- Upgrade Banner -->
            <Alert v-if="showUpgradePrompt && usage.plan_key === 'free'" class="border-primary/50 bg-gradient-to-r from-primary/5 to-primary/10">
                <Zap class="h-4 w-4 text-primary" />
                <AlertDescription class="flex items-center justify-between">
                    <span>
                        <strong>Unlock more scans!</strong> Upgrade to Pro for 50 scans/month, full GEO breakdown, and PDF export.
                    </span>
                    <Link href="/billing/plans">
                        <Button size="sm" class="ml-4">
                            View Plans
                            <ArrowRight class="ml-1 h-4 w-4" />
                        </Button>
                    </Link>
                </AlertDescription>
            </Alert>

            <!-- Team Switcher (for users with multiple teams) -->
            <div v-if="teams && teams.length > 0" class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="text-sm text-muted-foreground">Viewing:</span>
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button variant="outline" class="gap-2">
                                <Building2 v-if="currentTeam" class="h-4 w-4" />
                                <User v-else class="h-4 w-4" />
                                {{ currentContextName }}
                                <ChevronDown class="h-4 w-4 opacity-50" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="start" class="w-56">
                            <DropdownMenuLabel>Switch Context</DropdownMenuLabel>
                            <DropdownMenuSeparator />
                            <template v-if="hasPersonalOption">
                                <DropdownMenuItem
                                    class="cursor-pointer gap-2"
                                    :class="{ 'bg-accent': !currentTeamId }"
                                    @click="switchTeam('personal')"
                                >
                                    <User class="h-4 w-4" />
                                    Personal
                                </DropdownMenuItem>
                                <DropdownMenuSeparator />
                            </template>
                            <DropdownMenuLabel class="text-xs font-normal text-muted-foreground">Teams</DropdownMenuLabel>
                            <DropdownMenuItem
                                v-for="team in teams"
                                :key="team.id"
                                class="cursor-pointer gap-2"
                                :class="{ 'bg-accent': currentTeamId === team.id }"
                                @click="switchTeam(team.id)"
                            >
                                <Building2 class="h-4 w-4" />
                                <span class="flex-1">{{ team.name }}</span>
                                <Badge v-if="team.is_owner" variant="secondary" class="ml-2 text-xs">Owner</Badge>
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
                <p class="text-sm text-muted-foreground">
                    <template v-if="currentTeam">
                        Showing scans from <strong>{{ currentTeam.name }}</strong>
                    </template>
                    <template v-else>
                        Showing your personal scans
                    </template>
                </p>
            </div>

            <!-- Scan Form Card -->
            <Card
                class="border-2 border-dashed bg-gradient-to-br to-transparent"
                :class="teamBranding?.enabled ? '' : 'border-primary/20 from-primary/5'"
                :style="teamBranding?.enabled ? {
                    borderColor: `${teamBranding.primaryColor}33`,
                    background: `linear-gradient(to bottom right, ${teamBranding.primaryColor}0D, transparent)`
                } : {}"
            >
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Globe
                            class="h-5 w-5"
                            :class="teamBranding?.enabled ? '' : 'text-primary'"
                            :style="teamBranding?.enabled ? { color: teamBranding.primaryColor } : {}"
                        />
                        Start a GEO Scan
                    </CardTitle>
                    <CardDescription>
                        {{ canBulkScan ? 'Scan a single URL or multiple URLs at once' : 'Enter a URL to analyze its Generative Engine Optimization score' }}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <Tabs default-value="single" class="w-full">
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
                            <form @submit.prevent="submit" class="flex gap-3">
                                <div class="flex-1">
                                    <Input
                                        v-model="form.url"
                                        type="url"
                                        placeholder="https://example.com/page"
                                        class="h-12 text-base"
                                        :disabled="form.processing || !usage.can_scan"
                                    />
                                </div>
                                <Button
                                    type="submit"
                                    size="lg"
                                    :disabled="form.processing || !form.url || !usage.can_scan || isOnCooldown || checkingCooldown"
                                    class="h-12 px-8"
                                >
                                    <Clock v-if="isOnCooldown" class="mr-2 h-4 w-4" />
                                    <svg v-else-if="form.processing || checkingCooldown" class="mr-2 h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
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
                                        {{ form.processing ? 'Scanning...' : 'Scan URL' }}
                                    </template>
                                </Button>
                            </form>

                            <Alert v-if="form.errors.url" variant="destructive" class="mt-3">
                                <AlertDescription>{{ form.errors.url }}</AlertDescription>
                            </Alert>
                            <Alert v-if="form.errors.cooldown" class="mt-3 border-yellow-500/50 bg-yellow-50 text-yellow-800 dark:border-yellow-500/30 dark:bg-yellow-950/50 dark:text-yellow-200">
                                <Clock class="h-4 w-4" />
                                <AlertDescription>{{ form.errors.cooldown }}</AlertDescription>
                            </Alert>
                            <Alert v-else-if="isOnCooldown" class="mt-3 border-amber-500/50 bg-amber-50 text-amber-800 dark:border-amber-500/30 dark:bg-amber-950/50 dark:text-amber-200">
                                <Clock class="h-4 w-4 text-amber-600 dark:text-amber-400" />
                                <AlertDescription class="flex items-center justify-between gap-4">
                                    <span>This URL was scanned recently. Please wait {{ cooldownMinutes }} {{ cooldownMinutes === 1 ? 'minute' : 'minutes' }} before scanning again.</span>
                                    <Button v-if="cooldown?.existing_scan_uuid" variant="outline" size="sm" @click="viewExistingScan" class="shrink-0">
                                        View Existing Scan
                                    </Button>
                                </AlertDescription>
                            </Alert>
                            <Alert v-if="form.errors.limit" variant="destructive" class="mt-3">
                                <AlertDescription>
                                    {{ form.errors.limit }}
                                    <Link href="/billing/plans" class="ml-2 underline">Upgrade now</Link>
                                </AlertDescription>
                            </Alert>
                            <Alert v-if="!usage.can_scan && !form.errors.limit" variant="destructive" class="mt-3">
                                <AlertDescription>
                                    You've reached your monthly scan limit.
                                    <Link href="/billing/plans" class="ml-1 underline">Upgrade your plan</Link> to continue scanning.
                                </AlertDescription>
                            </Alert>
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
                                                <span class="text-lg font-bold" :class="getScoreColor(scan.score || 0)">
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
                                    size="lg"
                                    :disabled="bulkProcessing || bulkUrlCount === 0 || bulkUrlCount > 50"
                                    class="h-12"
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

            <!-- Stats Cards -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-5">
                <!-- Usage Card -->
                <Card class="md:col-span-2 lg:col-span-1">
                    <CardContent class="pt-6">
                        <div class="flex flex-col gap-3">
                            <div class="flex items-center justify-between">
                                <p class="text-sm font-medium text-muted-foreground">Scans This Month</p>
                                <span class="rounded-full bg-muted px-2 py-0.5 text-xs font-medium">
                                    {{ usage.plan_name }}
                                </span>
                            </div>
                            <div class="flex items-baseline gap-1">
                                <span class="text-3xl font-bold" :class="getUsageColor()">
                                    {{ usage.scans_used }}
                                </span>
                                <span class="text-muted-foreground">
                                    / {{ usage.is_unlimited ? '∞' : usage.scans_limit }}
                                </span>
                            </div>
                            <Progress
                                v-if="!usage.is_unlimited"
                                :model-value="usagePercentage()"
                                class="h-2"
                                :class="getProgressColor()"
                            />
                            <p v-if="!usage.is_unlimited" class="text-xs text-muted-foreground">
                                {{ usage.scans_remaining }} scans remaining
                            </p>
                            <p v-else class="text-xs text-muted-foreground">
                                Unlimited scans
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="pt-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Total Scans</p>
                                <p class="text-3xl font-bold">{{ stats.total_scans }}</p>
                            </div>
                            <div class="rounded-full bg-primary/10 p-3">
                                <Globe class="h-6 w-6 text-primary" />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="pt-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Average Score</p>
                                <p class="text-3xl font-bold" :class="getScoreColor(stats.avg_score)">
                                    {{ stats.avg_score.toFixed(1) }}
                                </p>
                            </div>
                            <div class="rounded-full bg-blue-100 p-3 dark:bg-blue-950">
                                <TrendingUp class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="pt-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Best Score</p>
                                <p class="text-3xl font-bold" :class="getScoreColor(stats.best_score)">
                                    {{ stats.best_score.toFixed(1) }}
                                </p>
                            </div>
                            <div class="rounded-full bg-green-100 p-3 dark:bg-green-950">
                                <Target class="h-6 w-6 text-green-600 dark:text-green-400" />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="pt-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">This Week</p>
                                <p class="text-3xl font-bold">{{ stats.scans_this_week }}</p>
                            </div>
                            <div class="rounded-full bg-purple-100 p-3 dark:bg-purple-950">
                                <Calendar class="h-6 w-6 text-purple-600 dark:text-purple-400" />
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Recent Scans -->
            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle>Recent Scans</CardTitle>
                            <CardDescription>Your latest GEO analysis results</CardDescription>
                        </div>
                        <Link v-if="recentScans.length > 0" href="/scans">
                            <Button variant="outline" size="sm">View All</Button>
                        </Link>
                    </div>
                </CardHeader>
                <CardContent>
                    <div v-if="recentScans.length === 0" class="py-12 text-center">
                        <Globe class="mx-auto h-12 w-12 text-muted-foreground/50" />
                        <h3 class="mt-4 text-lg font-medium">No scans yet</h3>
                        <p class="mt-2 text-sm text-muted-foreground">
                            Enter a URL above to start analyzing your content
                        </p>
                    </div>

                    <div v-else class="space-y-3">
                        <Link
                            v-for="scan in recentScans"
                            :key="scan.id"
                            :href="`/scans/${scan.uuid || scan.id}`"
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
                                    <p v-if="scan.user && currentTeam" class="mt-1 text-xs text-muted-foreground">
                                        by {{ scan.user.name }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-4">
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
                                <div class="text-right text-sm text-muted-foreground">
                                    {{ formatDate(scan.created_at) }}
                                </div>
                            </div>
                        </Link>
                    </div>
                </CardContent>
            </Card>

            <!-- Citation Tracking Section (Agency only) -->
            <Card v-if="citationData">
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle class="flex items-center gap-2">
                                <Quote class="h-5 w-5" />
                                Citation Tracking
                            </CardTitle>
                            <CardDescription>Monitor how AI platforms cite your content</CardDescription>
                        </div>
                        <Link href="/citations">
                            <Button variant="outline" size="sm">View All</Button>
                        </Link>
                    </div>
                </CardHeader>
                <CardContent>
                    <!-- Citation Stats -->
                    <div class="grid gap-4 mb-6 sm:grid-cols-3">
                        <div class="rounded-lg border p-3 text-center">
                            <p class="text-2xl font-bold">{{ citationData.stats.total_queries }}</p>
                            <p class="text-xs text-muted-foreground">Queries Tracked</p>
                        </div>
                        <div class="rounded-lg border p-3 text-center">
                            <p class="text-2xl font-bold text-green-600">{{ citationData.stats.cited_count }}</p>
                            <p class="text-xs text-muted-foreground">Citations Found</p>
                        </div>
                        <div class="rounded-lg border p-3 text-center">
                            <p class="text-2xl font-bold" :class="citationData.stats.citation_rate >= 50 ? 'text-green-600' : 'text-yellow-600'">
                                {{ citationData.stats.citation_rate }}%
                            </p>
                            <p class="text-xs text-muted-foreground">Citation Rate</p>
                        </div>
                    </div>

                    <!-- Recent Queries -->
                    <div v-if="citationData.queries.length === 0" class="py-8 text-center">
                        <Quote class="mx-auto h-12 w-12 text-muted-foreground/50" />
                        <h3 class="mt-4 text-lg font-medium">No citation queries yet</h3>
                        <p class="mt-2 text-sm text-muted-foreground">
                            Create a query to track how AI platforms cite your content
                        </p>
                        <Link href="/citations/queries/create" class="mt-4 inline-block">
                            <Button>Create Your First Query</Button>
                        </Link>
                    </div>

                    <div v-else class="space-y-2">
                        <Link
                            v-for="query in citationData.queries"
                            :key="query.id"
                            :href="`/citations/queries/${query.uuid}`"
                            class="flex items-center justify-between rounded-lg border p-3 transition-colors hover:bg-muted/50"
                        >
                            <div class="flex-1 min-w-0">
                                <p class="font-medium truncate">{{ query.query }}</p>
                                <p class="text-sm text-muted-foreground truncate">{{ query.domain }}</p>
                            </div>
                            <div class="flex items-center gap-2 ml-4">
                                <template v-if="query.is_cited !== null">
                                    <CheckCircle2 v-if="query.is_cited" class="h-5 w-5 text-green-600" />
                                    <XCircle v-else class="h-5 w-5 text-red-500" />
                                </template>
                                <span v-else class="text-sm text-muted-foreground">Not checked</span>
                            </div>
                        </Link>
                    </div>
                </CardContent>
            </Card>

            <!-- Teams Section (Agency only) -->
            <Card v-if="teams">
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle class="flex items-center gap-2">
                                <Users class="h-5 w-5" />
                                Your Teams
                            </CardTitle>
                            <CardDescription>Collaborate with your team members</CardDescription>
                        </div>
                        <Link v-if="canCreateTeams" href="/teams/create">
                            <Button size="sm">
                                <Plus class="mr-2 h-4 w-4" />
                                New Team
                            </Button>
                        </Link>
                    </div>
                </CardHeader>
                <CardContent>
                    <div v-if="teams.length === 0" class="py-8 text-center">
                        <Users class="mx-auto h-12 w-12 text-muted-foreground/50" />
                        <h3 class="mt-4 text-lg font-medium">No teams yet</h3>
                        <p v-if="canCreateTeams" class="mt-2 text-sm text-muted-foreground">
                            Create a team to start collaborating with others
                        </p>
                        <p v-else class="mt-2 text-sm text-muted-foreground">
                            You'll see teams here when you're invited to join one
                        </p>
                        <Link v-if="canCreateTeams" href="/teams/create" class="mt-4 inline-block">
                            <Button>Create Your First Team</Button>
                        </Link>
                    </div>

                    <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <Link
                            v-for="team in teams"
                            :key="team.id"
                            :href="`/teams/${team.slug}`"
                            class="group flex flex-col rounded-lg border p-4 transition-colors hover:bg-muted/50"
                        >
                            <div class="flex items-start justify-between">
                                <div class="flex items-center gap-2">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-primary/10 text-primary">
                                        {{ team.name.charAt(0).toUpperCase() }}
                                    </div>
                                    <div>
                                        <p class="font-medium group-hover:text-primary">{{ team.name }}</p>
                                        <p class="text-xs text-muted-foreground">
                                            {{ team.members_count }} member{{ team.members_count !== 1 ? 's' : '' }}
                                        </p>
                                    </div>
                                </div>
                                <Badge v-if="team.is_owner" variant="secondary" class="gap-1">
                                    <Crown class="h-3 w-3" />
                                    Owner
                                </Badge>
                                <Badge v-else variant="outline" class="capitalize">
                                    {{ team.role }}
                                </Badge>
                            </div>
                        </Link>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
