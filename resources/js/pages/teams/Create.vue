<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';

import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Teams',
        href: '/teams',
    },
    {
        title: 'Create Team',
        href: '/teams/create',
    },
];

const form = useForm({
    name: '',
    slug: '',
    description: '',
});

const submit = () => {
    form.post('/teams');
};
</script>

<template>
    <Head title="Create Team" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-2xl p-6">
            <HeadingSmall
                title="Create a New Team"
                description="Teams allow you to collaborate with others"
            />

            <Card class="mt-6">
                <form @submit.prevent="submit">
                    <CardHeader>
                        <CardTitle>Team Details</CardTitle>
                        <CardDescription>Enter the details for your new team</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="space-y-2">
                            <Label for="name">Team Name</Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                type="text"
                                placeholder="My Awesome Team"
                                required
                            />
                            <InputError :message="form.errors.name" />
                        </div>

                        <div class="space-y-2">
                            <Label for="slug">URL Slug (optional)</Label>
                            <div class="flex items-center gap-2">
                                <span class="text-muted-foreground">/teams/</span>
                                <Input
                                    id="slug"
                                    v-model="form.slug"
                                    type="text"
                                    placeholder="my-awesome-team"
                                    class="flex-1"
                                />
                            </div>
                            <p class="text-sm text-muted-foreground">
                                Leave empty to auto-generate from team name
                            </p>
                            <InputError :message="form.errors.slug" />
                        </div>

                        <div class="space-y-2">
                            <Label for="description">Description (optional)</Label>
                            <Textarea
                                id="description"
                                v-model="form.description"
                                placeholder="What is this team for?"
                                rows="3"
                            />
                            <InputError :message="form.errors.description" />
                        </div>
                    </CardContent>
                    <CardFooter>
                        <Button type="submit" :disabled="form.processing">
                            {{ form.processing ? 'Creating...' : 'Create Team' }}
                        </Button>
                    </CardFooter>
                </form>
            </Card>
        </div>
    </AppLayout>
</template>
