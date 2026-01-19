<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { XCircle } from 'lucide-vue-next';

import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import AuthBase from '@/layouts/AuthLayout.vue';

interface Props {
    message: string;
}

defineProps<Props>();
</script>

<template>
    <AuthBase
        title="Invitation Invalid"
        description="This invitation is no longer valid"
    >
        <Head title="Invalid Invitation" />

        <Card class="border-0 shadow-none">
            <CardHeader class="text-center">
                <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-destructive/10">
                    <XCircle class="h-6 w-6 text-destructive" />
                </div>
                <CardTitle class="text-xl">Invitation Invalid</CardTitle>
                <CardDescription>{{ message }}</CardDescription>
            </CardHeader>

            <CardContent class="space-y-4">
                <p class="text-center text-sm text-muted-foreground">
                    If you believe this is an error, please contact the team owner to send you a new invitation.
                </p>
            </CardContent>

            <CardFooter class="flex justify-center gap-3">
                <Button variant="outline" as-child>
                    <TextLink href="/login">Log in</TextLink>
                </Button>
                <Button as-child>
                    <TextLink href="/">Go to home</TextLink>
                </Button>
            </CardFooter>
        </Card>
    </AuthBase>
</template>
