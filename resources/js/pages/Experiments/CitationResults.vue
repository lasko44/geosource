<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import {
    CheckCircle,
    XCircle,
    Loader2,
    AlertCircle,
    ChevronDown,
    Globe,
    Search,
    ArrowRight,
    Sparkles,
} from 'lucide-vue-next';
import { register } from '@/routes';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import ThemeSwitcher from '@/components/ThemeSwitcher.vue';

interface Check {
    id: number;
    platform: string;
    status: 'pending' | 'processing' | 'completed' | 'failed';
    is_cited: boolean | null;
    ai_response: string | null;
    citations: any[] | null;
    error_message: string | null;
}

interface Props {
    query: { uuid: string; query: string; domain: string };
    checks: Check[];
    remainingChecks: number;
    statusUrl: string;
}

const props = defineProps<Props>();

const localChecks = ref<Check[]>([...props.checks]);
let pollInterval: ReturnType<typeof setInterval> | null = null;

const platformMeta: Record<string, { name: string; color: string; dotClass: string; bgClass: string; borderClass: string }> = {
    perplexity: {
        name: 'Perplexity',
        color: 'teal',
        dotClass: 'bg-teal-500',
        bgClass: 'bg-teal-500/10',
        borderClass: 'border-teal-500/20',
    },
    openai: {
        name: 'ChatGPT',
        color: 'emerald',
        dotClass: 'bg-emerald-500',
        bgClass: 'bg-emerald-500/10',
        borderClass: 'border-emerald-500/20',
    },
    claude: {
        name: 'Claude',
        color: 'amber',
        dotClass: 'bg-amber-500',
        bgClass: 'bg-amber-500/10',
        borderClass: 'border-amber-500/20',
    },
};

const allComplete = computed(() =>
    localChecks.value.every(c => c.status === 'completed' || c.status === 'failed'),
);

const citedCount = computed(() =>
    localChecks.value.filter(c => c.status === 'completed' && c.is_cited).length,
);

const pollStatus = async () => {
    try {
        const response = await fetch(props.statusUrl);
        const data = await response.json();

        if (data.checks) {
            localChecks.value = data.checks;
        }

        if (data.all_complete && pollInterval) {
            clearInterval(pollInterval);
            pollInterval = null;
        }
    } catch {
        // Silent fail, retry next interval
    }
};

onMounted(() => {
    if (!allComplete.value) {
        pollInterval = setInterval(pollStatus, 2000);
    }
});

onUnmounted(() => {
    if (pollInterval) clearInterval(pollInterval);
});

const expandedCards = ref<Set<number>>(new Set());

const toggleCard = (id: number) => {
    if (expandedCards.value.has(id)) {
        expandedCards.value.delete(id);
    } else {
        expandedCards.value.add(id);
    }
};

const truncateResponse = (text: string | null, maxLength = 200): string => {
    if (!text) return '';
    return text.length > maxLength ? text.substring(0, maxLength) + '...' : text;
};
</script>

