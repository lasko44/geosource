<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

interface BreadcrumbItem {
    label: string;
    href?: string;
}

const props = defineProps<{
    items: BreadcrumbItem[];
}>();

// Generate BreadcrumbList schema.org markup
const breadcrumbJsonLd = computed(() => ({
    '@context': 'https://schema.org',
    '@type': 'BreadcrumbList',
    itemListElement: props.items.map((item, index) => ({
        '@type': 'ListItem',
        position: index + 1,
        name: item.label,
        item: item.href ? `https://geosource.ai${item.href}` : undefined,
    })),
}));
</script>

<template>
    <div class="border-b bg-muted/30">
        <div class="mx-auto max-w-4xl px-4 py-4 sm:px-6 lg:px-8">
            <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-sm text-muted-foreground">
                <ol class="flex items-center gap-2" role="list">
                    <li v-for="(item, index) in items" :key="index" class="flex items-center gap-2">
                        <template v-if="item.href">
                            <Link
                                :href="item.href"
                                class="hover:text-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 rounded"
                            >
                                {{ item.label }}
                            </Link>
                        </template>
                        <template v-else>
                            <span class="text-foreground" aria-current="page">{{ item.label }}</span>
                        </template>
                        <span v-if="index < items.length - 1" aria-hidden="true">/</span>
                    </li>
                </ol>
            </nav>
        </div>
    </div>
    <component :is="'script'" type="application/ld+json">{{ JSON.stringify(breadcrumbJsonLd) }}</component>
</template>
