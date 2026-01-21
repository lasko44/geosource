<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

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

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Billing',
        href: '/billing',
    },
];

const showCancelModal = ref(false);
const isCanceling = ref(false);

const openCancelModal = () => {
    showCancelModal.value = true;
};

const closeCancelModal = () => {
    showCancelModal.value = false;
};

const confirmCancel = () => {
    isCanceling.value = true;
    router.post('/billing/cancel', {}, {
        onFinish: () => {
            isCanceling.value = false;
            showCancelModal.value = false;
        },
    });
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};
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
                        <div class="mt-4 flex flex-wrap gap-2">
                            <Button as-child>
                                <Link href="/billing/plans">
                                    {{ subscription ? 'Change Plan' : 'View Plans' }}
                                </Link>
                            </Button>
                            <Button v-if="subscription?.canceled && subscription?.on_grace_period" variant="outline" as-child>
                                <Link href="/billing/resume" method="post" as="button">
                                    Resume Subscription
                                </Link>
                            </Button>
                            <Button
                                v-if="subscription?.active && !subscription?.canceled"
                                variant="outline"
                                class="text-destructive hover:text-destructive"
                                @click="openCancelModal"
                            >
                                Cancel Subscription
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <!-- Cancel Subscription Modal -->
                <Teleport to="body">
                    <div
                        v-if="showCancelModal"
                        class="fixed inset-0 z-50 flex items-center justify-center"
                    >
                        <!-- Backdrop -->
                        <div
                            class="absolute inset-0 bg-black/80"
                            @click="closeCancelModal"
                        />

                        <!-- Modal -->
                        <div class="relative z-10 w-full max-w-lg rounded-lg border bg-background p-6 shadow-lg">
                            <h2 class="text-lg font-semibold">Cancel your subscription?</h2>
                            <div class="mt-4 space-y-2 text-sm text-muted-foreground">
                                <p>Are you sure you want to cancel your subscription?</p>
                                <p>You'll continue to have access to all features until the end of your current billing period.</p>
                                <p class="font-medium text-foreground">You can resume your subscription at any time before it expires.</p>
                            </div>
                            <div class="mt-6 flex justify-end gap-3">
                                <Button variant="outline" @click="closeCancelModal">
                                    Keep Subscription
                                </Button>
                                <Button
                                    variant="destructive"
                                    :disabled="isCanceling"
                                    @click="confirmCancel"
                                >
                                    {{ isCanceling ? 'Canceling...' : 'Yes, Cancel Subscription' }}
                                </Button>
                            </div>
                        </div>
                    </div>
                </Teleport>

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

        </div>
    </AppLayout>
</template>
