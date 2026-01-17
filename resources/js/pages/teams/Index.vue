<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

import HeadingSmall from '@/components/HeadingSmall.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type Team } from '@/types';

interface Props {
    ownedTeams: Team[];
    memberTeams: Team[];
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Teams',
        href: '/teams',
    },
];
</script>

<template>
    <Head title="Teams" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <div class="flex items-center justify-between">
                <HeadingSmall
                    title="Your Teams"
                    description="Manage your teams and team memberships"
                />
                <Button as-child>
                    <Link href="/teams/create">Create Team</Link>
                </Button>
            </div>

            <div v-if="ownedTeams.length" class="space-y-4">
                <h3 class="text-lg font-medium">Teams You Own</h3>
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <Card v-for="team in ownedTeams" :key="team.id">
                        <CardHeader>
                            <CardTitle>
                                <Link :href="`/teams/${team.slug}`" class="hover:underline">
                                    {{ team.name }}
                                </Link>
                            </CardTitle>
                            <CardDescription>{{ team.description || 'No description' }}</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-muted-foreground">
                                    {{ team.members_count }} member{{ team.members_count !== 1 ? 's' : '' }}
                                </span>
                                <span class="rounded-full bg-primary/10 px-2 py-1 text-xs text-primary">Owner</span>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <div v-if="memberTeams.length" class="space-y-4">
                <h3 class="text-lg font-medium">Teams You're In</h3>
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <Card v-for="team in memberTeams" :key="team.id">
                        <CardHeader>
                            <CardTitle>
                                <Link :href="`/teams/${team.slug}`" class="hover:underline">
                                    {{ team.name }}
                                </Link>
                            </CardTitle>
                            <CardDescription>{{ team.description || 'No description' }}</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-muted-foreground">
                                    Owned by {{ team.owner?.name }}
                                </span>
                                <span class="rounded-full bg-secondary px-2 py-1 text-xs capitalize">
                                    {{ team.role }}
                                </span>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <div v-if="!ownedTeams.length && !memberTeams.length" class="rounded-lg border border-dashed p-8 text-center">
                <h3 class="text-lg font-medium">No teams yet</h3>
                <p class="mt-1 text-muted-foreground">Create a team to get started</p>
                <Button class="mt-4" as-child>
                    <Link href="/teams/create">Create Your First Team</Link>
                </Button>
            </div>
        </div>
    </AppLayout>
</template>
