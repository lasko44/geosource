<script setup lang="ts">
interface Props {
    content?: string;
    author?: string;
}

const props = defineProps<Props>();
</script>

<template>
    <blockquote class="border-l-4 border-primary pl-6 my-6 italic">
        <p v-html="content" />
        <footer v-if="author" class="mt-2 text-sm text-muted-foreground not-italic">
            — {{ author }}
        </footer>
    </blockquote>
</template>
