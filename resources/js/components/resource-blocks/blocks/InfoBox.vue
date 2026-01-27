<script setup lang="ts">
import { Info } from 'lucide-vue-next';

interface Props {
    content?: string;
    title?: string;
}

const props = defineProps<Props>();
</script>

<template>
    <div class="bg-blue-500/5 border border-blue-500/50 rounded-lg p-4 my-6">
        <div class="flex items-start gap-3">
            <Info class="h-5 w-5 text-blue-500 shrink-0 mt-0.5" />
            <div>
                <p v-if="title" class="font-medium text-foreground">{{ title }}</p>
                <div class="text-muted-foreground" v-html="content" />
            </div>
        </div>
    </div>
</template>
