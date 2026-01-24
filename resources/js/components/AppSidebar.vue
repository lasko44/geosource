<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    BookOpen,
    CalendarClock,
    CircleHelp,
    FileChartColumnIncreasingIcon,
    LayoutGrid,
    Newspaper,
    Quote,
    Receipt,
    Users
} from 'lucide-vue-next';
import { computed } from 'vue';

import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { type NavItem, type TeamBranding } from '@/types';

import AppLogo from './AppLogo.vue';
import AppLogoIcon from './AppLogoIcon.vue';

const page = usePage();
const hasTeams = computed(() => page.props.hasTeams);
const hasCitationAccess = computed(() => page.props.hasCitationAccess);
const hasScheduledScansAccess = computed(() => page.props.hasScheduledScansAccess);
const teamBranding = computed(() => page.props.teamBranding as TeamBranding | null);

const mainNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboard(),
            icon: LayoutGrid,
        },
        {
            title: 'Scans',
            href: '/scans',
            icon: FileChartColumnIncreasingIcon,
        },
    ];

    if (hasScheduledScansAccess.value) {
        items.push({
            title: 'Scheduled Scans',
            href: '/scheduled-scans',
            icon: CalendarClock,
        });
    }

    if (hasCitationAccess.value) {
        items.push({
            title: 'Citations',
            href: '/citations',
            icon: Quote,
        });
    }

    if (hasTeams.value) {
        items.push({
            title: 'Teams',
            href: '/teams',
            icon: Users,
        });
    }

    items.push({
        title: 'Billing',
        href: '/billing',
        icon: Receipt,
    });

    return items;
});

const footerNavItems: NavItem[] = [
    {
        title: 'Blog',
        href: '/blog',
        icon: Newspaper,
        target: '_blank',
    },
    {
        title: 'GEO Resources',
        href: '/resources',
        icon: BookOpen,
        target: '_blank',
    },
    {
        title: 'Help Guide',
        href: '/help',
        icon: CircleHelp,
        target: '_blank',
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <!-- Team branding logo -->
                            <template v-if="teamBranding?.enabled">
                                <div
                                    v-if="teamBranding.logoUrl"
                                    class="flex aspect-square size-8 items-center justify-center rounded-md overflow-hidden"
                                    :style="{ backgroundColor: teamBranding.primaryColor }"
                                >
                                    <img
                                        :src="teamBranding.logoUrl"
                                        :alt="teamBranding.companyName"
                                        class="size-6 object-contain"
                                    />
                                </div>
                                <div
                                    v-else
                                    class="flex aspect-square size-8 items-center justify-center rounded-md text-white font-bold text-sm"
                                    :style="{ backgroundColor: teamBranding.primaryColor }"
                                >
                                    {{ teamBranding.companyName.charAt(0).toUpperCase() }}
                                </div>
                                <div class="ml-1 grid flex-1 text-left text-sm">
                                    <span class="mb-0.5 truncate leading-tight font-semibold">
                                        {{ teamBranding.companyName }}
                                    </span>
                                </div>
                            </template>
                            <!-- Default app logo -->
                            <template v-else>
                                <AppLogo />
                            </template>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
