<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Globe, TrendingUp, Target, Calendar, ExternalLink, Zap, ArrowRight, Users, Crown, Plus } from 'lucide-vue-next';

import AppLayout from '@/layouts/AppLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Progress } from '@/components/ui/progress';
import { type BreadcrumbItem, type Scan, type DashboardStats, type UsageSummary, type PlanWithLimits } from '@/types';

interface Team {
    id: number;
    name: string;
    slug: string;
    is_owner: boolean;
    members_count: number;
    role: string;
}

interface Props {
    recentScans: Scan[];
    stats: DashboardStats;
    usage: UsageSummary;
    showUpgradePrompt: boolean;
    plans: Record<string, PlanWithLimits>;
    teams: Team[] | null;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

const form = useForm({
    url: '',
});

const submit = () => {
    form.post('/scan', {
        preserveScroll: true,
    });
};

const getGradeColor = (grade: string) => {
    if (grade.startsWith('A')) return 'text-green-600 bg-green-100 dark:text-green-400 dark:bg-green-950';
    if (grade.startsWith('B')) return 'text-blue-600 bg-blue-100 dark:text-blue-400 dark:bg-blue-950';
    if (grade.startsWith('C')) return 'text-yellow-600 bg-yellow-100 dark:text-yellow-400 dark:bg-yellow-950';
    if (grade.startsWith('D')) return 'text-orange-600 bg-orange-100 dark:text-orange-400 dark:bg-orange-950';
    return 'text-red-600 bg-red-100 dark:text-red-400 dark:bg-red-950';
};

const getScoreColor = (score: number) => {
    if (score >= 80) return 'text-green-600 dark:text-green-400';
    if (score >= 60) return 'text-blue-600 dark:text-blue-400';
    if (score >= 40) return 'text-yellow-600 dark:text-yellow-400';
    return 'text-red-600 dark:text-red-400';
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
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
            <!-- Upgrade Banner -->
            <Alert v-if="showUpgradePrompt && usage.plan_key === 'free'" class="border-primary/50 bg-gradient-to-r from-primary/5 to-primary/10">
                <Zap class="h-4 w-4 text-primary" />
                <AlertDescription class="flex items-center justify-between">
                    <span>
                        <strong>Unlock more scans!</strong> Upgrade to Pro for 50 scans/month, full GEO breakdown, and CSV export.
                    </span>
                    <Link href="/billing/plans">
                        <Button size="sm" class="ml-4">
                            View Plans
                            <ArrowRight class="ml-1 h-4 w-4" />
                        </Button>
                    </Link>
                </AlertDescription>
            </Alert>

            <!-- Scan Form Card -->
            <Card class="border-2 border-dashed border-primary/20 bg-gradient-to-br from-primary/5 to-transparent">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Globe class="h-5 w-5 text-primary" />
                        Start a GEO Scan
                    </CardTitle>
                    <CardDescription>
                        Enter a URL to analyze its Generative Engine Optimization score
                    </CardDescription>
                </CardHeader>
                <CardContent>
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
                            :disabled="form.processing || !form.url || !usage.can_scan"
                            class="h-12 px-8"
                        >
                            <svg v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                            </svg>
                            {{ form.processing ? 'Scanning...' : 'Scan URL' }}
                        </Button>
                    </form>
                    <Alert v-if="form.errors.url" variant="destructive" class="mt-3">
                        <AlertDescription>{{ form.errors.url }}</AlertDescription>
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
                            :href="`/scans/${scan.uuid}`"
                            class="flex items-center justify-between rounded-lg border p-4 transition-colors hover:bg-muted/50"
                        >
                            <div class="flex items-center gap-4">
                                <!-- Score Badge -->
                                <div
                                    class="flex h-12 w-12 items-center justify-center rounded-full text-lg font-bold"
                                    :class="getGradeColor(scan.grade)"
                                >
                                    {{ scan.grade }}
                                </div>

                                <!-- Info -->
                                <div>
                                    <p class="font-medium">{{ scan.title || 'Untitled' }}</p>
                                    <p class="flex items-center gap-1 text-sm text-muted-foreground">
                                        <ExternalLink class="h-3 w-3" />
                                        {{ truncateUrl(scan.url) }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-4">
                                <!-- Score -->
                                <div class="text-right">
                                    <p class="text-2xl font-bold" :class="getScoreColor(scan.score)">
                                        {{ scan.score.toFixed(1) }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">/ 100</p>
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
                        <Link href="/teams/create">
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
                        <p class="mt-2 text-sm text-muted-foreground">
                            Create a team to start collaborating with others
                        </p>
                        <Link href="/teams/create" class="mt-4 inline-block">
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
