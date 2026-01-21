<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { Mail } from 'lucide-vue-next';
import { computed } from 'vue';
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import type { BreadcrumbItemType, TeamBranding } from '@/types';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const teamBranding = computed(() => page.props.teamBranding as TeamBranding | null);

// Generate CSS custom properties for team branding
const brandingStyles = computed(() => {
    if (!teamBranding.value?.enabled) {
        return {};
    }
    return {
        '--team-primary': teamBranding.value.primaryColor,
        '--team-secondary': teamBranding.value.secondaryColor,
    };
});

const contactEmail = computed(() => {
    if (teamBranding.value?.enabled && teamBranding.value.contactEmail) {
        return teamBranding.value.contactEmail;
    }
    return 'support@geosource.ai';
});
</script>

<template>
    <AppShell variant="sidebar" :style="brandingStyles">
        <AppSidebar />
        <AppContent variant="sidebar" class="overflow-x-hidden">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <slot />
            <footer class="mt-auto border-t py-6">
                <div
                    class="flex items-center justify-center gap-2 rounded-lg px-4 py-2 mx-auto w-fit"
                    :class="teamBranding?.enabled ? 'bg-[var(--team-primary)]/10' : 'bg-primary/10'"
                >
                    <Mail
                        class="h-4 w-4"
                        :class="teamBranding?.enabled ? 'text-[var(--team-primary)]' : 'text-primary'"
                    />
                    <span class="text-sm font-medium">Need help?</span>
                    <a
                        :href="`mailto:${contactEmail}`"
                        class="text-sm font-semibold hover:underline"
                        :class="teamBranding?.enabled ? 'text-[var(--team-primary)]' : 'text-primary'"
                    >
                        {{ contactEmail }}
                    </a>
                </div>
            </footer>
        </AppContent>
    </AppShell>
</template>
