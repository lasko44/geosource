<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Terminal, Copy, Check, ArrowLeft, Globe, Quote, Coins, BarChart3, Shield, MonitorSmartphone, MousePointerClick, CheckCircle2 } from 'lucide-vue-next';
import { ref } from 'vue';

import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'MCP Setup', href: '/mcp' },
];

const copied = ref<string | null>(null);

const configJson = JSON.stringify({
    mcpServers: {
        geosource: {
            command: 'npx',
            args: ['-y', 'geosource-mcp'],
        },
    },
}, null, 2);

const copyToClipboard = (text: string, key: string) => {
    navigator.clipboard.writeText(text);
    copied.value = key;
    setTimeout(() => { copied.value = null; }, 2000);
};

const appOrigin = window.location.origin;

const tools = [
    { icon: Globe, name: 'GEO Scans', description: 'Run scans, check status, and review results with full score breakdowns' },
    { icon: Quote, name: 'Citation Tracking', description: 'Monitor citations across ChatGPT, Gemini, Perplexity, and more' },
    { icon: Coins, name: 'Token Balance', description: 'Check your token usage and remaining balance' },
    { icon: BarChart3, name: 'Dashboard', description: 'Get a high-level overview of your GEO performance' },
];
</script>

<template>
    <Head title="MCP Setup" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-3xl px-6 py-10">
            <!-- Header -->
            <div class="mb-10">
                <Link href="/dashboard" class="mb-6 inline-flex items-center gap-1.5 text-sm text-muted-foreground transition-colors hover:text-foreground">
                    <ArrowLeft class="h-3.5 w-3.5" />
                    Back to Dashboard
                </Link>
                <div class="flex items-start gap-4">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-primary/10">
                        <Terminal class="h-6 w-6 text-primary" />
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight">Connect GEOSource to Claude</h1>
                        <p class="mt-1 text-muted-foreground">
                            Use the Model Context Protocol (MCP) to access GEOSource tools directly from Claude Desktop. Authentication happens securely through your browser.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Steps -->
            <div class="space-y-8">

                <!-- Step 1 -->
                <section class="relative pl-10">
                    <div class="absolute left-0 top-0 flex h-7 w-7 items-center justify-center rounded-full bg-primary text-sm font-bold text-primary-foreground">
                        1
                    </div>
                    <div class="absolute left-[13px] top-8 bottom-0 w-px bg-border"></div>

                    <div>
                        <h2 class="text-lg font-semibold">Install Claude Desktop</h2>
                        <p class="mt-1 text-sm text-muted-foreground">
                            If you don't have it yet, download Claude Desktop from Anthropic.
                        </p>
                        <a
                            href="https://claude.ai/download"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="mt-3 inline-flex items-center gap-2 text-sm font-medium text-primary transition-colors hover:text-primary/80"
                        >
                            <MonitorSmartphone class="h-4 w-4" />
                            Download Claude Desktop
                        </a>
                    </div>
                </section>

                <!-- Step 2 -->
                <section class="relative pl-10">
                    <div class="absolute left-0 top-0 flex h-7 w-7 items-center justify-center rounded-full bg-primary text-sm font-bold text-primary-foreground">
                        2
                    </div>
                    <div class="absolute left-[13px] top-8 bottom-0 w-px bg-border"></div>

                    <div>
                        <h2 class="text-lg font-semibold">Add the MCP server config</h2>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Open Claude Desktop and go to <strong>Settings &rarr; Developer &rarr; Edit Config</strong>. Paste this into your configuration file:
                        </p>
                        <div class="group relative mt-3">
                            <div class="flex items-center justify-between rounded-t-lg border border-b-0 bg-muted/60 px-4 py-2">
                                <span class="text-xs font-medium text-muted-foreground">claude_desktop_config.json</span>
                                <button
                                    class="flex items-center gap-1.5 rounded-md px-2 py-1 text-xs text-muted-foreground transition-colors hover:bg-background hover:text-foreground"
                                    @click="copyToClipboard(configJson, 'config')"
                                >
                                    <Check v-if="copied === 'config'" class="h-3.5 w-3.5 text-green-600" />
                                    <Copy v-else class="h-3.5 w-3.5" />
                                    {{ copied === 'config' ? 'Copied' : 'Copy' }}
                                </button>
                            </div>
                            <pre class="overflow-x-auto rounded-b-lg border bg-muted/30 p-4 font-mono text-sm leading-relaxed">{{ configJson }}</pre>
                        </div>
                    </div>
                </section>

                <!-- Step 3 -->
                <section class="relative pl-10">
                    <div class="absolute left-0 top-0 flex h-7 w-7 items-center justify-center rounded-full bg-primary text-sm font-bold text-primary-foreground">
                        3
                    </div>
                    <div class="absolute left-[13px] top-8 bottom-0 w-px bg-border"></div>

                    <div>
                        <h2 class="text-lg font-semibold">Authorize in your browser</h2>
                        <p class="mt-1 text-sm text-muted-foreground">
                            When you first use a GEOSource tool in Claude, your browser will open automatically to authorize the connection. Sign in to your GEOSource account and click <strong>Authorize</strong>.
                        </p>
                        <div class="mt-4 rounded-lg border bg-muted/30 p-4">
                            <div class="flex items-start gap-3">
                                <Shield class="mt-0.5 h-5 w-5 shrink-0 text-primary" />
                                <div class="space-y-2 text-sm">
                                    <p class="font-medium">Secure OAuth flow</p>
                                    <p class="text-muted-foreground">
                                        Your credentials never leave the browser. The MCP server receives a scoped API token that can only access your GEOSource data. You can revoke access anytime from your
                                        <Link href="/settings/profile" class="text-primary underline underline-offset-2 hover:text-primary/80">account settings</Link>.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Step 4 -->
                <section class="relative pl-10">
                    <div class="absolute left-0 top-0 flex h-7 w-7 items-center justify-center rounded-full bg-primary text-sm font-bold text-primary-foreground">
                        4
                    </div>

                    <div>
                        <h2 class="text-lg font-semibold">Start using GEOSource in Claude</h2>
                        <p class="mt-1 text-sm text-muted-foreground">
                            That's it. Ask Claude to run a scan, check your citations, or review your dashboard. Here are some things to try:
                        </p>
                        <div class="mt-3 space-y-2">
                            <div
                                v-for="(prompt, i) in [
                                    'Run a GEO scan on https://example.com',
                                    'Show me my recent scan results',
                                    'Check my citation status across AI platforms',
                                    'What\'s my current token balance?',
                                ]"
                                :key="i"
                                class="flex items-center gap-2 rounded-lg border bg-muted/20 px-3 py-2 text-sm"
                            >
                                <MousePointerClick class="h-3.5 w-3.5 shrink-0 text-muted-foreground" />
                                <span class="italic text-muted-foreground">"{{ prompt }}"</span>
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            <!-- Available Tools -->
            <div class="mt-12 border-t pt-10">
                <h2 class="text-lg font-semibold">Available tools</h2>
                <p class="mt-1 text-sm text-muted-foreground">
                    The MCP server exposes these capabilities to Claude:
                </p>
                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    <div
                        v-for="tool in tools"
                        :key="tool.name"
                        class="flex items-start gap-3 rounded-lg border p-4"
                    >
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary/10">
                            <component :is="tool.icon" class="h-4.5 w-4.5 text-primary" />
                        </div>
                        <div>
                            <p class="text-sm font-medium">{{ tool.name }}</p>
                            <p class="mt-0.5 text-xs text-muted-foreground">{{ tool.description }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Troubleshooting -->
            <div class="mt-10 border-t pt-10">
                <h2 class="text-lg font-semibold">Troubleshooting</h2>
                <div class="mt-4 space-y-4">
                    <div>
                        <h3 class="text-sm font-medium">The browser doesn't open for authorization</h3>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Make sure you're logged in to GEOSource at <strong>{{ appOrigin }}</strong> before starting Claude Desktop. The auth page requires an active session.
                        </p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium">Tools aren't appearing in Claude</h3>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Fully quit and reopen Claude Desktop after saving your config. Check that <code class="rounded bg-muted px-1.5 py-0.5 text-xs">npx</code> is available in your system PATH.
                        </p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium">Want to re-authorize or switch accounts?</h3>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Delete the stored token file at <code class="rounded bg-muted px-1.5 py-0.5 text-xs">~/.geosource-mcp/config.json</code> and restart Claude Desktop. The browser auth flow will trigger again on next use.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
