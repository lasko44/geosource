<script setup lang="ts">
import { loadStripe, type Stripe, type StripeCardElement } from '@stripe/stripe-js';
import { onMounted, ref, computed } from 'vue';

import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';

interface Props {
    stripeKey: string;
    clientSecret: string;
    buttonText?: string;
}

const props = withDefaults(defineProps<Props>(), {
    buttonText: 'Save Card',
});

const emit = defineEmits<{
    (e: 'success', paymentMethodId: string): void;
    (e: 'error', message: string): void;
}>();

const stripe = ref<Stripe | null>(null);
const cardElement = ref<StripeCardElement | null>(null);
const cardErrors = ref<string>('');
const processing = ref(false);

const isDark = computed(() => document.documentElement.classList.contains('dark'));

onMounted(async () => {
    stripe.value = await loadStripe(props.stripeKey);
    if (stripe.value) {
        const elements = stripe.value.elements();
        cardElement.value = elements.create('card', {
            style: {
                base: {
                    fontSize: '16px',
                    color: isDark.value ? '#e2e8f0' : '#32325d',
                    iconColor: isDark.value ? '#94a3b8' : '#6b7280',
                    '::placeholder': {
                        color: isDark.value ? '#64748b' : '#aab7c4',
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

    try {
        const { setupIntent, error } = await stripe.value.confirmCardSetup(
            props.clientSecret,
            {
                payment_method: {
                    card: cardElement.value,
                },
            }
        );

        if (error) {
            cardErrors.value = error.message || 'An error occurred';
            emit('error', error.message || 'An error occurred');
            return;
        }

        if (setupIntent?.payment_method) {
            emit('success', setupIntent.payment_method as string);
        }
    } finally {
        processing.value = false;
    }
};
</script>

<template>
    <div class="space-y-4">
        <div id="card-element" class="rounded-md border p-3" />
        <Alert v-if="cardErrors" variant="destructive">
            <AlertDescription>{{ cardErrors }}</AlertDescription>
        </Alert>
        <Button
            :disabled="processing"
            class="w-full"
            @click="submit"
        >
            {{ processing ? 'Processing...' : buttonText }}
        </Button>
    </div>
</template>
