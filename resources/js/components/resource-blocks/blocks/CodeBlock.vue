<script setup lang="ts">
interface Props {
    content?: string;
    language?: string;
}

const props = defineProps<Props>();
</script>

<template>
    <pre class="bg-muted p-4 rounded-lg overflow-x-auto my-6"><code :class="language ? `language-${language}` : ''">{{ content }}</code></pre>
</template>
