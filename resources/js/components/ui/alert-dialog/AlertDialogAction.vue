<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { AlertDialogAction } from 'reka-ui';
import { cn } from '@/lib/utils';
import { buttonVariants } from '@/components/ui/button';
import { useAttrs } from 'vue';

defineOptions({
    inheritAttrs: false,
});

const props = defineProps<{
    class?: HTMLAttributes['class'];
    disabled?: boolean;
}>();

const attrs = useAttrs();
</script>

<template>
    <AlertDialogAction
        :class="cn(buttonVariants(), props.class)"
        :disabled="props.disabled"
        v-bind="attrs"
    >
        <slot />
    </AlertDialogAction>
</template>
