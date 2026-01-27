<script setup lang="ts">
import { defineAsyncComponent, computed } from 'vue';

export interface ContentBlock {
    type: string;
    props?: Record<string, unknown>;
    content?: string | ContentBlock[];
}

interface Props {
    blocks: ContentBlock[];
}

const props = defineProps<Props>();

// Lazy load block components
const blockComponents: Record<string, ReturnType<typeof defineAsyncComponent>> = {
    'heading': defineAsyncComponent(() => import('./blocks/HeadingBlock.vue')),
    'paragraph': defineAsyncComponent(() => import('./blocks/ParagraphBlock.vue')),
    'list': defineAsyncComponent(() => import('./blocks/ListBlock.vue')),
    'table': defineAsyncComponent(() => import('./blocks/TableBlock.vue')),
    'highlight-box': defineAsyncComponent(() => import('./blocks/HighlightBox.vue')),
    'info-box': defineAsyncComponent(() => import('./blocks/InfoBox.vue')),
    'warning-box': defineAsyncComponent(() => import('./blocks/WarningBox.vue')),
    'success-box': defineAsyncComponent(() => import('./blocks/SuccessBox.vue')),
    'definition': defineAsyncComponent(() => import('./blocks/DefinitionBlock.vue')),
    'comparison-cards': defineAsyncComponent(() => import('./blocks/ComparisonCards.vue')),
    'step-list': defineAsyncComponent(() => import('./blocks/StepList.vue')),
    'checklist': defineAsyncComponent(() => import('./blocks/ChecklistBlock.vue')),
    'code': defineAsyncComponent(() => import('./blocks/CodeBlock.vue')),
    'quote': defineAsyncComponent(() => import('./blocks/QuoteBlock.vue')),
    'feature-grid': defineAsyncComponent(() => import('./blocks/FeatureGrid.vue')),
    'cta': defineAsyncComponent(() => import('./blocks/CtaBlock.vue')),
};

const getComponent = (type: string) => {
    return blockComponents[type] || null;
};
</script>

<template>
    <div class="resource-blocks space-y-8">
        <template v-for="(block, index) in blocks" :key="index">
            <component
                v-if="getComponent(block.type)"
                :is="getComponent(block.type)"
                v-bind="block.props"
                :content="block.content"
            />
            <div v-else class="text-red-500">
                Unknown block type: {{ block.type }}
            </div>
        </template>
    </div>
</template>
