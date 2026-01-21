<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

import HeadingSmall from '@/components/HeadingSmall.vue';
import SubscriptionStatus from '@/components/billing/SubscriptionStatus.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type PaymentMethod, type Subscription } from '@/types';

interface TeamData {
    id: number;
    name: string;
    slug: string;
}

interface Props {
    team: TeamData;
    subscription: Subscription | null;
    defaultPaymentMethod: PaymentMethod | null;
    onTrial: boolean;
    trialEndsAt: string | null;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Teams',
        href: '/teams',
    },
    {
        title: props.team.name,
        href: `/teams/${props.team.slug}`,
    },
    {
        title: 'Billing',
        href: `/teams/${props.team.slug}/billing`,
    },
];
</script>

<template>
    <Head :title="`${team.name} - Billing`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <HeadingSmall
                :title="`${team.name} Billing`"
                description="Manage your team's subscription and billing"
            />

            <div class="grid gap-6 md:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle>Subscription Status</CardTitle>
                        <CardDescription>Current team subscription plan</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <SubscriptionStatus
                            :subscription="subscription"
                            :on-trial="onTrial"
                            :trial-ends-at="trialEndsAt"
                        />
                        <div class="mt-4 flex gap-2">
                            <Button as-child>
                                <Link :href="`/teams/${team.slug}/billing/plans`">
                                    {{ subscription ? 'Change Plan' : 'View Plans' }}
                                </Link>
                            </Button>
                            <Button
                                v-if="subscription?.canceled && subscription?.on_grace_period"
                                variant="outline"
                                as-child
                            >
                                <Link
                                    :href="`/teams/${team.slug}/billing/resume`"
                                    method="post"
                                    as="button"
                                >
                                    Resume
                                </Link>
                            </Button>
                            <Button
                                v-if="subscription?.active && !subscription?.canceled"
                                variant="outline"
                                as-child
                            >
                                <Link
                                    :href="`/teams/${team.slug}/billing/cancel`"
                                    method="post"
                                    as="button"
                                >
                                    Cancel
                                </Link>
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Payment Method</CardTitle>
                        <CardDescription>Team's default payment method</CardDescription>
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
                                <Link :href="`/teams/${team.slug}/billing/portal`">
                                    Manage in Stripe
                                </Link>
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Billing Portal</CardTitle>
                    <CardDescription>Manage your subscription through Stripe</CardDescription>
                </CardHeader>
                <CardContent>
                    <p class="mb-4 text-muted-foreground">
                        Access the Stripe billing portal to manage payment methods, view invoices, and update billing information.
                    </p>
                    <Button variant="outline" as-child>
                        <Link :href="`/teams/${team.slug}/billing/portal`">
                            Open Billing Portal
                        </Link>
                    </Button>
                </CardContent>
            </Card>

            <div>
                <Link :href="`/teams/${team.slug}`" class="text-sm text-muted-foreground hover:text-foreground">
                    &larr; Back to Team
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
