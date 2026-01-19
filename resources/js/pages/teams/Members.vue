<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Clock, Mail, MailPlus, RefreshCw, Trash2, Users } from 'lucide-vue-next';
import { ref } from 'vue';

import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Progress } from '@/components/ui/progress';
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

interface PendingInvitation {
    id: number;
    email: string;
    role: string;
    inviter_name: string;
    expires_at: string;
    created_at: string;
}

interface Seats {
    used: number;
    max: number;
    available: number;
    can_add: boolean;
    has_collaboration: boolean;
}

interface Props {
    team: TeamData;
    owner: Owner;
    members: Member[];
    pendingInvitations: PendingInvitation[];
    seats: Seats;
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

const showInviteMember = ref(false);

const inviteForm = useForm({
    email: '',
    role: 'member',
});

const submitInvite = () => {
    inviteForm.post(`/teams/${props.team.slug}/invitations`, {
        onSuccess: () => {
            showInviteMember.value = false;
            inviteForm.reset();
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

const cancelInvitation = (invitation: PendingInvitation) => {
    if (confirm(`Are you sure you want to cancel the invitation to ${invitation.email}?`)) {
        router.delete(`/teams/${props.team.slug}/invitations/${invitation.id}`);
    }
};

const resendInvitation = (invitation: PendingInvitation) => {
    router.post(`/teams/${props.team.slug}/invitations/${invitation.id}/resend`);
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const formatRelativeTime = (dateString: string) => {
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = date.getTime() - now.getTime();
    const diffDays = Math.ceil(diffMs / (1000 * 60 * 60 * 24));

    if (diffDays < 0) {
        return 'Expired';
    } else if (diffDays === 0) {
        return 'Expires today';
    } else if (diffDays === 1) {
        return 'Expires tomorrow';
    } else {
        return `Expires in ${diffDays} days`;
    }
};

const seatUsagePercent = () => {
    if (props.seats.max === -1) return 0;
    return Math.min(100, (props.seats.used / props.seats.max) * 100);
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
                <Dialog v-if="isAdmin && seats.has_collaboration" v-model:open="showInviteMember">
                    <DialogTrigger as-child>
                        <Button :disabled="!seats.can_add">
                            <MailPlus class="mr-2 h-4 w-4" />
                            Invite Member
                        </Button>
                    </DialogTrigger>
                    <DialogContent>
                        <form @submit.prevent="submitInvite">
                            <DialogHeader>
                                <DialogTitle>Invite Team Member</DialogTitle>
                                <DialogDescription>
                                    Send an invitation to join this team. They will receive an email with a link to accept.
                                </DialogDescription>
                            </DialogHeader>
                            <div class="space-y-4 py-4">
                                <div class="space-y-2">
                                    <Label for="email">Email Address</Label>
                                    <Input
                                        id="email"
                                        v-model="inviteForm.email"
                                        type="email"
                                        placeholder="user@example.com"
                                        required
                                    />
                                    <InputError :message="inviteForm.errors.email" />
                                </div>
                                <div class="space-y-2">
                                    <Label for="role">Role</Label>
                                    <select
                                        id="role"
                                        v-model="inviteForm.role"
                                        class="border-input bg-background w-full rounded-md border px-3 py-2"
                                    >
                                        <option value="member">Member</option>
                                        <option value="admin">Admin</option>
                                    </select>
                                    <InputError :message="inviteForm.errors.role" />
                                </div>
                            </div>
                            <DialogFooter>
                                <Button type="submit" :disabled="inviteForm.processing">
                                    {{ inviteForm.processing ? 'Sending...' : 'Send Invitation' }}
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>

            <!-- Seat Usage Card -->
            <Card v-if="seats.has_collaboration">
                <CardHeader class="pb-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <Users class="h-5 w-5 text-muted-foreground" />
                            <CardTitle class="text-lg">Team Seats</CardTitle>
                        </div>
                        <Badge v-if="seats.max === -1" variant="secondary">Unlimited</Badge>
                        <Badge v-else :variant="seats.available === 0 ? 'destructive' : 'secondary'">
                            {{ seats.used }} / {{ seats.max }} used
                        </Badge>
                    </div>
                </CardHeader>
                <CardContent>
                    <div v-if="seats.max !== -1" class="space-y-2">
                        <Progress :model-value="seatUsagePercent()" class="h-2" />
                        <p class="text-sm text-muted-foreground">
                            <template v-if="seats.available === 0">
                                All seats are in use.
                                <Link v-if="isOwner" :href="`/teams/${team.slug}/billing/plans`" class="text-primary hover:underline">
                                    Upgrade your plan
                                </Link>
                                <template v-else>Contact the team owner to add more seats.</template>
                            </template>
                            <template v-else>
                                {{ seats.available }} seat{{ seats.available !== 1 ? 's' : '' }} available
                            </template>
                        </p>
                    </div>
                    <p v-else class="text-sm text-muted-foreground">
                        Your plan includes unlimited team seats.
                    </p>
                </CardContent>
            </Card>

            <!-- Upgrade Prompt for non-collaboration plans -->
            <Alert v-if="!seats.has_collaboration && isOwner" variant="default">
                <Users class="h-4 w-4" />
                <AlertTitle>Team Collaboration</AlertTitle>
                <AlertDescription>
                    Upgrade to Agency plan to invite up to 5 team members.
                    <Link :href="`/teams/${team.slug}/billing/plans`" class="ml-1 text-primary hover:underline">
                        View plans
                    </Link>
                </AlertDescription>
            </Alert>

            <!-- Team Owner -->
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
                        <Badge variant="default">Owner</Badge>
                    </div>
                </CardContent>
            </Card>

            <!-- Pending Invitations -->
            <Card v-if="pendingInvitations.length > 0 && isAdmin">
                <CardHeader>
                    <div class="flex items-center gap-2">
                        <Mail class="h-5 w-5 text-muted-foreground" />
                        <CardTitle>Pending Invitations</CardTitle>
                    </div>
                    <CardDescription>{{ pendingInvitations.length }} pending invitation{{ pendingInvitations.length !== 1 ? 's' : '' }}</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="space-y-4">
                        <div
                            v-for="invitation in pendingInvitations"
                            :key="invitation.id"
                            class="flex items-center justify-between rounded-lg border border-dashed p-4"
                        >
                            <div>
                                <p class="font-medium">{{ invitation.email }}</p>
                                <div class="flex items-center gap-2 text-sm text-muted-foreground">
                                    <span>Invited by {{ invitation.inviter_name }}</span>
                                    <span>&middot;</span>
                                    <span class="flex items-center gap-1">
                                        <Clock class="h-3 w-3" />
                                        {{ formatRelativeTime(invitation.expires_at) }}
                                    </span>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <Badge variant="outline" class="capitalize">{{ invitation.role }}</Badge>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    title="Resend invitation"
                                    @click="resendInvitation(invitation)"
                                >
                                    <RefreshCw class="h-4 w-4" />
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    class="text-destructive hover:text-destructive"
                                    title="Cancel invitation"
                                    @click="cancelInvitation(invitation)"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </Button>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Team Members -->
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
                                <Badge v-else variant="secondary" class="capitalize">
                                    {{ member.role }}
                                </Badge>
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
