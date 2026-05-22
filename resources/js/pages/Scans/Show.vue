<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
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
    ChevronDown,
    XCircle,
    Info,
    Coins,
    Users,
    TrendingUp,
    BarChart3,
    Lightbulb,
    Target,
    Sparkles,
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
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { register } from '@/routes';
import { useDateFormat } from '@/composables/useDateFormat';
import { getPillarExplanations as getPillarExplanationsFromModule } from '@/utils/pillar-explanations';
import { type BreadcrumbItem, type Scan, type PillarResult, type Recommendation, type AISuggestion, type BenchmarkResult, type CompetitorBenchmarkResult } from '@/types';

const { formatDate } = useDateFormat();

interface TierCooldownInfo {
    minutes_remaining: number;
    available_at: string;
}

interface CooldownInfo {
    basic?: TierCooldownInfo | null;
    pro?: TierCooldownInfo | null;
}

interface DiscoveredCompetitor {
    uuid: string;
    url: string;
    title: string | null;
    status: string;
    score: number | null;
    grade: string | null;
}

interface ParentScan {
    uuid: string;
    url: string;
    title: string | null;
}

interface Props {
    scan: Scan;
    canExportPdf: boolean;
    canEmailReport: boolean;
    cooldown?: CooldownInfo | null;
    discoveredCompetitors?: DiscoveredCompetitor[];
    parentScan?: ParentScan | null;
    isGuest?: boolean;
    remainingGuestScans?: number;
    statusUrl?: string;
}

const props = defineProps<Props>();
const page = usePage();

// Get user's token balance
const tokenBalance = computed(() => page.props.auth?.user?.token_balance ?? 0);

// Tier options for rescan modal
const tierOptions = [
    { value: 'basic', label: 'Basic', pillars: 5, tokens: 0, cooldown: 5, description: 'Essential GEO metrics' },
    { value: 'pro', label: 'Pro', pillars: 8, tokens: 5, cooldown: 1, description: 'Extended analysis' },
    { value: 'full', label: 'Full', pillars: 12, tokens: 10, cooldown: 1, description: 'Complete GEO breakdown' },
];

// Rescan modal state
const showRescanModal = ref(false);
const selectedRescanTier = ref('basic');

// Cooldown state management - now tier-based
const basicCooldownMinutes = ref(Math.ceil(props.cooldown?.basic?.minutes_remaining || 0));
const proCooldownMinutes = ref(Math.ceil(props.cooldown?.pro?.minutes_remaining || 0));
const basicCooldownAvailableAt = ref(props.cooldown?.basic?.available_at ? new Date(props.cooldown.basic.available_at) : null);
const proCooldownAvailableAt = ref(props.cooldown?.pro?.available_at ? new Date(props.cooldown.pro.available_at) : null);
let cooldownInterval: ReturnType<typeof setInterval> | null = null;

// Check if a specific tier is on cooldown
const isTierOnCooldown = (tier: string): boolean => {
    if (tier === 'basic') {
        return basicCooldownMinutes.value > 0;
    }
    // Pro and Full have same cooldown
    return proCooldownMinutes.value > 0;
};

// Get cooldown minutes for a tier
const getTierCooldownMinutes = (tier: string): number => {
    if (tier === 'basic') {
        return basicCooldownMinutes.value;
    }
    return proCooldownMinutes.value;
};

// Check if user can afford a tier
const canAffordTier = (tier: string): boolean => {
    const option = tierOptions.find(t => t.value === tier);
    if (!option) return false;
    if (option.tokens === 0) return true;
    return tokenBalance.value >= option.tokens;
};

// Legacy computed for basic cooldown display (used by rescan button)
const isOnCooldown = computed(() => basicCooldownMinutes.value > 0 && proCooldownMinutes.value > 0);

const updateCooldowns = () => {
    const now = new Date();

    // Update basic cooldown
    if (basicCooldownAvailableAt.value) {
        const diff = basicCooldownAvailableAt.value.getTime() - now.getTime();
        if (diff > 0) {
            basicCooldownMinutes.value = Math.ceil(diff / 60000);
        } else {
            basicCooldownMinutes.value = 0;
            basicCooldownAvailableAt.value = null;
        }
    }

    // Update pro cooldown
    if (proCooldownAvailableAt.value) {
        const diff = proCooldownAvailableAt.value.getTime() - now.getTime();
        if (diff > 0) {
            proCooldownMinutes.value = Math.ceil(diff / 60000);
        } else {
            proCooldownMinutes.value = 0;
            proCooldownAvailableAt.value = null;
        }
    }

    // Clear interval if both cooldowns are done
    if (!basicCooldownAvailableAt.value && !proCooldownAvailableAt.value) {
        if (cooldownInterval) {
            clearInterval(cooldownInterval);
            cooldownInterval = null;
        }
    }
};

// Start cooldown timer if needed
if (props.cooldown?.basic?.available_at || props.cooldown?.pro?.available_at) {
    cooldownInterval = setInterval(updateCooldowns, 10000); // Update every 10 seconds
}

const scanStatus = ref(props.scan.status || 'completed');
const progressStep = ref(props.scan.progress_step || 'Initializing');
const progressPercent = ref(props.scan.progress_percent || 0);
const scanTitle = ref(props.scan.title);
const errorMessage = ref(props.scan.error_message);
const reloading = ref(false);
let pollInterval: ReturnType<typeof setInterval> | null = null;

// Competitor discovery reactive state
const competitorDiscoveryStatus = ref(props.scan.competitor_discovery_status);
const competitorsFound = ref(props.scan.competitors_found ?? 0);

// Collapsible section states (start collapsed)
const isCompetitorsExpanded = ref(false);
const isSuggestionsExpanded = ref(false);
const discoveredCompetitorsList = ref<DiscoveredCompetitor[]>(props.discoveredCompetitors || []);
let competitorPollInterval: ReturnType<typeof setInterval> | null = null;

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

