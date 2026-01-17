<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { loadStripe, type Stripe, type StripeCardElement } from '@stripe/stripe-js';
import { onMounted, ref } from 'vue';

import HeadingSmall from '@/components/HeadingSmall.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
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
        <div class="mx-auto max-w-2xl p-6">
            <HeadingSmall
                :title="`Subscribe to ${plan.name}`"
                :description="plan.description"
            />

            <Card class="mt-6">
                <CardHeader>
                    <CardTitle>{{ plan.name }}</CardTitle>
                    <CardDescription>
                        <span class="text-2xl font-bold">${{ plan.price }}</span>
                        <span class="text-muted-foreground">/{{ plan.interval }}</span>
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <ul class="mb-6 space-y-2">
                        <li v-for="feature in plan.features" :key="feature" class="flex items-center gap-2">
                            <svg class="h-4 w-4 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            {{ feature }}
                        </li>
                    </ul>

                    <div class="space-y-4">
                        <label class="block text-sm font-medium">Card Details</label>
                        <div id="card-element" class="rounded-md border p-3" />
                        <Alert v-if="cardErrors" variant="destructive">
                            <AlertDescription>{{ cardErrors }}</AlertDescription>
                        </Alert>
                    </div>
                </CardContent>
                <CardFooter>
                    <Button
                        class="w-full"
                        :disabled="processing"
                        @click="submit"
                    >
                        {{ processing ? 'Processing...' : `Subscribe for $${plan.price}/${plan.interval}` }}
                    </Button>
                </CardFooter>
            </Card>
        </div>
    </AppLayout>
</template>
