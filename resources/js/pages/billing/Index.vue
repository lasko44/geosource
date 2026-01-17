<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

import HeadingSmall from '@/components/HeadingSmall.vue';
import SubscriptionStatus from '@/components/billing/SubscriptionStatus.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type PaymentMethod, type Subscription } from '@/types';

interface Props {
    subscription: Subscription | null;
    defaultPaymentMethod: PaymentMethod | null;
    onTrial: boolean;
    trialEndsAt: string | null;
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Billing',
        href: '/billing',
    },
];
</script>

<template>
    <Head title="Billing" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <HeadingSmall
                title="Billing & Subscription"
                description="Manage your subscription and billing information"
            />

            <div class="grid gap-6 md:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle>Subscription Status</CardTitle>
                        <CardDescription>Your current subscription plan</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <SubscriptionStatus
                            :subscription="subscription"
                            :on-trial="onTrial"
                            :trial-ends-at="trialEndsAt"
                        />
                        <div class="mt-4 flex gap-2">
                            <Button as-child>
                                <Link href="/billing/plans">
                                    {{ subscription ? 'Change Plan' : 'View Plans' }}
                                </Link>
                            </Button>
                            <Button v-if="subscription?.cancelled && subscription?.on_grace_period" variant="outline" as-child>
                                <Link href="/billing" method="post" :data="{ _method: 'POST' }" as="button">
                                    Resume Subscription
                                </Link>
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Payment Method</CardTitle>
                        <CardDescription>Your default payment method</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div v-if="defaultPaymentMethod" class="flex items-center gap-3">
                            <div class="rounded-md border p-2">
                                <span class="text-sm font-medium capitalize">{{ defaultPaymentMethod.brand }}</span>
                            </div>
                            <span class="text-muted-foreground">**** **** **** {{ defaultPaymentMethod.last_four }}</span>
                        </div>
                        <p v-else class="text-muted-foreground">No payment method on file</p>
                        <div class="mt-4">
                            <Button variant="outline" as-child>
                                <Link href="/billing/payment-methods">Manage Payment Methods</Link>
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Billing History</CardTitle>
                    <CardDescription>View and download your invoices</CardDescription>
                </CardHeader>
                <CardContent>
                    <Button variant="outline" as-child>
                        <Link href="/billing/invoices">View Invoices</Link>
                    </Button>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Billing Portal</CardTitle>
                    <CardDescription>Manage your subscription through Stripe</CardDescription>
                </CardHeader>
                <CardContent>
                    <Button variant="outline" as-child>
                        <Link href="/billing/portal">Open Billing Portal</Link>
                    </Button>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
