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
                color: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300',
                message: `Trial ends on ${formatDate(props.trialEndsAt)}`,
            };
        }
        return {
            label: 'No Subscription',
            color: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
            message: 'You are not subscribed to any plan',
        };
    }

    if (props.subscription.canceled) {
        if (props.subscription.on_grace_period && props.subscription.ends_at) {
            return {
                label: 'Cancelled',
                color: 'bg-orange-100 text-orange-800 dark:bg-orange-900/50 dark:text-orange-300',
                message: `Access until ${formatDate(props.subscription.ends_at)}`,
            };
        }
        return {
            label: 'Cancelled',
            color: 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300',
            message: 'Subscription has ended',
        };
    }

    if (props.subscription.trial_ends_at && new Date(props.subscription.trial_ends_at) > new Date()) {
        return {
            label: 'Trial',
            color: 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300',
            message: `Trial ends on ${formatDate(props.subscription.trial_ends_at)}`,
        };
    }

    if (props.subscription.active) {
        return {
            label: 'Active',
            color: 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300',
            message: 'Your subscription is active',
        };
    }

    return {
        label: props.subscription.stripe_status,
        color: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
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
        <p v-if="subscription?.plan_name" class="mt-1 text-xs text-muted-foreground">
            {{ subscription.plan_name }} - ${{ subscription.plan_price }}/{{ subscription.plan_interval }}
        </p>
    </div>
</template>