// Competitor discovery computed states
const isCompetitorDiscoveryInProgress = computed(() => {
    const status = competitorDiscoveryStatus.value;
    return status === 'pending' || status === 'discovering' || status === 'scanning';
});

const isCompetitorDiscoveryComplete = computed(() => competitorDiscoveryStatus.value === 'completed');
const isCompetitorDiscoveryFailed = computed(() => competitorDiscoveryStatus.value === 'failed');

// Progress steps for visual display with descriptions
const progressSteps = [
    {
        key: 'fetching',
        label: 'Fetching webpage',
        description: 'Downloading and rendering your page content',
        percent: 10
    },
    {
        key: 'analyzing',
        label: 'Analyzing page structure',
        description: 'Extracting headings, content blocks, schema markup, and metadata',
        percent: 20
    },
    {
        key: 'llms',
        label: 'Checking llms.txt',
        description: 'Looking for AI-specific instructions that help LLMs understand your content',
        percent: 30
    },
    {
        key: 'scoring_pillars',
        label: 'Scoring GEO pillars',
        description: 'Evaluating definitions, structure, and answerability',
        percent: 40
    },
    {
        key: 'scoring_authority',
        label: 'Evaluating authority & trust',
        description: 'Analyzing E-E-A-T signals, citations, and expertise indicators',
        percent: 50
    },
    {
        key: 'scoring_advanced',
        label: 'Analyzing advanced metrics',
        description: 'Checking freshness, readability, and multimedia content',
        percent: 60
    },
    {
        key: 'scoring_final',
        label: 'Finalizing pillar scores',
        description: 'Completing score calculations for all pillars',
        percent: 70
    },
    {
        key: 'finding_similar',
        label: 'Finding similar content',
        description: 'Comparing with other scanned pages for benchmarking',
        percent: 75
    },
    {
        key: 'recommendations',
        label: 'Generating AI recommendations',
        description: 'Creating personalized improvement suggestions',
        percent: 85
    },
    {
        key: 'benchmarks',
        label: 'Calculating benchmarks',
        description: 'Computing your position relative to other content',
        percent: 95
    },
];

const currentStepIndex = computed(() => {
    const percent = progressPercent.value;
    if (percent >= 95) return 9; // benchmarks
    if (percent >= 85) return 8; // recommendations
    if (percent >= 75) return 7; // finding_similar
    if (percent >= 70) return 6; // scoring_final
    if (percent >= 60) return 5; // scoring_advanced
    if (percent >= 50) return 4; // scoring_authority
    if (percent >= 40) return 3; // scoring_pillars
    if (percent >= 30) return 2; // llms
    if (percent >= 20) return 1; // analyzing
    return 0; // fetching
});

const scanStatusUrl = computed(() => props.statusUrl ?? `/scans/${props.scan.uuid}/status`);

