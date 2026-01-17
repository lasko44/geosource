<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

import HeadingSmall from '@/components/HeadingSmall.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

interface Invoice {
    id: string;
    date: string;
    total: string;
    status: string;
}

interface Props {
    invoices: Invoice[];
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Billing',
        href: '/billing',
    },
    {
        title: 'Invoices',
        href: '/billing/invoices',
    },
];

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const getStatusColor = (status: string) => {
    switch (status) {
        case 'paid':
            return 'text-green-600 bg-green-50';
        case 'open':
            return 'text-yellow-600 bg-yellow-50';
        case 'void':
            return 'text-gray-600 bg-gray-50';
        default:
            return 'text-gray-600 bg-gray-50';
    }
};
</script>

<template>
    <Head title="Invoices" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <HeadingSmall
                title="Invoices"
                description="View and download your billing history"
            />

            <Card>
                <CardHeader>
                    <CardTitle>Billing History</CardTitle>
                    <CardDescription>Your past invoices and payments</CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="invoices.length" class="divide-y">
                        <div
                            v-for="invoice in invoices"
                            :key="invoice.id"
                            class="flex items-center justify-between py-4"
                        >
                            <div>
                                <p class="font-medium">{{ formatDate(invoice.date) }}</p>
                                <p class="text-sm text-muted-foreground">{{ invoice.total }}</p>
                            </div>
                            <div class="flex items-center gap-4">
                                <span
                                    :class="[
                                        'rounded-full px-2 py-1 text-xs font-medium capitalize',
                                        getStatusColor(invoice.status)
                                    ]"
                                >
                                    {{ invoice.status }}
                                </span>
                                <Button variant="outline" size="sm" as-child>
                                    <Link :href="`/billing/invoices/${invoice.id}`">
                                        Download
                                    </Link>
                                </Button>
                            </div>
                        </div>
                    </div>
                    <p v-else class="text-muted-foreground">No invoices yet</p>
                </CardContent>
            </Card>

            <div>
                <Link href="/billing" class="text-sm text-muted-foreground hover:text-foreground">
                    &larr; Back to Billing
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
