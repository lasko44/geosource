<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { CheckCircle, Coins, ArrowRight } from 'lucide-vue-next';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

interface Props {
    balance: number;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Tokens',
        href: '/tokens',
    },
    {
        title: 'Purchase Complete',
        href: '/tokens/success',
    },
];
</script>

<template>
    <Head title="Purchase Complete" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex min-h-[60vh] items-center justify-center p-6">
            <Card class="mx-auto w-full max-w-md text-center">
                <CardHeader class="pb-4">
                    <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/30">
                        <CheckCircle class="h-10 w-10 text-green-600 dark:text-green-400" />
                    </div>
                    <CardTitle class="text-2xl">Purchase Complete!</CardTitle>
                    <CardDescription>
                        Your tokens have been added to your account.
                    </CardDescription>
                </CardHeader>

                <CardContent class="space-y-6">
                    <div class="rounded-lg bg-muted p-4">
                        <div class="flex items-center justify-center gap-2">
                            <Coins class="h-6 w-6 text-primary" />
                            <span class="text-2xl font-bold">{{ balance }}</span>
                        </div>
                        <p class="mt-1 text-sm text-muted-foreground">Your current balance</p>
                    </div>

                    <div class="space-y-3">
                        <Button as-child class="w-full">
                            <Link href="/dashboard">
                                Start Scanning
                                <ArrowRight class="ml-2 h-4 w-4" />
                            </Link>
                        </Button>

                        <Button as-child variant="outline" class="w-full">
                            <Link href="/tokens/history">
                                View Transaction History
                            </Link>
                        </Button>
                    </div>

                    <p class="text-xs text-muted-foreground">
                        A receipt has been sent to your email address.
                    </p>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
