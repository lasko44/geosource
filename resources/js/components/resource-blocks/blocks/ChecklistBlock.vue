<script setup lang="ts">
import { Square, CheckSquare } from 'lucide-vue-next';
import { computed } from 'vue';

interface ChecklistItem {
    text: string;
    checked?: boolean;
}

interface Props {
    title?: string;
    items: (string | ChecklistItem)[];
}

const props = defineProps<Props>();

// Normalize items to always have text and checked properties
const normalizedItems = computed(() => {
    return props.items.map(item => {
        if (typeof item === 'string') {
            return { text: item, checked: false };
        }
        return { text: item.text, checked: item.checked ?? false };
    });
});
</script>

<template>
    <div class="my-6">
        <h3 v-if="title" class="font-semibold mb-3">{{ title }}</h3>
        <ul class="space-y-2">
            <li v-for="(item, index) in normalizedItems" :key="index" class="flex items-start gap-2">
                <CheckSquare v-if="item.checked" class="h-4 w-4 text-green-500 shrink-0 mt-1" />
                <Square v-else class="h-4 w-4 text-muted-foreground shrink-0 mt-1" />
                <span v-html="item.text" />
            </li>
        </ul>
    </div>
</template>
