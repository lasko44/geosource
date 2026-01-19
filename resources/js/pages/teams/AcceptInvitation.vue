<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Users } from 'lucide-vue-next';
import { ref } from 'vue';

import TextLink from '@/components/TextLink.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Spinner } from '@/components/ui/spinner';
import AuthBase from '@/layouts/AuthLayout.vue';

interface Invitation {
    token: string;
    team_name: string;
    email: string;
    role: string;
    inviter_name: string;
    expires_at: string;
}

interface Props {
    invitation: Invitation;
    hasAccount: boolean;
}

const props = defineProps<Props>();

const processing = ref(false);
const error = ref<string | null>(null);

const acceptInvitation = () => {
    processing.value = true;
    error.value = null;

    router.post(`/invitations/${props.invitation.token}/accept`, {}, {
        onError: (errors) => {
            processing.value = false;
            error.value = errors.error || 'Failed to accept invitation. Please try again.';
        },
        onFinish: () => {
            processing.value = false;
        },
    });
};

const formatExpirationDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: 'numeric',
        minute: 'numeric',
    });
};
</script>

<template>
    <AuthBase
        title="Team Invitation"
        description="You've been invited to join a team"
    >
        <Head title="Team Invitation" />

        <Card class="border-0 shadow-none">
            <CardHeader class="text-center">
                <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-primary/10">
                    <Users class="h-6 w-6 text-primary" />
                </div>
                <CardTitle class="text-xl">Join {{ invitation.team_name }}</CardTitle>
                <CardDescription>
                    {{ invitation.inviter_name }} has invited you to join their team as a
                    <Badge variant="secondary" class="ml-1 capitalize">{{ invitation.role }}</Badge>
                </CardDescription>
            </CardHeader>

            <CardContent class="space-y-4">
                <div class="rounded-lg border bg-muted/50 p-4">
                    <div class="grid gap-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Email</span>
                            <span class="font-medium">{{ invitation.email }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Role</span>
                            <span class="font-medium capitalize">{{ invitation.role }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Expires</span>
                            <span class="font-medium">{{ formatExpirationDate(invitation.expires_at) }}</span>
                        </div>
                    </div>
                </div>

                <div v-if="error" class="rounded-lg border border-destructive/50 bg-destructive/10 p-3 text-sm text-destructive">
                    {{ error }}
                </div>

                <div v-if="!$page.props.auth.user" class="space-y-3">
                    <p class="text-center text-sm text-muted-foreground">
                        <template v-if="hasAccount">
                            You need to log in to accept this invitation.
                        </template>
                        <template v-else>
                            Create an account to join the team.
                        </template>
                    </p>
                    <div class="flex gap-3">
                        <Button v-if="hasAccount" class="flex-1" as-child>
                            <Link :href="`/login?redirect=/invitations/${invitation.token}`">
                                Log in to accept
                            </Link>
                        </Button>
                        <Button v-else class="flex-1" as-child>
                            <Link :href="`/register?email=${encodeURIComponent(invitation.email)}&redirect=/invitations/${invitation.token}`">
                                Create account
                            </Link>
                        </Button>
                    </div>
                </div>

                <div v-else class="space-y-3">
                    <div v-if="$page.props.auth.user.email.toLowerCase() !== invitation.email.toLowerCase()" class="rounded-lg border border-yellow-500/50 bg-yellow-500/10 p-3 text-sm text-yellow-600 dark:text-yellow-400">
                        You're logged in as <strong>{{ $page.props.auth.user.email }}</strong>, but this invitation was sent to <strong>{{ invitation.email }}</strong>. Please log in with the correct account.
                    </div>
                    <Button
                        v-else
                        class="w-full"
                        :disabled="processing"
                        @click="acceptInvitation"
                    >
                        <Spinner v-if="processing" class="mr-2" />
                        {{ processing ? 'Joining...' : 'Accept Invitation' }}
                    </Button>
                </div>
            </CardContent>

            <CardFooter class="justify-center">
                <TextLink href="/" class="text-sm text-muted-foreground">
                    Return to home
                </TextLink>
            </CardFooter>
        </Card>
    </AuthBase>
</template>
