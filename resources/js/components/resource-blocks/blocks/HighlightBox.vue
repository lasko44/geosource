<script setup lang="ts">
interface Props {
    content?: string;
    title?: string;
    centered?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    centered: false,
});
</script>

<template>
    <div
        class="bg-primary/5 border border-primary/50 rounded-lg p-6 my-6"
        :class="{ 'text-center': centered }"
    >
        <p v-if="title" class="font-semibold text-foreground mb-2">{{ title }}</p>
        <div v-html="content" />
    </div>
</template>
