<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Plus, Search, Clock, CheckCircle2, XCircle, Loader2, Coins } from 'lucide-vue-next';

import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { useDateFormat } from '@/composables/useDateFormat';
import { type BreadcrumbItem } from '@/types';

const { formatRelative } = useDateFormat();

interface CitationResearch {
    id: number;
    uuid: string;
    topic: string;
    tier: string;
    status: string;
    progress_step: string | null;
    progress_percent: number;
    sources_found: Array<{ url: string; citation_count: number }> | null;
    created_at: string;
    completed_at: string | null;
}

interface Usage {
    can_access: boolean;
    can_afford_research: boolean;
    can_afford_audit: boolean;
    research_this_month: number;
    total_completed: number;
    token_balance: number;
    costs: {
        research: number;
        research_audit: number;
    };
}

interface Props {
    research: CitationResearch[];
    usage: Usage;
    currentTeam: { id: number; name: string } | null;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Citation Tracking', href: '/citations' },
    { title: 'Research', href: '/citations/research' },
];

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

const getTierLabel = (tier: string) => {
    return tier === 'research_audit' ? 'Research + Audit' : 'Research';
};
</script>

<template>
    <Head title="Citation Research" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Citation Research</h1>
                    <p class="text-muted-foreground">
                        Discover what content gets cited by AI platforms for any topic.
                    </p>
                </div>
                <Link href="/citations/research/create">
                    <Button :disabled="!usage.can_afford_research">
                        <Plus class="mr-2 h-4 w-4" />
                        New Research
                    </Button>
                </Link>
            </div>

            <!-- Stats -->
            <div class="grid gap-4 md:grid-cols-3">
                <Card>
                    <CardHeader class="pb-2">
                        <CardDescription>Token Balance</CardDescription>
                        <CardTitle class="flex items-center gap-2 text-2xl">
                            <Coins class="h-5 w-5 text-primary" />
                            {{ usage.token_balance }}
                        </CardTitle>
                    </CardHeader>
                </Card>
                <Card>
                    <CardHeader class="pb-2">
                        <CardDescription>Research This Month</CardDescription>
                        <CardTitle class="text-2xl">{{ usage.research_this_month }}</CardTitle>
                    </CardHeader>
                </Card>
                <Card>
                    <CardHeader class="pb-2">
                        <CardDescription>Total Completed</CardDescription>
                        <CardTitle class="text-2xl">{{ usage.total_completed }}</CardTitle>
                    </CardHeader>
                </Card>
            </div>

            <!-- Research List -->
            <Card>
                <CardHeader>
                    <CardTitle>Your Research</CardTitle>
                    <CardDescription>
                        View past citation research and their results.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="research.length === 0" class="text-center py-12">
                        <Search class="mx-auto h-12 w-12 text-muted-foreground/50" />
                        <h3 class="mt-4 text-lg font-semibold">No research yet</h3>
                        <p class="text-muted-foreground mt-2">
                            Start your first citation research to discover what AI platforms cite.
                        </p>
                        <Link href="/citations/research/create" class="mt-4 inline-block">
                            <Button>
                                <Plus class="mr-2 h-4 w-4" />
                                New Research
                            </Button>
                        </Link>
                    </div>

                    <div v-else class="space-y-4">
                        <Link
                            v-for="item in research"
                            :key="item.uuid"
                            :href="`/citations/research/${item.uuid}`"
                            class="block"
                        >
                            <div class="flex items-center justify-between p-4 rounded-lg border hover:bg-muted/50 transition-colors">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <component
                                            :is="getStatusIcon(item.status)"
                                            class="h-4 w-4 flex-shrink-0"
                                            :class="[
                                                getStatusColor(item.status),
                                                item.status === 'processing' ? 'animate-spin' : ''
                                            ]"
                                        />
                                        <span class="font-medium truncate">{{ item.topic }}</span>
                                    </div>
                                    <div class="mt-1 flex items-center gap-3 text-sm text-muted-foreground">
                                        <Badge variant="outline">{{ getTierLabel(item.tier) }}</Badge>
                                        <span>{{ formatRelative(item.created_at) }}</span>
                                        <span v-if="item.sources_found">
                                            {{ item.sources_found.length }} sources found
                                        </span>
                                    </div>
                                    <div v-if="item.status === 'processing'" class="mt-2">
                                        <div class="flex items-center gap-2 text-sm text-muted-foreground">
                                            <span>{{ item.progress_step }}</span>
                                            <span>({{ item.progress_percent }}%)</span>
                                        </div>
                                        <div class="mt-1 h-1.5 w-full bg-muted rounded-full overflow-hidden">
                                            <div
                                                class="h-full bg-primary transition-all duration-500"
                                                :style="{ width: `${item.progress_percent}%` }"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </Link>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