<template>
    <Head :title="`Citation Check: ${query.domain}`" />

    <div class="min-h-screen bg-background text-foreground">
        <!-- Top banner -->
        <div class="border-b bg-primary/5 px-4 py-3">
            <div class="mx-auto flex max-w-5xl items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link href="/" class="flex items-center gap-2">
                        <Globe class="h-5 w-5 text-primary" />
                        <span class="text-sm font-bold">GeoSource<span class="text-primary">.ai</span></span>
                    </Link>
                    <span class="text-sm text-muted-foreground">
                        <template v-if="remainingChecks > 0">{{ remainingChecks }} free check{{ remainingChecks !== 1 ? 's' : '' }} remaining</template>
                        <template v-else>No free checks remaining</template>
                    </span>
                </div>
                <div class="flex items-center gap-2">
                    <ThemeSwitcher />
                    <Link :href="register()">
                        <Button size="sm">Sign Up Free</Button>
                    </Link>
                </div>
            </div>
        </div>

        <main class="mx-auto max-w-5xl px-4 py-10 sm:py-14">
            <!-- Query header -->
            <div class="mb-8">
                <div class="flex items-center gap-2 text-sm text-muted-foreground">
                    <Search class="h-4 w-4" />
                    <span class="font-mono">{{ query.query }}</span>
                </div>
                <h1 class="mt-2 text-2xl font-bold tracking-tight sm:text-3xl">
                    Is <span class="text-primary">{{ query.domain }}</span> cited by AI?
                </h1>
                <p class="mt-2 text-muted-foreground">
                    Checking 3 major AI platforms to see if they reference your domain when answering this query.
                </p>
            </div>

            <!-- Status summary -->
            <div v-if="allComplete" class="mb-8 rounded-xl border p-5" :class="citedCount > 0 ? 'border-emerald-500/30 bg-emerald-500/5' : 'border-amber-500/30 bg-amber-500/5'">
                <div class="flex items-start gap-3">
                    <CheckCircle v-if="citedCount > 0" class="mt-0.5 h-5 w-5 shrink-0 text-emerald-500" />
                    <AlertCircle v-else class="mt-0.5 h-5 w-5 shrink-0 text-amber-500" />
                    <div>
                        <p class="font-semibold">
                            <template v-if="citedCount > 0">
                                Cited by {{ citedCount }} of 3 platforms
                            </template>
                            <template v-else>
                                Not currently cited by any platform
                            </template>
                        </p>
                        <p class="mt-1 text-sm text-muted-foreground">
                            <template v-if="citedCount > 0">
                                AI platforms are referencing your domain for this query. Sign up to track changes and monitor more queries.
                            </template>
                            <template v-else>
                                Your domain wasn't found in AI responses for this query. Sign up to get recommendations on how to improve your AI visibility.
                            </template>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Platform cards -->
            <div class="grid gap-4 sm:grid-cols-3">
                <Card
                    v-for="check in localChecks"
                    :key="check.id"
                    class="relative overflow-hidden transition-all duration-300"
                    :class="check.status === 'completed' && check.is_cited ? 'ring-1 ring-emerald-500/30' : ''"
                >
                    <!-- Scanning animation bar -->
                    <div
                        v-if="check.status === 'pending' || check.status === 'processing'"
                        class="absolute inset-x-0 top-0 h-0.5 overflow-hidden bg-muted"
                    >
                        <div class="h-full w-1/3 animate-[scan_1.5s_ease-in-out_infinite] rounded-full bg-primary" />
                    </div>

                    <CardHeader class="pb-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <span class="h-2.5 w-2.5 rounded-full" :class="platformMeta[check.platform]?.dotClass ?? 'bg-muted-foreground'" />
                                <CardTitle class="text-base">{{ platformMeta[check.platform]?.name ?? check.platform }}</CardTitle>
                            </div>

                            <!-- Status indicator -->
                            <div>
                                <Badge v-if="check.status === 'pending' || check.status === 'processing'" variant="secondary" class="gap-1.5">
                                    <Loader2 class="h-3 w-3 animate-spin" />
                                    Checking
                                </Badge>
                                <Badge v-else-if="check.status === 'completed' && check.is_cited" class="gap-1.5 bg-emerald-500/10 text-emerald-700 dark:text-emerald-400">
                                    <CheckCircle class="h-3 w-3" />
                                    Cited
                                </Badge>
                                <Badge v-else-if="check.status === 'completed' && !check.is_cited" variant="secondary" class="gap-1.5">
                                    <XCircle class="h-3 w-3" />
                                    Not cited
                                </Badge>
                                <Badge v-else-if="check.status === 'failed'" variant="destructive" class="gap-1.5">
                                    <AlertCircle class="h-3 w-3" />
                                    Failed
                                </Badge>
                            </div>
                        </div>
                    </CardHeader>

                    <CardContent>
                        <!-- Loading state -->
                        <div v-if="check.status === 'pending' || check.status === 'processing'" class="space-y-2">
                            <div class="h-3 w-full animate-pulse rounded bg-muted" />
                            <div class="h-3 w-4/5 animate-pulse rounded bg-muted" />
                            <div class="h-3 w-3/5 animate-pulse rounded bg-muted" />
                        </div>

                        <!-- Completed state -->
                        <div v-else-if="check.status === 'completed'">
                            <button
                                class="w-full text-left"
                                @click="toggleCard(check.id)"
                            >
                                <p class="text-sm leading-relaxed text-muted-foreground">
                                    {{ expandedCards.has(check.id) ? check.ai_response : truncateResponse(check.ai_response) }}
                                </p>
                                <div class="mt-3 flex items-center justify-between">
                                    <p v-if="check.citations && check.citations.length > 0" class="text-xs text-muted-foreground">
                                        {{ check.citations.length }} citation{{ check.citations.length !== 1 ? 's' : '' }} found
                                    </p>
                                    <span v-if="check.ai_response && check.ai_response.length > 200" class="flex items-center gap-1 text-xs font-medium text-primary">
                                        {{ expandedCards.has(check.id) ? 'Show less' : 'Read full response' }}
                                        <ChevronDown class="h-3 w-3 transition-transform" :class="expandedCards.has(check.id) ? 'rotate-180' : ''" />
                                    </span>
                                </div>
                            </button>
                        </div>

                        <!-- Failed state -->
                        <div v-else-if="check.status === 'failed'">
                            <p class="text-sm text-muted-foreground">
                                {{ check.error_message || 'Check failed. This can happen with rate limits or API issues.' }}
                            </p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- CTA section -->
            <div class="mt-12 rounded-2xl border bg-card p-8 text-center sm:p-10">
                <Sparkles class="mx-auto h-8 w-8 text-primary" />
                <h2 class="mt-4 text-xl font-bold sm:text-2xl">Track your AI citations over time</h2>
                <p class="mx-auto mt-3 max-w-lg text-muted-foreground">
                    Monitor 8 AI platforms, get alerts when citations change, and discover what content gets you cited. Free account includes 20 tokens to get started.
                </p>
                <div class="mt-6 flex flex-col items-center gap-3">
                    <Link :href="register()">
                        <Button size="lg" class="gap-2 px-8">
                            Start Tracking Free
                            <ArrowRight class="h-4 w-4" />
                        </Button>
                    </Link>
                    <span class="text-sm text-muted-foreground">No credit card required</span>
                </div>
            </div>
        </main>
    </div>
</template>

<style>
@keyframes scan {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(400%); }
}
</style>
