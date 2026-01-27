<script setup lang="ts">
interface Props {
    term: string;
    definition: string;
    source?: string;
}

const props = defineProps<Props>();
</script>

<template>
    <div class="bg-primary/5 border border-primary/50 rounded-lg p-6 my-6">
        <p class="text-lg">
            <dfn class="font-semibold not-italic"><strong>{{ term }}</strong></dfn>
            <span v-html="' ' + definition" />
        </p>
        <p v-if="source" class="mt-4 text-sm text-muted-foreground">
            <strong>Defined by:</strong> {{ source }}
        </p>
    </div>
</template>
