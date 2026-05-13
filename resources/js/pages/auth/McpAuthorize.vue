<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';

import { Button } from '@/components/ui/button';
import AuthBase from '@/layouts/AuthLayout.vue';

const props = defineProps<{
    port: number;
    state: string;
}>();

const form = useForm({
    port: props.port,
    state: props.state,
});

const authorize = () => {
    form.post('/auth/mcp');
};
</script>

<template>
    <AuthBase
        title="Authorize MCP Server"
        description="An MCP client is requesting access to your Geosource account"
    >
        <Head title="Authorize MCP Server" />

        <div class="flex flex-col gap-6">
            <div class="rounded-lg border border-border bg-muted/50 p-4">
                <p class="text-sm text-muted-foreground">
                    This will create an API token that allows the MCP server to:
                </p>
                <ul class="mt-3 space-y-1.5 text-sm text-muted-foreground">
                    <li class="flex items-center gap-2">
                        <span class="h-1.5 w-1.5 rounded-full bg-primary" />
                        View and run GEO scans
                    </li>
                    <li class="flex items-center gap-2">
                        <span class="h-1.5 w-1.5 rounded-full bg-primary" />
                        Manage citation queries and checks
                    </li>
                    <li class="flex items-center gap-2">
                        <span class="h-1.5 w-1.5 rounded-full bg-primary" />
                        View your token balance and dashboard
                    </li>
                </ul>
            </div>

            <div class="flex gap-3">
                <Button
                    class="flex-1"
                    @click="authorize"
                    :disabled="form.processing"
                >
                    Authorize
                </Button>
                <Button
                    variant="outline"
                    class="flex-1"
                    as="a"
                    href="/"
                >
                    Cancel
                </Button>
            </div>

            <p class="text-center text-xs text-muted-foreground">
                You can revoke this token at any time from your settings.
            </p>
        </div>
    </AuthBase>
</template>
