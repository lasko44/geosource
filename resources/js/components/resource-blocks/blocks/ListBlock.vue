<script setup lang="ts">
import { CheckCircle } from 'lucide-vue-next';

interface Props {
    type?: 'bullet' | 'numbered' | 'check';
    items: string[];
}

const props = withDefaults(defineProps<Props>(), {
    type: 'bullet',
});
</script>

<template>
    <ul v-if="type === 'bullet'" class="my-6 list-disc pl-6 space-y-2">
        <li v-for="item in items" :key="item" class="text-muted-foreground" v-html="item" />
    </ul>

    <ol v-else-if="type === 'numbered'" class="my-6 list-decimal pl-6 space-y-2">
        <li v-for="item in items" :key="item" class="text-muted-foreground" v-html="item" />
    </ol>

    <ul v-else class="my-6 space-y-3">
        <li v-for="item in items" :key="item" class="flex items-start gap-3">
            <CheckCircle class="h-5 w-5 text-green-500 shrink-0 mt-0.5" />
            <span v-html="item" />
        </li>
    </ul>
</template>
