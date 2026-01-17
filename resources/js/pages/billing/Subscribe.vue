<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { loadStripe, type Stripe, type StripeCardElement } from '@stripe/stripe-js';
import { onMounted, ref } from 'vue';

import HeadingSmall from '@/components/HeadingSmall.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type Plan } from '@/types';

interface Props {
    plan: Plan & { key: string };
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
        title: 'Plans',
        href: '/billing/plans',
    },
    {
        title: 'Subscribe',
        href: `/billing/checkout/${props.plan.key}`,
    },
];

const stripe = ref<Stripe | null>(null);
const cardElement = ref<StripeCardElement | null>(null);
const cardErrors = ref<string>('');
const processing = ref(false);

onMounted(async () => {
    stripe.value = await loadStripe(props.stripeKey);
    if (stripe.value) {
        const elements = stripe.value.elements();
        cardElement.value = elements.create('card', {
            style: {
                base: {
                    fontSize: '16px',
                    color: '#1a1a1a',
                    fontFamily: 'system-ui, -apple-system, sans-serif',
                    '::placeholder': {
                        color: '#6b7280',
                    },
                },
                invalid: {
                    color: '#ef4444',
                    iconColor: '#ef4444',
                },
            },
        });
        cardElement.value.mount('#card-element');
        cardElement.value.on('change', (event) => {
            cardErrors.value = event.error ? event.error.message : '';
        });
    }
});

const submit = async () => {
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

    router.post('/billing/subscribe', {
        plan: props.plan.key,
        payment_method: setupIntent?.payment_method,
    }, {
        onFinish: () => {
            processing.value = false;
        },
    });
};
</script>

<template>
    <Head :title="`Subscribe to ${plan.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-5xl p-6">
            <HeadingSmall
                title="Complete your subscription"
                description="You're one step away from unlocking all features"
            />

            <div class="mt-8 grid gap-8 lg:grid-cols-5">
                <!-- Order Summary -->
                <div class="lg:col-span-2">
                    <div class="sticky top-6 rounded-2xl border bg-card p-6">
                        <h3 class="text-lg font-semibold text-foreground">Order Summary</h3>

                        <div class="mt-6 border-b pb-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="font-medium text-foreground">{{ plan.name }}</p>
                                    <p class="text-sm text-muted-foreground">{{ plan.description }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Features -->
                        <div class="mt-6 space-y-3">
                            <p class="text-sm font-medium text-foreground">What's included:</p>
                            <ul class="space-y-2">
                                <li v-for="feature in plan.features" :key="feature" class="flex items-start gap-2">
                                    <svg class="mt-0.5 h-4 w-4 shrink-0 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span class="text-sm text-muted-foreground">{{ feature }}</span>
                                </li>
                            </ul>
                        </div>

                        <!-- Price breakdown -->
                        <div class="mt-6 border-t pt-6">
                            <div class="flex items-center justify-between">
                                <span class="text-muted-foreground">Subtotal</span>
                                <span class="font-medium text-foreground">${{ plan.price }}</span>
                            </div>
                            <div class="mt-2 flex items-center justify-between">
                                <span class="text-muted-foreground">Billed {{ plan.interval }}ly</span>
                                <span class="text-sm text-muted-foreground">Recurring</span>
                            </div>
                            <div class="mt-4 flex items-center justify-between border-t pt-4">
                                <span class="text-lg font-semibold text-foreground">Total</span>
                                <div class="text-right">
                                    <span class="text-2xl font-bold text-foreground">${{ plan.price }}</span>
                                    <span class="text-muted-foreground">/{{ plan.interval }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Form -->
                <div class="lg:col-span-3">
                    <div class="rounded-2xl border bg-card p-6">
                        <h3 class="text-lg font-semibold text-foreground">Payment Details</h3>
                        <p class="mt-1 text-sm text-muted-foreground">Enter your card information to complete your subscription</p>

                        <div class="mt-6 space-y-6">
                            <!-- Card Element -->
                            <div>
                                <label class="mb-2 block text-sm font-medium text-foreground">
                                    Card Information
                                </label>
                                <div
                                    id="card-element"
                                    class="rounded-lg border border-input bg-background p-4 transition-colors focus-within:border-primary focus-within:ring-1 focus-within:ring-primary"
                                />
                            </div>

                            <!-- Error Alert -->
                            <Alert v-if="cardErrors" variant="destructive">
                                <AlertDescription>{{ cardErrors }}</AlertDescription>
                            </Alert>

                            <!-- Security Note -->
                            <div class="flex items-center gap-2 rounded-lg bg-muted/50 p-3">
                                <svg class="h-5 w-5 text-muted-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                </svg>
                                <span class="text-sm text-muted-foreground">
                                    Your payment is secured with 256-bit SSL encryption
                                </span>
                            </div>

                            <!-- Submit Button -->
                            <Button
                                class="w-full py-6 text-base font-semibold"
                                :disabled="processing"
                                @click="submit"
                            >
                                <svg v-if="processing" class="mr-2 h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                                </svg>
                                {{ processing ? 'Processing...' : `Subscribe for $${plan.price}/${plan.interval}` }}
                            </Button>

                            <!-- Terms -->
                            <p class="text-center text-xs text-muted-foreground">
                                By subscribing, you agree to our Terms of Service and Privacy Policy.
                                You can cancel anytime from your billing settings.
                            </p>
                        </div>
                    </div>

                    <!-- Back link -->
                    <div class="mt-6 text-center">
                        <Link href="/billing/plans" class="text-sm text-muted-foreground hover:text-foreground">
                            &larr; Back to Plans
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
