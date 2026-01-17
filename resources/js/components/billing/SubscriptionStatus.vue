<script setup lang="ts">
import { computed } from 'vue';

import { type Subscription } from '@/types';

interface Props {
    subscription: Subscription | null;
    onTrial: boolean;
    trialEndsAt: string | null;
}

const props = defineProps<Props>();

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const statusInfo = computed(() => {
    if (!props.subscription) {
        if (props.onTrial && props.trialEndsAt) {
            return {
                label: 'Trial',
                color: 'bg-yellow-100 text-yellow-800',
                message: `Trial ends on ${formatDate(props.trialEndsAt)}`,
            };
        }
        return {
            label: 'No Subscription',
            color: 'bg-gray-100 text-gray-800',
            message: 'You are not subscribed to any plan',
        };
    }

    if (props.subscription.cancelled) {
        if (props.subscription.on_grace_period && props.subscription.ends_at) {
            return {
                label: 'Cancelled',
                color: 'bg-orange-100 text-orange-800',
                message: `Access until ${formatDate(props.subscription.ends_at)}`,
            };
        }
        return {
            label: 'Cancelled',
            color: 'bg-red-100 text-red-800',
            message: 'Subscription has ended',
        };
    }

    if (props.subscription.trial_ends_at && new Date(props.subscription.trial_ends_at) > new Date()) {
        return {
            label: 'Trial',
            color: 'bg-blue-100 text-blue-800',
            message: `Trial ends on ${formatDate(props.subscription.trial_ends_at)}`,
        };
    }

    if (props.subscription.active) {
        return {
            label: 'Active',
            color: 'bg-green-100 text-green-800',
            message: 'Your subscription is active',
        };
    }

    return {
        label: props.subscription.stripe_status,
        color: 'bg-gray-100 text-gray-800',
        message: `Status: ${props.subscription.stripe_status}`,
    };
});
</script>

<template>
    <div>
        <div class="flex items-center gap-3">
            <span
                :class="[
                    'rounded-full px-3 py-1 text-sm font-medium',
                    statusInfo.color
                ]"
            >
                {{ statusInfo.label }}
            </span>
        </div>
        <p class="mt-2 text-sm text-muted-foreground">{{ statusInfo.message }}</p>
        <p v-if="subscription?.stripe_price" class="mt-1 text-xs text-muted-foreground">
            Plan: {{ subscription.stripe_price }}
        </p>
    </div>
</template>
