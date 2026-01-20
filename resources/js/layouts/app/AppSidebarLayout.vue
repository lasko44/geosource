<script setup lang="ts">
import { Mail } from 'lucide-vue-next';
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import type { BreadcrumbItemType } from '@/types';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar />
        <AppContent variant="sidebar" class="overflow-x-hidden">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <slot />
            <footer class="mt-auto border-t py-6">
                <div class="flex items-center justify-center gap-2 rounded-lg bg-primary/10 px-4 py-2 mx-auto w-fit">
                    <Mail class="h-4 w-4 text-primary" />
                    <span class="text-sm font-medium">Need help?</span>
                    <a href="mailto:support@geosource.ai" class="text-sm font-semibold text-primary hover:underline">
                        support@geosource.ai
                    </a>
                </div>
            </footer>
        </AppContent>
    </AppShell>
</template>