const pollStatus = async () => {
    try {
        const response = await fetch(scanStatusUrl.value);
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

        // Also update competitor discovery status during scan polling
        if (data.competitor_discovery_status) {
            competitorDiscoveryStatus.value = data.competitor_discovery_status;
            competitorsFound.value = data.competitors_found ?? 0;
            if (data.discovered_competitors) {
                discoveredCompetitorsList.value = data.discovered_competitors;
            }
        }

        errorMessage.value = data.error_message;

        if (data.status === 'completed' || data.status === 'failed' || data.status === 'cancelled') {
            if (pollInterval) {
                clearInterval(pollInterval);
                pollInterval = null;
            }
            if (data.status === 'completed') {
                // Check if competitor discovery is in progress - if so, start competitor polling instead of reloading
                const discoveryInProgress = data.competitor_discovery_status &&
                    ['pending', 'discovering', 'scanning'].includes(data.competitor_discovery_status);

                if (discoveryInProgress && !competitorPollInterval) {
                    // Start competitor discovery polling
                    scanStatus.value = 'completed';
                    progressStep.value = 'Completed';
                    progressPercent.value = 100;
                    reloading.value = false;
                    competitorPollInterval = setInterval(pollCompetitorStatus, 2000);
                    // Do an initial reload to show results while competitors are being discovered
                    router.reload();
                } else if (!discoveryInProgress) {
                    // No competitor discovery, just reload as usual
                    reloading.value = true;
                    progressStep.value = 'Loading results...';
                    progressPercent.value = 100;
                    router.reload();
                }
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

// Competitor discovery polling
const pollCompetitorStatus = async () => {
    try {
        const response = await fetch(scanStatusUrl.value);
        const data = await response.json();

        // Update competitor discovery state
        if (data.competitor_discovery_status) {
            competitorDiscoveryStatus.value = data.competitor_discovery_status;
            competitorsFound.value = data.competitors_found ?? 0;

            // Update discovered competitors list if available
            if (data.discovered_competitors) {
                discoveredCompetitorsList.value = data.discovered_competitors;
            }
        }

        // Stop polling when discovery is complete or failed
        if (data.competitor_discovery_status === 'completed' || data.competitor_discovery_status === 'failed') {
            if (competitorPollInterval) {
                clearInterval(competitorPollInterval);
                competitorPollInterval = null;
            }
            // Reload page to get final results with benchmarks
            if (data.competitor_discovery_status === 'completed') {
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
    // Start competitor polling if discovery is in progress
    if (isCompetitorDiscoveryInProgress.value) {
        competitorPollInterval = setInterval(pollCompetitorStatus, 2000);
    }
});

onUnmounted(() => {
    if (pollInterval) {
        clearInterval(pollInterval);
    }
    if (cooldownInterval) {
        clearInterval(cooldownInterval);
    }
    if (competitorPollInterval) {
        clearInterval(competitorPollInterval);
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
const rescanError = ref('');
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

const openRescanModal = () => {
    selectedRescanTier.value = 'basic';
    rescanError.value = '';
    showRescanModal.value = true;
};

const rescan = async (tier: string = 'basic') => {
    rescanning.value = true;
    rescanProgress.value = 'Starting rescan...';
    rescanError.value = '';
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
            body: JSON.stringify({ tier }),
        });

        const data = await response.json();

        if (data.uuid) {
            showRescanModal.value = false;
            newScanUuid.value = data.uuid;
            rescanProgress.value = 'Scanning...';
            pollNewScan();
        } else if (data.error) {
            rescanProgress.value = '';
            rescanning.value = false;
            rescanError.value = data.error;
        } else {
            // Fallback: if we got a redirect response or unexpected format
            rescanProgress.value = '';
            rescanning.value = false;
            showRescanModal.value = false;
            router.reload();
        }
    } catch {
        rescanProgress.value = '';
        rescanning.value = false;
        rescanError.value = 'An unexpected error occurred. Please try again.';
    }
};

const deleteScan = () => {
    if (confirm('Are you sure you want to delete this scan?')) {
        deleting.value = true;
        router.delete(`/scans/${props.scan.uuid}`);
    }
};

const getGradeColor = (grade: string) => {
    if (grade.startsWith('A')) return 'text-green-600 bg-green-100 border-green-200 dark:text-green-400 dark:bg-green-500/10 dark:border-green-800';
    if (grade.startsWith('B')) return 'text-blue-600 bg-blue-100 border-blue-200 dark:text-blue-400 dark:bg-blue-500/10 dark:border-blue-800';
    if (grade.startsWith('C')) return 'text-yellow-600 bg-yellow-100 border-yellow-200 dark:text-yellow-400 dark:bg-yellow-500/10 dark:border-yellow-800';
    if (grade.startsWith('D')) return 'text-orange-600 bg-orange-100 border-orange-200 dark:text-orange-400 dark:bg-orange-500/10 dark:border-orange-800';
    return 'text-red-600 bg-red-100 border-red-200 dark:text-red-400 dark:bg-red-500/10 dark:border-red-800';
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

// AI suggestions from RAG analysis
const aiSuggestions = computed((): AISuggestion[] => {
    const suggestions = props.scan.results?.ai_suggestions;
    if (!suggestions || !Array.isArray(suggestions)) return [];
    return suggestions;
});

// Benchmark against own content
const benchmark = computed((): BenchmarkResult | null => {
    return props.scan.results?.benchmark ?? null;
});

// Benchmark against competitors
const competitorBenchmark = computed((): CompetitorBenchmarkResult | null => {
    return props.scan.results?.competitor_benchmark ?? null;
});

// Whether we have sufficient data for benchmarking
const hasSufficientData = computed(() => props.scan.results?.has_sufficient_data ?? false);

// AI Citation Readiness (separate metric from main score)
const citationReadiness = computed(() => props.scan.results?.citation_readiness ?? null);

// Priority color mapping for AI suggestions
const getPriorityBadgeClass = (priority: string) => {
    switch (priority) {
        case 'critical':
            return 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300';
        case 'high':
            return 'bg-orange-100 text-orange-700 dark:bg-orange-900 dark:text-orange-300';
        case 'medium':
            return 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300';
        case 'low':
            return 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300';
        default:
            return 'bg-gray-100 text-gray-700 dark:bg-gray-900 dark:text-gray-300';
    }
};

// Position color mapping for benchmarks
const getPositionClass = (position: string) => {
    switch (position) {
        case 'dominant':
        case 'leader':
            return 'text-green-600 dark:text-green-400';
        case 'ahead':
        case 'above_average':
            return 'text-blue-600 dark:text-blue-400';
        case 'competitive':
        case 'average':
            return 'text-yellow-600 dark:text-yellow-400';
        case 'behind':
        case 'below_average':
            return 'text-orange-600 dark:text-orange-400';
        case 'far_behind':
        case 'needs_improvement':
            return 'text-red-600 dark:text-red-400';
        default:
            return 'text-muted-foreground';
    }
};

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

    <component :is="isGuest ? 'div' : AppLayout" v-bind="isGuest ? { class: 'min-h-screen bg-background text-foreground' } : { breadcrumbs }">
        <!-- Guest banner -->
        <div v-if="isGuest" class="border-b bg-primary/5 px-6 py-3">
            <div class="mx-auto flex max-w-7xl items-center justify-between">
                <p class="text-sm text-muted-foreground">
                    <span class="font-medium text-foreground">Free scan</span> —
                    <template v-if="remainingGuestScans && remainingGuestScans > 0">{{ remainingGuestScans }} free scan{{ remainingGuestScans !== 1 ? 's' : '' }} remaining.</template>
                    <template v-else>You've used all free scans.</template>
                    <Link :href="register()" class="ml-1 font-medium text-primary hover:underline">Create a free account</Link> for unlimited basic scans.
                </p>
                <Link :href="register()">
                    <Button size="sm">Sign Up Free</Button>
                </Link>
            </div>
        </div>

        <div class="flex flex-col gap-6 p-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <Link :href="isGuest ? '/' : '/dashboard'">
                            <Button variant="ghost" size="sm">
                                <ArrowLeft class="mr-1 h-4 w-4" />
                                Back
                            </Button>
                        </Link>
                    </div>
                    <div class="mt-2 flex items-center gap-2">
                        <h1 class="text-2xl font-bold">{{ scan.title || 'Scan Results' }}</h1>
                        <span
                            v-if="scan.is_competitor"
                            class="inline-flex items-center gap-1 rounded-full bg-purple-100 px-2 py-0.5 text-xs font-medium text-purple-700 dark:bg-purple-900 dark:text-purple-300"
                        >
                            <Users class="h-3 w-3" />
                            Competitor
                        </span>
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
                            @click="openRescanModal"
                            :disabled="rescanning"
                        >
                            <Loader2 v-if="rescanning" class="mr-2 h-4 w-4 animate-spin" />
                            <RefreshCw v-else class="mr-2 h-4 w-4" />
                            <template v-if="rescanning">
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
                        <div class="space-y-4">
                            <div
                                v-for="(step, index) in progressSteps"
                                :key="step.key"
                                class="flex items-start gap-3"
                            >
                                <!-- Step Icon -->
                                <div
                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full border-2 transition-all"
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

                                <!-- Step Label and Description -->
                                <div class="flex-1 pt-1">
                                    <span
                                        class="text-sm transition-colors block"
                                        :class="{
                                            'font-medium text-foreground': index <= currentStepIndex,
                                            'text-muted-foreground': index > currentStepIndex,
                                        }"
                                    >
                                        {{ step.label }}
                                    </span>
                                    <span
                                        v-if="index === currentStepIndex"
                                        class="text-xs text-muted-foreground mt-0.5 block"
                                    >
                                        {{ step.description }}
                                    </span>
                                </div>
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
            <Alert v-else-if="isFailed" variant="destructive" class="border-destructive/30 bg-destructive/5 dark:border-destructive/30 dark:bg-destructive/10">
                <AlertCircle class="h-5 w-5" />
                <AlertTitle>Scan Failed</AlertTitle>
                <AlertDescription>
                    {{ errorMessage || 'An unexpected error occurred while scanning the URL.' }}
                    <div class="mt-4">
                        <Button variant="outline" @click="openRescanModal" :disabled="rescanning">
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
                        <Button variant="outline" @click="openRescanModal" :disabled="rescanning">
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
                                <h4 class="flex items-center gap-2 text-sm font-medium text-green-600 dark:text-green-400">
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

            <!-- AI Citation Readiness (separate metric for LLM citation potential) -->
            <Card v-if="isCompleted && citationReadiness" class="border-primary/30 bg-gradient-to-br from-primary/5 to-transparent">
                <CardHeader class="pb-3">
                    <CardTitle class="flex items-center gap-2">
                        <Sparkles class="h-5 w-5 text-primary" />
                        AI Citation Readiness
                        <TooltipProvider :delay-duration="0">
                            <Tooltip>
                                <TooltipTrigger>
                                    <HelpCircle class="h-4 w-4 text-muted-foreground" />
                                </TooltipTrigger>
                                <TooltipContent class="max-w-xs">
                                    <p class="text-sm">
                                        This score measures how likely AI assistants (ChatGPT, Claude, Perplexity, etc.)
                                        are to cite your content when answering user questions. Higher scores indicate
                                        content that is more "quotable" and authoritative for AI systems.
                                    </p>
                                </TooltipContent>
                            </Tooltip>
                        </TooltipProvider>
                    </CardTitle>
                    <CardDescription>
                        How likely AI assistants are to cite this content
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <!-- Main Score -->
                    <div class="flex items-center gap-6 mb-6">
                        <div class="flex items-center justify-center w-20 h-20 rounded-full border-4"
                            :class="citationReadiness.score >= 70 ? 'border-green-500 bg-green-50 dark:bg-green-500/10' :
                                    citationReadiness.score >= 50 ? 'border-yellow-500 bg-yellow-50 dark:bg-yellow-500/10' :
                                    'border-red-500 bg-red-50 dark:bg-red-500/10'">
                            <span class="text-2xl font-bold"
                                :class="citationReadiness.score >= 70 ? 'text-green-600 dark:text-green-400' :
                                        citationReadiness.score >= 50 ? 'text-yellow-600 dark:text-yellow-400' : 'text-red-600 dark:text-red-400'">
                                {{ citationReadiness.score }}
                            </span>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm text-muted-foreground">{{ citationReadiness.summary }}</p>
                        </div>
                    </div>

                    <!-- Factor Breakdown -->
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                        <div v-for="(factor, key) in citationReadiness.factors" :key="key"
                            class="bg-background/50 rounded-lg p-3 border">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-xs font-medium capitalize">{{ key.replace('_', ' ') }}</span>
                                <span class="text-sm font-bold"
                                    :class="factor.score >= 70 ? 'text-green-600 dark:text-green-400' :
                                            factor.score >= 50 ? 'text-yellow-600 dark:text-yellow-400' : 'text-red-600 dark:text-red-400'">
                                    {{ factor.score }}
                                </span>
                            </div>
                            <div class="h-1.5 bg-muted rounded-full overflow-hidden mb-2">
                                <div class="h-full rounded-full transition-all"
                                    :class="factor.score >= 70 ? 'bg-green-500' :
                                            factor.score >= 50 ? 'bg-yellow-500' : 'bg-red-500'"
                                    :style="{ width: `${factor.score}%` }">
                                </div>
                            </div>
                            <p class="text-xs text-muted-foreground line-clamp-2">{{ factor.reason }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Benchmarks Section (only show when completed) -->
            <div v-if="isCompleted && (benchmark || competitorBenchmark)" class="grid gap-4 md:grid-cols-2">
                <!-- Own Content Benchmark -->
                <Card v-if="benchmark">
                    <CardHeader class="pb-2">
                        <CardTitle class="flex items-center gap-2 text-base">
                            <BarChart3 class="h-5 w-5 text-blue-500" />
                            Your Content Benchmark
                            <TooltipProvider :delay-duration="0">
                                <Tooltip>
                                    <TooltipTrigger>
                                        <HelpCircle class="h-4 w-4 text-muted-foreground hover:text-foreground cursor-help" />
                                    </TooltipTrigger>
                                    <TooltipContent side="top" class="max-w-xs">
                                        <p>Compare this page's GEO score against your other scanned content. Helps identify which of your pages perform best in AI search visibility.</p>
                                    </TooltipContent>
                                </Tooltip>
                            </TooltipProvider>
                        </CardTitle>
                        <CardDescription>
                            How this scan compares to your other content
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div v-if="benchmark.position === 'insufficient_data'" class="text-center py-4">
                            <p class="text-muted-foreground">{{ benchmark.comparison }}</p>
                            <p class="text-xs text-muted-foreground mt-2">
                                Scans needed: {{ benchmark.scans_until_benchmark }}
                            </p>
                        </div>
                        <div v-else class="space-y-3">
                            <div class="flex items-center justify-between">
                                <TooltipProvider :delay-duration="0">
                                    <Tooltip>
                                        <TooltipTrigger class="text-sm text-muted-foreground flex items-center gap-1 cursor-help">
                                            Position <HelpCircle class="h-3 w-3" />
                                        </TooltipTrigger>
                                        <TooltipContent side="left" class="max-w-xs">
                                            <p>Your ranking among your own content: top performer, above average, average, or below average.</p>
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                                <span class="font-semibold capitalize" :class="getPositionClass(benchmark.position)">
                                    {{ benchmark.position.replace('_', ' ') }}
                                </span>
                            </div>
                            <div v-if="benchmark.percentile !== null" class="flex items-center justify-between">
                                <TooltipProvider :delay-duration="0">
                                    <Tooltip>
                                        <TooltipTrigger class="text-sm text-muted-foreground flex items-center gap-1 cursor-help">
                                            Percentile <HelpCircle class="h-3 w-3" />
                                        </TooltipTrigger>
                                        <TooltipContent side="left" class="max-w-xs">
                                            <p>This page scores better than {{ benchmark.percentile }}% of your other scanned content.</p>
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                                <span class="font-semibold">{{ benchmark.percentile }}th</span>
                            </div>
                            <div v-if="benchmark.avg_similar_score !== null" class="flex items-center justify-between">
                                <TooltipProvider :delay-duration="0">
                                    <Tooltip>
                                        <TooltipTrigger class="text-sm text-muted-foreground flex items-center gap-1 cursor-help">
                                            Avg. Similar Content <HelpCircle class="h-3 w-3" />
                                        </TooltipTrigger>
                                        <TooltipContent side="left" class="max-w-xs">
                                            <p>The average GEO score across all your scanned content (excluding competitors).</p>
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                                <span class="font-semibold">{{ benchmark.avg_similar_score }}</span>
                            </div>
                            <div v-if="benchmark.score_difference !== null" class="flex items-center justify-between">
                                <TooltipProvider :delay-duration="0">
                                    <Tooltip>
                                        <TooltipTrigger class="text-sm text-muted-foreground flex items-center gap-1 cursor-help">
                                            Difference <HelpCircle class="h-3 w-3" />
                                        </TooltipTrigger>
                                        <TooltipContent side="left" class="max-w-xs">
                                            <p>How many points this page scores above (+) or below (-) your average content score.</p>
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                                <span class="font-semibold" :class="benchmark.score_difference >= 0 ? 'text-green-600' : 'text-orange-600'">
                                    {{ benchmark.score_difference >= 0 ? '+' : '' }}{{ benchmark.score_difference }}
                                </span>
                            </div>
                            <p class="text-sm text-muted-foreground pt-2 border-t">
                                {{ benchmark.comparison }}
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Competitor Benchmark -->
                <Card v-if="competitorBenchmark">
                    <CardHeader class="pb-2">
                        <CardTitle class="flex items-center gap-2 text-base">
                            <Users class="h-5 w-5 text-purple-500" />
                            Competitor Benchmark
                            <TooltipProvider :delay-duration="0">
                                <Tooltip>
                                    <TooltipTrigger>
                                        <HelpCircle class="h-4 w-4 text-muted-foreground hover:text-foreground cursor-help" />
                                    </TooltipTrigger>
                                    <TooltipContent side="top" class="max-w-xs">
                                        <p>Compare your GEO score against competitor pages you've scanned. See where you stand in AI search visibility compared to your competition.</p>
                                    </TooltipContent>
                                </Tooltip>
                            </TooltipProvider>
                        </CardTitle>
                        <CardDescription>
                            How you compare to competitor scans
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div v-if="competitorBenchmark.position === 'no_competitors'" class="text-center py-4">
                            <p class="text-muted-foreground">{{ competitorBenchmark.comparison }}</p>
                            <Link href="/scans" class="text-sm text-primary hover:underline mt-2 block">
                                Start a competitor scan
                            </Link>
                        </div>
                        <div v-else class="space-y-3">
                            <div class="flex items-center justify-between">
                                <TooltipProvider :delay-duration="0">
                                    <Tooltip>
                                        <TooltipTrigger class="text-sm text-muted-foreground flex items-center gap-1 cursor-help">
                                            Position <HelpCircle class="h-3 w-3" />
                                        </TooltipTrigger>
                                        <TooltipContent side="left" class="max-w-xs">
                                            <p>How you rank against competitors: beating, matching, or losing to them in AI search visibility.</p>
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                                <span class="font-semibold capitalize" :class="getPositionClass(competitorBenchmark.position)">
                                    {{ competitorBenchmark.position.replace('_', ' ') }}
                                </span>
                            </div>
                            <div v-if="competitorBenchmark.percentile !== null" class="flex items-center justify-between">
                                <TooltipProvider :delay-duration="0">
                                    <Tooltip>
                                        <TooltipTrigger class="text-sm text-muted-foreground flex items-center gap-1 cursor-help">
                                            Beat % of Competitors <HelpCircle class="h-3 w-3" />
                                        </TooltipTrigger>
                                        <TooltipContent side="left" class="max-w-xs">
                                            <p>You outperform {{ competitorBenchmark.percentile }}% of your tracked competitors in GEO score.</p>
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                                <span class="font-semibold">{{ competitorBenchmark.percentile }}%</span>
                            </div>
                            <div v-if="competitorBenchmark.avg_competitor_score !== null" class="flex items-center justify-between">
                                <TooltipProvider :delay-duration="0">
                                    <Tooltip>
                                        <TooltipTrigger class="text-sm text-muted-foreground flex items-center gap-1 cursor-help">
                                            Avg. Competitor Score <HelpCircle class="h-3 w-3" />
                                        </TooltipTrigger>
                                        <TooltipContent side="left" class="max-w-xs">
                                            <p>The average GEO score across all pages you've marked as competitors.</p>
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                                <span class="font-semibold">{{ competitorBenchmark.avg_competitor_score }}</span>
                            </div>
                            <div v-if="competitorBenchmark.score_difference !== null" class="flex items-center justify-between">
                                <TooltipProvider :delay-duration="0">
                                    <Tooltip>
                                        <TooltipTrigger class="text-sm text-muted-foreground flex items-center gap-1 cursor-help">
                                            Score Gap <HelpCircle class="h-3 w-3" />
                                        </TooltipTrigger>
                                        <TooltipContent side="left" class="max-w-xs">
                                            <p>How many points you're ahead (+) or behind (-) the average competitor score. Positive means you're winning!</p>
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                                <span class="font-semibold" :class="competitorBenchmark.score_difference >= 0 ? 'text-green-600' : 'text-red-600'">
                                    {{ competitorBenchmark.score_difference >= 0 ? '+' : '' }}{{ competitorBenchmark.score_difference }}
                                </span>
                            </div>
                            <div v-if="competitorBenchmark.competitors_analyzed" class="flex items-center justify-between">
                                <TooltipProvider :delay-duration="0">
                                    <Tooltip>
                                        <TooltipTrigger class="text-sm text-muted-foreground flex items-center gap-1 cursor-help">
                                            Competitors Analyzed <HelpCircle class="h-3 w-3" />
                                        </TooltipTrigger>
                                        <TooltipContent side="left" class="max-w-xs">
                                            <p>The number of competitor pages included in this benchmark comparison.</p>
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                                <span class="font-semibold">{{ competitorBenchmark.competitors_analyzed }}</span>
                            </div>
                            <p class="text-sm text-muted-foreground pt-2 border-t">
                                {{ competitorBenchmark.comparison }}
                            </p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Parent Scan Notice (for auto-discovered competitor scans) -->
            <Alert v-if="parentScan" class="border-purple-500/50 bg-purple-50 dark:bg-purple-500/10">
                <Users class="h-4 w-4 text-purple-600" />
                <AlertTitle>Auto-Discovered Competitor</AlertTitle>
                <AlertDescription>
                    This competitor was automatically discovered from
                    <Link :href="`/scans/${parentScan.uuid}`" class="font-medium underline hover:no-underline">
                        {{ parentScan.title || parentScan.url }}
                    </Link>
                </AlertDescription>
            </Alert>

            <!-- Discovered Competitors Section -->
            <Collapsible v-if="isCompleted && scan.auto_find_competitors" v-model:open="isCompetitorsExpanded">
                <Card>
                    <CollapsibleTrigger class="w-full text-left">
                        <CardHeader class="cursor-pointer hover:bg-muted/50 transition-colors rounded-t-lg">
                            <div class="flex items-center justify-between">
                                <div>
                                    <CardTitle class="flex items-center gap-2">
                                        <Users class="h-5 w-5 text-purple-600" />
                                        AI-Discovered Competitors
                                        <span class="ml-2 text-sm font-normal text-muted-foreground">
                                            ({{ discoveredCompetitorsList.length }} found)
                                        </span>
                                    </CardTitle>
                                    <CardDescription class="mt-1">
                                        Competitors automatically found and scanned for benchmarking
                                    </CardDescription>
                                </div>
                                <ChevronDown
                                    class="h-5 w-5 text-muted-foreground transition-transform duration-200"
                                    :class="{ 'rotate-180': isCompetitorsExpanded }"
                                />
                            </div>
                        </CardHeader>
                    </CollapsibleTrigger>
                    <CollapsibleContent>
                        <CardContent class="pt-0">
                            <!-- Empty state when no competitors found -->
                            <div v-if="!discoveredCompetitorsList || discoveredCompetitorsList.length === 0" class="text-center py-6">
                                <Users class="h-12 w-12 text-muted-foreground/50 mx-auto mb-3" />
                                <p class="text-muted-foreground mb-2">
                                    <span v-if="competitorDiscoveryStatus === 'discovering'">
                                        Discovering competitors...
                                    </span>
                                    <span v-else-if="competitorDiscoveryStatus === 'failed'">
                                        Failed to discover competitors
                                    </span>
                                    <span v-else>
                                        No competitors were found for this scan
                                    </span>
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    Competitor limit may have been reached, or no suitable competitors were identified.
                                </p>
                            </div>
                            <!-- Competitor list -->
                            <div v-else class="space-y-3">
                                <Link
                                    v-for="competitor in discoveredCompetitorsList"
                                    :key="competitor.uuid"
                                    :href="`/scans/${competitor.uuid}`"
                                    class="flex items-center justify-between rounded-lg border p-4 transition-colors hover:bg-muted/50"
                                >
                                    <div class="flex items-center gap-4">
                                        <div
                                            v-if="competitor.status === 'completed' && competitor.grade"
                                            class="flex h-12 w-12 items-center justify-center rounded-full text-lg font-bold"
                                            :class="getGradeColor(competitor.grade)"
                                        >
                                            {{ competitor.grade }}
                                        </div>
                                        <div
                                            v-else-if="competitor.status === 'failed'"
                                            class="flex h-12 w-12 items-center justify-center rounded-full bg-red-100 text-red-600 dark:bg-red-500/10"
                                        >
                                            <XCircle class="h-6 w-6" />
                                        </div>
                                        <div
                                            v-else
                                            class="flex h-12 w-12 items-center justify-center rounded-full bg-muted"
                                        >
                                            <Loader2 class="h-6 w-6 animate-spin text-muted-foreground" />
                                        </div>
                                        <div class="flex-1">
                                            <p class="font-medium text-base">{{ competitor.title || 'Scanning...' }}</p>
                                            <p class="text-sm text-muted-foreground truncate max-w-[400px]">{{ competitor.url }}</p>
                                            <div class="flex items-center gap-2 mt-1">
                                                <span
                                                    v-if="competitor.status === 'completed'"
                                                    class="text-xs px-2 py-0.5 rounded-full bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300"
                                                >
                                                    Completed
                                                </span>
                                                <span
                                                    v-else-if="competitor.status === 'failed'"
                                                    class="text-xs px-2 py-0.5 rounded-full bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300"
                                                >
                                                    Failed to scan
                                                </span>
                                                <span
                                                    v-else-if="competitor.status === 'processing'"
                                                    class="text-xs px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300"
                                                >
                                                    Scanning...
                                                </span>
                                                <span
                                                    v-else
                                                    class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300"
                                                >
                                                    {{ competitor.status }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-4">
                                        <div v-if="competitor.status === 'completed' && competitor.score !== null" class="text-right">
                                            <p class="text-2xl font-bold" :class="getScoreColor(competitor.score)">
                                                {{ competitor.score.toFixed(1) }}
                                            </p>
                                            <p class="text-xs text-muted-foreground">GEO Score</p>
                                        </div>
                                        <ChevronRight class="h-5 w-5 text-muted-foreground" />
                                    </div>
                                </Link>
                            </div>
                        </CardContent>
                    </CollapsibleContent>
                </Card>
            </Collapsible>

            <!-- Competitor Discovery Progress -->
            <Card v-if="scan.auto_find_competitors && competitorDiscoveryStatus && competitorDiscoveryStatus !== 'completed'">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Sparkles class="h-5 w-5 text-primary animate-pulse" />
                        <span v-if="competitorDiscoveryStatus === 'pending'">Competitor Discovery Queued</span>
                        <span v-else-if="competitorDiscoveryStatus === 'discovering'">Discovering Competitors...</span>
                        <span v-else-if="competitorDiscoveryStatus === 'scanning'">Scanning Competitors...</span>
                        <span v-else-if="competitorDiscoveryStatus === 'failed'">Competitor Discovery Failed</span>
                    </CardTitle>
                    <CardDescription>
                        <span v-if="competitorDiscoveryStatus === 'pending'">
                            Competitor discovery will begin after your scan completes
                        </span>
                        <span v-else-if="competitorDiscoveryStatus === 'discovering'">
                            AI is analyzing your content to find competitor websites
                        </span>
                        <span v-else-if="competitorDiscoveryStatus === 'scanning'">
                            Found {{ competitorsFound }} competitors - scanning in progress
                        </span>
                        <span v-else-if="competitorDiscoveryStatus === 'failed'">
                            There was an error discovering competitors. You will not be charged for this feature.
                        </span>
                    </CardDescription>
                </CardHeader>
                <CardContent v-if="competitorDiscoveryStatus !== 'failed'">
                    <div class="space-y-3">
                        <!-- Progress steps -->
                        <div class="flex items-center gap-4">
                            <div class="flex items-center gap-2">
                                <div
                                    class="flex h-8 w-8 items-center justify-center rounded-full text-sm font-medium"
                                    :class="competitorDiscoveryStatus === 'pending' ? 'bg-muted text-muted-foreground' : 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300'"
                                >
                                    <CheckCircle2 v-if="competitorDiscoveryStatus !== 'pending'" class="h-4 w-4" />
                                    <span v-else>1</span>
                                </div>
                                <span class="text-sm" :class="competitorDiscoveryStatus === 'pending' ? 'text-muted-foreground' : ''">Scan Complete</span>
                            </div>
                            <div class="h-px flex-1 bg-border" />
                            <div class="flex items-center gap-2">
                                <div
                                    class="flex h-8 w-8 items-center justify-center rounded-full text-sm font-medium"
                                    :class="{
                                        'bg-muted text-muted-foreground': competitorDiscoveryStatus === 'pending',
                                        'bg-primary text-primary-foreground': competitorDiscoveryStatus === 'discovering',
                                        'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300': competitorDiscoveryStatus === 'scanning'
                                    }"
                                >
                                    <Loader2 v-if="competitorDiscoveryStatus === 'discovering'" class="h-4 w-4 animate-spin" />
                                    <CheckCircle2 v-else-if="competitorDiscoveryStatus === 'scanning'" class="h-4 w-4" />
                                    <span v-else>2</span>
                                </div>
                                <span class="text-sm" :class="competitorDiscoveryStatus === 'pending' ? 'text-muted-foreground' : ''">AI Discovery</span>
                            </div>
                            <div class="h-px flex-1 bg-border" />
                            <div class="flex items-center gap-2">
                                <div
                                    class="flex h-8 w-8 items-center justify-center rounded-full text-sm font-medium"
                                    :class="competitorDiscoveryStatus === 'scanning' ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground'"
                                >
                                    <Loader2 v-if="competitorDiscoveryStatus === 'scanning'" class="h-4 w-4 animate-spin" />
                                    <span v-else>3</span>
                                </div>
                                <span class="text-sm" :class="competitorDiscoveryStatus !== 'scanning' ? 'text-muted-foreground' : ''">
                                    Scanning{{ competitorsFound > 0 ? ` (${competitorsFound})` : '' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- AI Suggestions Section (only show when completed and has suggestions) -->
            <Collapsible v-if="isCompleted && aiSuggestions.length > 0" v-model:open="isSuggestionsExpanded">
                <Card>
                    <CollapsibleTrigger class="w-full text-left">
                        <CardHeader class="cursor-pointer hover:bg-muted/50 transition-colors rounded-t-lg">
                            <div class="flex items-center justify-between">
                                <div>
                                    <CardTitle class="flex items-center gap-2">
                                        <Lightbulb class="h-5 w-5 text-yellow-500" />
                                        AI-Powered Suggestions
                                        <span class="ml-2 text-sm font-normal text-muted-foreground">
                                            ({{ aiSuggestions.length }} suggestions)
                                        </span>
                                    </CardTitle>
                                    <CardDescription class="mt-1">
                                        Personalized recommendations based on analysis of your content and similar high-performing pages
                                    </CardDescription>
                                </div>
                                <ChevronDown
                                    class="h-5 w-5 text-muted-foreground transition-transform duration-200"
                                    :class="{ 'rotate-180': isSuggestionsExpanded }"
                                />
                            </div>
                        </CardHeader>
                    </CollapsibleTrigger>
                    <CollapsibleContent>
                        <CardContent class="pt-0">
                            <div class="space-y-3">
                                <div
                                    v-for="(suggestion, index) in aiSuggestions"
                                    :key="index"
                                    class="rounded-lg border p-4"
                                >
                                    <div class="flex items-start gap-3">
                                        <Target class="h-5 w-5 text-primary mt-0.5 shrink-0" />
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2 mb-2">
                                                <span
                                                    v-if="suggestion.priority"
                                                    class="text-xs font-medium px-2 py-0.5 rounded-full"
                                                    :class="getPriorityBadgeClass(suggestion.priority)"
                                                >
                                                    {{ suggestion.priority }}
                                                </span>
                                                <span
                                                    v-if="suggestion.category"
                                                    class="text-xs text-muted-foreground capitalize"
                                                >
                                                    {{ suggestion.category?.replace('_', ' ') }}
                                                </span>
                                            </div>
                                            <p class="text-sm font-medium leading-relaxed">{{ suggestion.suggestion }}</p>
                                            <div v-if="suggestion.example" class="mt-3 bg-muted/50 p-3 rounded-lg border">
                                                <p class="text-xs font-medium text-muted-foreground mb-1">Example:</p>
                                                <p class="text-sm font-mono">{{ suggestion.example }}</p>
                                            </div>
                                            <p v-if="suggestion.reasoning" class="text-sm text-muted-foreground mt-3 italic border-l-2 border-primary/30 pl-3">
                                                {{ suggestion.reasoning }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </CollapsibleContent>
                </Card>
            </Collapsible>

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
                    <Alert v-if="emailSuccess" class="border-green-500 bg-green-50 dark:bg-green-500/10">
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
                        :class="item.achieved ? 'border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-500/10' : 'border-orange-200 bg-orange-50 dark:border-orange-800 dark:bg-orange-500/10'"
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

        <!-- Rescan Modal -->
        <Dialog v-model:open="showRescanModal">
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <RefreshCw class="h-5 w-5" />
                        Rescan Page
                    </DialogTitle>
                    <DialogDescription>
                        Choose the scan tier for your rescan. Higher tiers analyze more pillars but cost tokens.
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-3 py-4">
                    <!-- Tier Selection -->
                    <div
                        v-for="option in tierOptions"
                        :key="option.value"
                        class="relative rounded-lg border p-4 cursor-pointer transition-all"
                        :class="{
                            'border-primary bg-primary/5 ring-2 ring-primary': selectedRescanTier === option.value,
                            'border-muted hover:border-primary/50': selectedRescanTier !== option.value,
                            'opacity-50 cursor-not-allowed': isTierOnCooldown(option.value) || !canAffordTier(option.value),
                        }"
                        @click="!isTierOnCooldown(option.value) && canAffordTier(option.value) && (selectedRescanTier = option.value)"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="font-semibold">{{ option.label }}</span>
                                    <span
                                        v-if="option.value !== 'basic'"
                                        class="inline-flex items-center gap-1 rounded-full bg-primary/10 px-2 py-0.5 text-xs font-medium text-primary"
                                    >
                                        <Coins class="h-3 w-3" />
                                        {{ option.tokens }} tokens
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700 dark:bg-green-900 dark:text-green-300"
                                    >
                                        Free
                                    </span>
                                </div>
                                <p class="text-sm text-muted-foreground mt-1">
                                    {{ option.description }} · {{ option.pillars }} pillars analyzed
                                </p>
                                <!-- Cooldown or affordability warning -->
                                <p
                                    v-if="isTierOnCooldown(option.value)"
                                    class="text-xs text-orange-600 dark:text-orange-400 mt-2 flex items-center gap-1"
                                >
                                    <Clock class="h-3 w-3" />
                                    Available in {{ getTierCooldownMinutes(option.value) }} {{ getTierCooldownMinutes(option.value) === 1 ? 'minute' : 'minutes' }}
                                </p>
                                <p
                                    v-else-if="!canAffordTier(option.value)"
                                    class="text-xs text-red-600 dark:text-red-400 mt-2 flex items-center gap-1"
                                >
                                    <Coins class="h-3 w-3" />
                                    Insufficient tokens ({{ tokenBalance }} available)
                                </p>
                                <p
                                    v-else
                                    class="text-xs text-green-600 dark:text-green-400 mt-2 flex items-center gap-1"
                                >
                                    <CheckCircle2 class="h-3 w-3" />
                                    Available now
                                </p>
                            </div>
                            <div
                                class="flex h-5 w-5 items-center justify-center rounded-full border-2 transition-colors"
                                :class="selectedRescanTier === option.value ? 'border-primary bg-primary' : 'border-muted'"
                            >
                                <div
                                    v-if="selectedRescanTier === option.value"
                                    class="h-2 w-2 rounded-full bg-white"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Error message -->
                    <Alert v-if="rescanError" variant="destructive">
                        <AlertCircle class="h-4 w-4" />
                        <AlertDescription>{{ rescanError }}</AlertDescription>
                    </Alert>

                    <!-- Token balance reminder -->
                    <div v-if="tokenBalance > 0" class="flex items-center justify-between rounded-lg bg-muted/50 px-3 py-2 text-sm">
                        <span class="text-muted-foreground">Your token balance</span>
                        <span class="font-semibold flex items-center gap-1">
                            <Coins class="h-4 w-4 text-primary" />
                            {{ tokenBalance }} tokens
                        </span>
                    </div>
                </div>

                <DialogFooter class="gap-2 sm:gap-0">
                    <Button variant="outline" @click="showRescanModal = false" :disabled="rescanning">
                        Cancel
                    </Button>
                    <Button
                        @click="rescan(selectedRescanTier)"
                        :disabled="rescanning || isTierOnCooldown(selectedRescanTier) || !canAffordTier(selectedRescanTier)"
                    >
                        <Loader2 v-if="rescanning" class="mr-2 h-4 w-4 animate-spin" />
                        <RefreshCw v-else class="mr-2 h-4 w-4" />
                        {{ rescanning ? 'Rescanning...' : 'Start Rescan' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </component>
</template>
