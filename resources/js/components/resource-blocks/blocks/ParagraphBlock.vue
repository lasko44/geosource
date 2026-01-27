<script setup lang="ts">
interface Props {
    content?: string;
    class?: string;
}

const props = defineProps<Props>();
</script>

<template>
    <p
        class="text-muted-foreground leading-relaxed"
        :class="props.class"
        v-html="content"
    />
</template>
