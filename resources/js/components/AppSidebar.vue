<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    BookOpen,
    CircleHelp,
    FileChartColumnIncreasingIcon,
    LayoutGrid,
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
import { type NavItem } from '@/types';

import AppLogo from './AppLogo.vue';

const page = usePage();
const hasTeams = computed(() => page.props.hasTeams);

const mainNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboard(),
            icon: LayoutGrid,
        },
        {
            title: 'Reports',
            href: '/scans',
            icon: FileChartColumnIncreasingIcon,
        },
    ];

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
                            <AppLogo />
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
