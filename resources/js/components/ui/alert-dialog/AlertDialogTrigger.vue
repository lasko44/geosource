<script setup lang="ts">
import { AlertDialogTrigger } from 'reka-ui';

defineProps<{
    asChild?: boolean;
}>();
</script>

<template>
    <AlertDialogTrigger as-child>
        <slot />
    </AlertDialogTrigger>
</template>
