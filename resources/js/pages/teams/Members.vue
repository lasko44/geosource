<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

interface TeamData {
    id: number;
    name: string;
    slug: string;
}

interface Member {
    id: number;
    name: string;
    email: string;
    role: string;
    joined_at: string;
}

interface Owner {
    id: number;
    name: string;
    email: string;
}

interface Props {
    team: TeamData;
    owner: Owner;
    members: Member[];
    userRole: string | null;
    isOwner: boolean;
    isAdmin: boolean;
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
        title: 'Members',
        href: `/teams/${props.team.slug}/members`,
    },
];

const showAddMember = ref(false);

const addMemberForm = useForm({
    email: '',
    role: 'member',
});

const submitAddMember = () => {
    addMemberForm.post(`/teams/${props.team.slug}/members`, {
        onSuccess: () => {
            showAddMember.value = false;
            addMemberForm.reset();
        },
    });
};

const updateRole = (member: Member, newRole: string) => {
    router.put(`/teams/${props.team.slug}/members/${member.id}`, {
        role: newRole,
    });
};

const removeMember = (member: Member) => {
    if (confirm(`Are you sure you want to remove ${member.name} from the team?`)) {
        router.delete(`/teams/${props.team.slug}/members/${member.id}`);
    }
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};
</script>

<template>
    <Head :title="`${team.name} - Members`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <div class="flex items-center justify-between">
                <HeadingSmall
                    :title="`${team.name} Members`"
                    description="Manage team members and their roles"
                />
                <Dialog v-if="isAdmin" v-model:open="showAddMember">
                    <DialogTrigger as-child>
                        <Button>Add Member</Button>
                    </DialogTrigger>
                    <DialogContent>
                        <form @submit.prevent="submitAddMember">
                            <DialogHeader>
                                <DialogTitle>Add Team Member</DialogTitle>
                                <DialogDescription>
                                    Invite a user to join this team by their email address.
                                </DialogDescription>
                            </DialogHeader>
                            <div class="space-y-4 py-4">
                                <div class="space-y-2">
                                    <Label for="email">Email Address</Label>
                                    <Input
                                        id="email"
                                        v-model="addMemberForm.email"
                                        type="email"
                                        placeholder="user@example.com"
                                        required
                                    />
                                    <InputError :message="addMemberForm.errors.email" />
                                </div>
                                <div class="space-y-2">
                                    <Label for="role">Role</Label>
                                    <select
                                        id="role"
                                        v-model="addMemberForm.role"
                                        class="border-input bg-background w-full rounded-md border px-3 py-2"
                                    >
                                        <option value="member">Member</option>
                                        <option value="admin">Admin</option>
                                    </select>
                                    <InputError :message="addMemberForm.errors.role" />
                                </div>
                            </div>
                            <DialogFooter>
                                <Button type="submit" :disabled="addMemberForm.processing">
                                    {{ addMemberForm.processing ? 'Adding...' : 'Add Member' }}
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Team Owner</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="flex items-center justify-between rounded-lg border p-4">
                        <div>
                            <p class="font-medium">{{ owner.name }}</p>
                            <p class="text-sm text-muted-foreground">{{ owner.email }}</p>
                        </div>
                        <span class="rounded-full bg-primary/10 px-2 py-1 text-xs text-primary">
                            Owner
                        </span>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Team Members</CardTitle>
                    <CardDescription>{{ members.length }} member{{ members.length !== 1 ? 's' : '' }}</CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="members.length" class="space-y-4">
                        <div
                            v-for="member in members"
                            :key="member.id"
                            class="flex items-center justify-between rounded-lg border p-4"
                        >
                            <div>
                                <p class="font-medium">{{ member.name }}</p>
                                <p class="text-sm text-muted-foreground">{{ member.email }}</p>
                                <p class="text-xs text-muted-foreground">Joined {{ formatDate(member.joined_at) }}</p>
                            </div>
                            <div class="flex items-center gap-4">
                                <select
                                    v-if="isAdmin"
                                    :value="member.role"
                                    class="border-input bg-background rounded-md border px-2 py-1 text-sm"
                                    @change="updateRole(member, ($event.target as HTMLSelectElement).value)"
                                >
                                    <option value="member">Member</option>
                                    <option value="admin">Admin</option>
                                </select>
                                <span v-else class="rounded-full bg-secondary px-2 py-1 text-xs capitalize">
                                    {{ member.role }}
                                </span>
                                <Button
                                    v-if="isAdmin"
                                    variant="ghost"
                                    size="sm"
                                    class="text-destructive hover:text-destructive"
                                    @click="removeMember(member)"
                                >
                                    Remove
                                </Button>
                            </div>
                        </div>
                    </div>
                    <p v-else class="text-muted-foreground">No additional members yet</p>
                </CardContent>
            </Card>

            <div>
                <Link :href="`/teams/${team.slug}`" class="text-sm text-muted-foreground hover:text-foreground">
                    &larr; Back to Team
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
