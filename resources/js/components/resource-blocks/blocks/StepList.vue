<script setup lang="ts">
interface Step {
    title: string;
    description?: string;
}

interface Props {
    steps: Step[];
}

const props = defineProps<Props>();
</script>

<template>
    <ol class="space-y-4 my-6">
        <li v-for="(step, index) in steps" :key="index" class="flex items-start gap-4">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary text-primary-foreground text-sm font-bold">
                {{ index + 1 }}
            </span>
            <div class="pt-1">
                <p class="font-medium">{{ step.title }}</p>
                <p v-if="step.description" class="text-sm text-muted-foreground">{{ step.description }}</p>
            </div>
        </li>
    </ol>
</template>
