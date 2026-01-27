<script setup lang="ts">
interface Props {
    level?: 2 | 3 | 4;
    content?: string;
    id?: string;
}

const props = withDefaults(defineProps<Props>(), {
    level: 2,
});
</script>

<template>
    <h2
        v-if="level === 2"
        :id="id"
        class="text-2xl font-bold mb-6 mt-12 scroll-mt-20"
    >
        {{ content }}
    </h2>
    <h3
        v-else-if="level === 3"
        :id="id"
        class="text-xl font-semibold mb-4 mt-8 scroll-mt-20"
    >
        {{ content }}
    </h3>
    <h4
        v-else
        :id="id"
        class="text-lg font-semibold mb-3 mt-6 scroll-mt-20"
    >
        {{ content }}
    </h4>
</template>
