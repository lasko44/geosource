<script setup lang="ts">
import { AlertTriangle, Lightbulb } from 'lucide-vue-next';

interface Props {
    content?: string;
    title?: string;
    icon?: 'warning' | 'lightbulb';
}

const props = withDefaults(defineProps<Props>(), {
    icon: 'lightbulb',
});
</script>

<template>
    <div class="bg-amber-500/5 border border-amber-500/50 rounded-lg p-4 my-6">
        <div class="flex items-start gap-3">
            <AlertTriangle v-if="icon === 'warning'" class="h-5 w-5 text-amber-500 shrink-0 mt-0.5" />
            <Lightbulb v-else class="h-5 w-5 text-amber-500 shrink-0 mt-0.5" />
            <div>
                <p v-if="title" class="font-medium text-foreground">{{ title }}</p>
                <div class="text-muted-foreground" v-html="content" />
            </div>
        </div>
    </div>
</template>
