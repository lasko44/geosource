<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { loadStripe, type Stripe, type StripeCardElement } from '@stripe/stripe-js';
import { onMounted, ref } from 'vue';

import HeadingSmall from '@/components/HeadingSmall.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

interface PaymentMethodData {
    id: string;
    brand: string;
    last_four: string;
    exp_month: number;
    exp_year: number;
    is_default: boolean;
}

interface Props {
    paymentMethods: PaymentMethodData[];
    intent: { client_secret: string };
    stripeKey: string;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Billing',
        href: '/billing',
    },
    {
        title: 'Payment Methods',
        href: '/billing/payment-methods',
    },
];

const stripe = ref<Stripe | null>(null);
const cardElement = ref<StripeCardElement | null>(null);
const cardErrors = ref<string>('');
const processing = ref(false);
const showAddForm = ref(false);

onMounted(async () => {
    stripe.value = await loadStripe(props.stripeKey);
});

const initCardElement = () => {
    if (stripe.value && !cardElement.value) {
        const elements = stripe.value.elements();
        cardElement.value = elements.create('card', {
            style: {
                base: {
                    fontSize: '16px',
                    color: '#32325d',
                    '::placeholder': {
                        color: '#aab7c4',
                    },
                },
                invalid: {
                    color: '#fa755a',
                    iconColor: '#fa755a',
                },
            },
        });
        cardElement.value.mount('#card-element');
        cardElement.value.on('change', (event) => {
            cardErrors.value = event.error ? event.error.message : '';
        });
    }
};

const toggleAddForm = () => {
    showAddForm.value = !showAddForm.value;
    if (showAddForm.value) {
        setTimeout(initCardElement, 100);
    }
};

const addPaymentMethod = async () => {
    if (!stripe.value || !cardElement.value) return;

    processing.value = true;
    cardErrors.value = '';

    const { setupIntent, error } = await stripe.value.confirmCardSetup(
        props.intent.client_secret,
        {
            payment_method: {
                card: cardElement.value,
            },
        }
    );

    if (error) {
        cardErrors.value = error.message || 'An error occurred';
        processing.value = false;
        return;
    }

    router.post('/billing/payment-methods', {
        payment_method: setupIntent?.payment_method,
    }, {
        onFinish: () => {
            processing.value = false;
            showAddForm.value = false;
        },
    });
};

const setDefault = (paymentMethodId: string) => {
    router.put('/billing/payment-methods/default', {
        payment_method: paymentMethodId,
    });
};

const removePaymentMethod = (paymentMethodId: string) => {
    if (confirm('Are you sure you want to remove this payment method?')) {
        router.delete('/billing/payment-methods', {
            data: { payment_method: paymentMethodId },
        });
    }
};
</script>

<template>
    <Head title="Payment Methods" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <HeadingSmall
                title="Payment Methods"
                description="Manage your payment methods"
            />

            <Card>
                <CardHeader>
                    <CardTitle>Your Cards</CardTitle>
                    <CardDescription>Add or remove payment methods</CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="paymentMethods.length" class="space-y-4">
                        <div
                            v-for="method in paymentMethods"
                            :key="method.id"
                            class="flex items-center justify-between rounded-lg border p-4"
                        >
                            <div class="flex items-center gap-4">
                                <div class="rounded-md border p-2">
                                    <span class="text-sm font-medium capitalize">{{ method.brand }}</span>
                                </div>
                                <div>
                                    <p class="font-medium">**** **** **** {{ method.last_four }}</p>
                                    <p class="text-sm text-muted-foreground">
                                        Expires {{ method.exp_month }}/{{ method.exp_year }}
                                    </p>
                                </div>
                                <span v-if="method.is_default" class="rounded-full bg-primary/10 px-2 py-1 text-xs text-primary">
                                    Default
                                </span>
                            </div>
                            <div class="flex gap-2">
                                <Button
                                    v-if="!method.is_default"
                                    variant="outline"
                                    size="sm"
                                    @click="setDefault(method.id)"
                                >
                                    Set Default
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    class="text-destructive hover:text-destructive"
                                    @click="removePaymentMethod(method.id)"
                                >
                                    Remove
                                </Button>
                            </div>
                        </div>
                    </div>
                    <p v-else class="text-muted-foreground">No payment methods on file</p>

                    <div class="mt-6">
                        <Button v-if="!showAddForm" @click="toggleAddForm">
                            Add Payment Method
                        </Button>
                        <div v-else class="space-y-4">
                            <label class="block text-sm font-medium">Card Details</label>
                            <div id="card-element" class="rounded-md border p-3" />
                            <Alert v-if="cardErrors" variant="destructive">
                                <AlertDescription>{{ cardErrors }}</AlertDescription>
                            </Alert>
                            <div class="flex gap-2">
                                <Button :disabled="processing" @click="addPaymentMethod">
                                    {{ processing ? 'Adding...' : 'Add Card' }}
                                </Button>
                                <Button variant="outline" @click="toggleAddForm">Cancel</Button>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
