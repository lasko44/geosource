<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

import HeadingSmall from '@/components/HeadingSmall.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

interface TeamData {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    created_at: string;
    owner: {
        id: number;
        name: string;
        email: string;
    };
    members_count: number;
}

interface Props {
    team: TeamData;
    userRole: string | null;
    isOwner: boolean;
    isAdmin: boolean;
    hasSubscription: boolean;
    hasWhiteLabel: boolean;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Teams',
        href: '/teams',
    },
    {
        title: props.team.name,
        href: `/teams/${props.team.slug}`,
    },
];

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};
</script>

<template>
    <Head :title="team.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <div class="flex items-start justify-between">
                <HeadingSmall
                    :title="team.name"
                    :description="team.description || 'No description'"
                />
                <div v-if="isAdmin" class="flex gap-2">
                    <Button variant="outline" as-child>
                        <Link :href="`/teams/${team.slug}/edit`">Edit Team</Link>
                    </Button>
                </div>
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle>Team Details</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div>
                            <p class="text-sm text-muted-foreground">Owner</p>
                            <p class="font-medium">{{ team.owner.name }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-muted-foreground">Members</p>
                            <p class="font-medium">{{ team.members_count }} member{{ team.members_count !== 1 ? 's' : '' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-muted-foreground">Created</p>
                            <p class="font-medium">{{ formatDate(team.created_at) }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-muted-foreground">Your Role</p>
                            <p class="font-medium capitalize">{{ userRole }}</p>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Quick Actions</CardTitle>
                        <CardDescription>Manage your team</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <Button variant="outline" class="w-full justify-start" as-child>
                            <Link :href="`/teams/${team.slug}/members`">
                                <svg xmlns="http://www.w3.org/2000/svg" class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                                </svg>
                                Manage Members
                            </Link>
                        </Button>
                                                <Button v-if="hasWhiteLabel && isAdmin" variant="outline" class="w-full justify-start" as-child>
                            <Link :href="`/teams/${team.slug}/white-label`">
                                <svg xmlns="http://www.w3.org/2000/svg" class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
                                </svg>
                                White Label Reports
                            </Link>
                        </Button>
                        <Button v-if="!isOwner" variant="outline" class="w-full justify-start text-destructive hover:text-destructive" as-child>
                            <Link
                                :href="`/teams/${team.slug}/leave`"
                                method="post"
                                as="button"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                                Leave Team
                            </Link>
                        </Button>
                    </CardContent>
                </Card>
            </div>

            <Card v-if="isOwner && !hasSubscription">
                <CardHeader>
                    <CardTitle>Subscription Required</CardTitle>
                    <CardDescription>Subscribe to unlock team features</CardDescription>
                </CardHeader>
                <CardContent>
                    <p class="mb-4 text-muted-foreground">
                        Get a team subscription to unlock collaboration features and add more members.
                    </p>
                    <Button as-child>
                        <Link :href="`/teams/${team.slug}/billing/plans`">View Plans</Link>
                    </Button>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
