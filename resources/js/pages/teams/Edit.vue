<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

interface TeamData {
    id: number;
    name: string;
    slug: string;
    description: string | null;
}

interface Props {
    team: TeamData;
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
    {
        title: 'Edit',
        href: `/teams/${props.team.slug}/edit`,
    },
];

const form = useForm({
    name: props.team.name,
    slug: props.team.slug,
    description: props.team.description || '',
});

const showDeleteConfirm = ref(false);
const deleteProcessing = ref(false);

const submit = () => {
    form.put(`/teams/${props.team.slug}`);
};

const deleteTeam = () => {
    deleteProcessing.value = true;
    router.delete(`/teams/${props.team.slug}`, {
        onFinish: () => {
            deleteProcessing.value = false;
        },
    });
};
</script>

<template>
    <Head :title="`Edit ${team.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-2xl p-6">
            <HeadingSmall
                :title="`Edit ${team.name}`"
                description="Update your team's details"
            />

            <Card class="mt-6">
                <form @submit.prevent="submit">
                    <CardHeader>
                        <CardTitle>Team Details</CardTitle>
                        <CardDescription>Make changes to your team</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="space-y-2">
                            <Label for="name">Team Name</Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                type="text"
                                required
                            />
                            <InputError :message="form.errors.name" />
                        </div>

                        <div class="space-y-2">
                            <Label for="slug">URL Slug</Label>
                            <div class="flex items-center gap-2">
                                <span class="text-muted-foreground">/teams/</span>
                                <Input
                                    id="slug"
                                    v-model="form.slug"
                                    type="text"
                                    class="flex-1"
                                />
                            </div>
                            <InputError :message="form.errors.slug" />
                        </div>

                        <div class="space-y-2">
                            <Label for="description">Description</Label>
                            <Textarea
                                id="description"
                                v-model="form.description"
                                rows="3"
                            />
                            <InputError :message="form.errors.description" />
                        </div>
                    </CardContent>
                    <CardFooter class="flex justify-between">
                        <Button type="submit" :disabled="form.processing">
                            {{ form.processing ? 'Saving...' : 'Save Changes' }}
                        </Button>
                        <Button variant="ghost" as-child>
                            <Link :href="`/teams/${team.slug}`">Cancel</Link>
                        </Button>
                    </CardFooter>
                </form>
            </Card>

            <Card class="mt-6 border-destructive">
                <CardHeader>
                    <CardTitle class="text-destructive">Danger Zone</CardTitle>
                    <CardDescription>Irreversible actions</CardDescription>
                </CardHeader>
                <CardContent>
                    <Alert v-if="showDeleteConfirm" variant="destructive">
                        <AlertTitle>Are you sure?</AlertTitle>
                        <AlertDescription>
                            This action cannot be undone. This will permanently delete the team
                            and remove all members.
                        </AlertDescription>
                        <div class="mt-4 flex gap-2">
                            <Button
                                variant="destructive"
                                size="sm"
                                :disabled="deleteProcessing"
                                @click="deleteTeam"
                            >
                                {{ deleteProcessing ? 'Deleting...' : 'Yes, delete team' }}
                            </Button>
                            <Button
                                variant="outline"
                                size="sm"
                                @click="showDeleteConfirm = false"
                            >
                                Cancel
                            </Button>
                        </div>
                    </Alert>
                    <Button
                        v-else
                        variant="destructive"
                        @click="showDeleteConfirm = true"
                    >
                        Delete Team
                    </Button>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
