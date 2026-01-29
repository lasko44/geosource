<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Coins, ArrowUpRight, ArrowDownLeft, Gift, RotateCcw, TrendingUp, TrendingDown } from 'lucide-vue-next';
import { computed } from 'vue';

import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

interface Transaction {
    uuid: string;
    type: 'purchase' | 'spend' | 'refund' | 'bonus';
    amount: number;
    formatted_amount: string;
    balance_after: number;
    description: string;
    created_at: string;
}

interface UsageStats {
    current_balance: number;
    spent_this_month: number;
    transactions_this_month: number;
    total_credited: number;
    total_spent: number;
}

interface Props {
    transactions: Transaction[];
    balance: number;
    stats: UsageStats;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Tokens',
        href: '/tokens',
    },
    {
        title: 'History',
        href: '/tokens/history',
    },
];

const getTypeIcon = (type: string) => {
    switch (type) {
        case 'purchase':
            return ArrowDownLeft;
        case 'spend':
            return ArrowUpRight;
        case 'refund':
            return RotateCcw;
        case 'bonus':
            return Gift;
        default:
            return Coins;
    }
};

const getTypeColor = (type: string) => {
    switch (type) {
        case 'purchase':
        case 'refund':
        case 'bonus':
            return 'text-green-600 dark:text-green-400 bg-green-100 dark:bg-green-900/30';
        case 'spend':
            return 'text-red-600 dark:text-red-400 bg-red-100 dark:bg-red-900/30';
        default:
            return 'text-gray-600 dark:text-gray-400 bg-gray-100 dark:bg-gray-900/30';
    }
};

const getTypeBadgeVariant = (type: string): 'default' | 'secondary' | 'destructive' | 'outline' => {
    switch (type) {
        case 'purchase':
            return 'default';
        case 'spend':
            return 'destructive';
        case 'refund':
        case 'bonus':
            return 'secondary';
        default:
            return 'outline';
    }
};

const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    });
};

const formatType = (type: string) => {
    return type.charAt(0).toUpperCase() + type.slice(1);
};
</script>

<template>
    <Head title="Token History" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-foreground">Token History</h1>
                    <p class="text-muted-foreground">View your token transactions and usage statistics.</p>
                </div>
                <Link href="/tokens">
                    <Badge variant="outline" class="gap-2 px-4 py-2">
                        <Coins class="h-4 w-4" />
                        {{ balance }} tokens available
                    </Badge>
                </Link>
            </div>

            <!-- Stats Cards -->
            <div class="grid gap-4 md:grid-cols-4">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Current Balance</CardTitle>
                        <Coins class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.current_balance }}</div>
                        <p class="text-xs text-muted-foreground">tokens available</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Spent This Month</CardTitle>
                        <TrendingDown class="h-4 w-4 text-red-500" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.spent_this_month }}</div>
                        <p class="text-xs text-muted-foreground">{{ stats.transactions_this_month }} transactions</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total Purchased</CardTitle>
                        <TrendingUp class="h-4 w-4 text-green-500" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.total_credited }}</div>
                        <p class="text-xs text-muted-foreground">all time</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total Used</CardTitle>
                        <ArrowUpRight class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.total_spent }}</div>
                        <p class="text-xs text-muted-foreground">all time</p>
                    </CardContent>
                </Card>
            </div>

            <!-- Transactions Table -->
            <Card>
                <CardHeader>
                    <CardTitle>Recent Transactions</CardTitle>
                    <CardDescription>Your token purchase and usage history.</CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="transactions.length === 0" class="py-8 text-center">
                        <Coins class="mx-auto h-12 w-12 text-muted-foreground/50" />
                        <p class="mt-4 text-muted-foreground">No transactions yet.</p>
                        <Link href="/tokens" class="mt-2 inline-block text-sm text-primary hover:underline">
                            Buy your first tokens
                        </Link>
                    </div>

                    <Table v-else>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Type</TableHead>
                                <TableHead>Description</TableHead>
                                <TableHead class="text-right">Amount</TableHead>
                                <TableHead class="text-right">Balance</TableHead>
                                <TableHead class="text-right">Date</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="transaction in transactions" :key="transaction.uuid">
                                <TableCell>
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="flex h-8 w-8 items-center justify-center rounded-full"
                                            :class="getTypeColor(transaction.type)"
                                        >
                                            <component :is="getTypeIcon(transaction.type)" class="h-4 w-4" />
                                        </div>
                                        <Badge :variant="getTypeBadgeVariant(transaction.type)">
                                            {{ formatType(transaction.type) }}
                                        </Badge>
                                    </div>
                                </TableCell>
                                <TableCell class="max-w-[200px] truncate">
                                    {{ transaction.description }}
                                </TableCell>
                                <TableCell class="text-right font-medium"
                                    :class="{
                                        'text-green-600 dark:text-green-400': transaction.amount > 0,
                                        'text-red-600 dark:text-red-400': transaction.amount < 0,
                                    }"
                                >
                                    {{ transaction.formatted_amount }}
                                </TableCell>
                                <TableCell class="text-right text-muted-foreground">
                                    {{ transaction.balance_after }}
                                </TableCell>
                                <TableCell class="text-right text-sm text-muted-foreground">
                                    {{ formatDate(transaction.created_at) }}
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
