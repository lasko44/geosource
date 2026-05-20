<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    Terminal,
    Copy,
    Check,
    Globe,
    Quote,
    Coins,
    BarChart3,
    Shield,
    MonitorSmartphone,
    MousePointerClick,
    ArrowRight,
    Sparkles,
} from 'lucide-vue-next';
import { ref, computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import SkipNav from '@/components/resources/SkipNav.vue';
import ResourceHeader from '@/components/resources/ResourceHeader.vue';
import ResourceFooter from '@/components/resources/ResourceFooter.vue';
import ResourceBreadcrumb from '@/components/resources/ResourceBreadcrumb.vue';
import { register } from '@/routes';

const breadcrumbItems = [
    { label: 'Resources', href: '/resources' },
    { label: 'MCP Setup' },
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

const tools = [
    { icon: Globe, name: 'GEO Scans', description: 'Run scans, check status, and review results with full score breakdowns' },
    { icon: Quote, name: 'Citation Tracking', description: 'Monitor citations across ChatGPT, Gemini, Perplexity, and more' },
    { icon: Coins, name: 'Token Balance', description: 'Check your token usage and remaining balance' },
    { icon: BarChart3, name: 'Dashboard', description: 'Get a high-level overview of your GEO performance' },
];

const articleJsonLd = computed(() => ({
    '@context': 'https://schema.org',
    '@type': 'HowTo',
    name: 'Connect GeoSource via MCP',
    description: 'Step-by-step guide to connecting GeoSource.ai to any MCP-compatible client. Run GEO scans and track citations directly from your AI assistant or editor.',
    url: 'https://geosource.ai/mcp',
    publisher: {
        '@type': 'Organization',
        name: 'GeoSource.ai',
        url: 'https://geosource.ai',
    },
    step: [
        { '@type': 'HowToStep', position: 1, name: 'Install an MCP-compatible client', text: 'Download Claude Desktop, Cursor, Windsurf, or any editor that supports the Model Context Protocol.' },
        { '@type': 'HowToStep', position: 2, name: 'Add the MCP server config', text: 'Add the GeoSource MCP server configuration to your client\'s MCP settings.' },
        { '@type': 'HowToStep', position: 3, name: 'Authorize in your browser', text: 'When you first use a GeoSource tool, your browser opens automatically to authorize the connection.' },
        { '@type': 'HowToStep', position: 4, name: 'Start using GeoSource', text: 'Run GEO scans, check citations, or review your dashboard directly from your MCP client.' },
    ],
}));

const faqJsonLd = computed(() => ({
    '@context': 'https://schema.org',
    '@type': 'FAQPage',
    mainEntity: [
        {
            '@type': 'Question',
            name: 'What is the GeoSource MCP server?',
            acceptedAnswer: {
                '@type': 'Answer',
                text: 'The GeoSource MCP server connects GeoSource.ai to any MCP-compatible client using the Model Context Protocol. It lets you run GEO scans, track AI citations, check token balances, and view dashboard metrics directly from your AI assistant or editor.',
            },
        },
        {
            '@type': 'Question',
            name: 'What clients support the GeoSource MCP server?',
            acceptedAnswer: {
                '@type': 'Answer',
                text: 'The GeoSource MCP server works with any MCP-compatible client including Claude Desktop, Cursor, Windsurf, and other editors that support the Model Context Protocol.',
            },
        },
        {
            '@type': 'Question',
            name: 'Is the MCP connection secure?',
            acceptedAnswer: {
                '@type': 'Answer',
                text: 'Yes. Authentication uses a secure OAuth flow through your browser. Your credentials never leave the browser. The MCP server receives a scoped API token that can only access your GeoSource data, and you can revoke access anytime.',
            },
        },
    ],
}));
</script>

<template>
    <Head>
        <title>GeoSource MCP Server: Connect to Any MCP Client</title>
        <meta name="description" content="Set up the GeoSource MCP server to run GEO scans, track AI citations, and monitor performance from Claude Desktop, Cursor, Windsurf, or any MCP-compatible client." />
        <link rel="canonical" href="https://geosource.ai/mcp" />
        <meta property="og:title" content="GeoSource MCP Server: Connect to Any MCP Client" />
        <meta property="og:description" content="Set up the GeoSource MCP server to run GEO scans, track AI citations, and monitor performance from Claude Desktop, Cursor, Windsurf, or any MCP-compatible client." />
        <meta property="og:url" content="https://geosource.ai/mcp" />
        <meta property="og:type" content="article" />
        <meta property="og:site_name" content="GeoSource.ai" />
    </Head>

    <SkipNav />
    <ResourceHeader />

    <main id="main-content">
        <ResourceBreadcrumb :items="breadcrumbItems" />

        <!-- Hero Section -->
        <section class="border-b bg-gradient-to-b from-primary/5 to-background py-16 sm:py-20">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col items-start gap-6">
                    <Badge variant="secondary" class="bg-primary/10 text-primary">
                        <Terminal class="mr-1.5 h-3 w-3" aria-hidden="true" />
                        MCP Integration
                    </Badge>
                    <h1 class="text-3xl font-bold tracking-tight sm:text-4xl lg:text-5xl">
                        GeoSource MCP Server
                    </h1>
                    <p class="max-w-2xl text-lg text-muted-foreground sm:text-xl">
                        Use the Model Context Protocol (MCP) to access GeoSource tools directly from Claude Desktop, Cursor, Windsurf, or any MCP-compatible client. Run GEO scans and track citations without leaving your workflow.
                    </p>
                    <div class="flex flex-wrap gap-3">
                        <Link :href="register().url">
                            <Button size="lg">
                                <Sparkles class="mr-2 h-4 w-4" aria-hidden="true" />
                                Get started free
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>
        </section>

        <!-- Setup Steps -->
        <section class="py-16" aria-labelledby="setup-heading">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <h2 id="setup-heading" class="text-2xl font-bold tracking-tight sm:text-3xl">
                    Setup guide
                </h2>
                <p class="mt-3 text-muted-foreground">
                    Get up and running in under two minutes.
                </p>

                <div class="mt-10 space-y-10">

                    <!-- Step 1 -->
                    <div class="relative pl-12">
                        <div class="absolute left-0 top-0 flex h-8 w-8 items-center justify-center rounded-full bg-primary text-sm font-bold text-primary-foreground">
                            1
                        </div>
                        <div class="absolute left-[15px] top-9 bottom-0 w-px bg-border"></div>

                        <div>
                            <h3 class="text-lg font-semibold">Install an MCP-compatible client</h3>
                            <p class="mt-1 text-muted-foreground">
                                Any client that supports the Model Context Protocol will work. Popular options include:
                            </p>
                            <div class="mt-3 flex flex-wrap gap-3">
                                <a
                                    v-for="client in [
                                        { name: 'Claude Desktop', href: 'https://claude.ai/download' },
                                        { name: 'Cursor', href: 'https://cursor.com' },
                                        { name: 'Windsurf', href: 'https://windsurf.com' },
                                    ]"
                                    :key="client.name"
                                    :href="client.href"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex items-center gap-1.5 text-sm font-medium text-primary transition-colors hover:text-primary/80"
                                >
                                    <MonitorSmartphone class="h-4 w-4" aria-hidden="true" />
                                    {{ client.name }}
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2 -->
                    <div class="relative pl-12">
                        <div class="absolute left-0 top-0 flex h-8 w-8 items-center justify-center rounded-full bg-primary text-sm font-bold text-primary-foreground">
                            2
                        </div>
                        <div class="absolute left-[15px] top-9 bottom-0 w-px bg-border"></div>

                        <div>
                            <h3 class="text-lg font-semibold">Add the MCP server config</h3>
                            <p class="mt-1 text-muted-foreground">
                                Add the following to your client's MCP configuration. In Claude Desktop, go to <strong>Settings &rarr; Developer &rarr; Edit Config</strong>. Other clients may have different configuration locations.
                            </p>
                            <div class="group relative mt-4">
                                <div class="flex items-center justify-between rounded-t-lg border border-b-0 bg-muted/60 px-4 py-2">
                                    <span class="text-xs font-medium text-muted-foreground">claude_desktop_config.json</span>
                                    <button
                                        class="flex items-center gap-1.5 rounded-md px-2 py-1 text-xs text-muted-foreground transition-colors hover:bg-background hover:text-foreground"
                                        @click="copyToClipboard(configJson, 'config')"
                                    >
                                        <Check v-if="copied === 'config'" class="h-3.5 w-3.5 text-green-600" aria-hidden="true" />
                                        <Copy v-else class="h-3.5 w-3.5" aria-hidden="true" />
                                        {{ copied === 'config' ? 'Copied' : 'Copy' }}
                                    </button>
                                </div>
                                <pre class="overflow-x-auto rounded-b-lg border bg-muted/30 p-4 font-mono text-sm leading-relaxed">{{ configJson }}</pre>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3 -->
                    <div class="relative pl-12">
                        <div class="absolute left-0 top-0 flex h-8 w-8 items-center justify-center rounded-full bg-primary text-sm font-bold text-primary-foreground">
                            3
                        </div>
                        <div class="absolute left-[15px] top-9 bottom-0 w-px bg-border"></div>

                        <div>
                            <h3 class="text-lg font-semibold">Authorize in your browser</h3>
                            <p class="mt-1 text-muted-foreground">
                                When you first use a GeoSource tool, your browser will open automatically to authorize the connection. Sign in to your GeoSource account and click <strong>Authorize</strong>.
                            </p>
                            <Card class="mt-4 border-l-4 border-l-primary">
                                <CardContent class="flex items-start gap-3 p-4">
                                    <Shield class="mt-0.5 h-5 w-5 shrink-0 text-primary" aria-hidden="true" />
                                    <div class="space-y-1 text-sm">
                                        <p class="font-medium">Secure OAuth flow</p>
                                        <p class="text-muted-foreground">
                                            Your credentials never leave the browser. The MCP server receives a scoped API token that can only access your GeoSource data. You can revoke access anytime from your account settings.
                                        </p>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </div>

                    <!-- Step 4 -->
                    <div class="relative pl-12">
                        <div class="absolute left-0 top-0 flex h-8 w-8 items-center justify-center rounded-full bg-primary text-sm font-bold text-primary-foreground">
                            4
                        </div>

                        <div>
                            <h3 class="text-lg font-semibold">Start using GeoSource</h3>
                            <p class="mt-1 text-muted-foreground">
                                That's it. Ask your AI assistant to run a scan, check your citations, or review your dashboard. Here are some things to try:
                            </p>
                            <div class="mt-4 space-y-2">
                                <div
                                    v-for="(prompt, i) in [
                                        'Run a GEO scan on https://example.com',
                                        'Show me my recent scan results',
                                        'Check my citation status across AI platforms',
                                        'What\'s my current token balance?',
                                    ]"
                                    :key="i"
                                    class="flex items-center gap-2 rounded-lg border bg-muted/20 px-4 py-3 text-sm"
                                >
                                    <MousePointerClick class="h-3.5 w-3.5 shrink-0 text-muted-foreground" aria-hidden="true" />
                                    <span class="italic text-muted-foreground">"{{ prompt }}"</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <Separator class="mx-auto max-w-4xl" />

        <!-- Available Tools -->
        <section class="py-16" aria-labelledby="tools-heading">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <h2 id="tools-heading" class="text-2xl font-bold tracking-tight sm:text-3xl">
                    Available tools
                </h2>
                <p class="mt-3 text-muted-foreground">
                    The MCP server exposes these capabilities to any MCP-compatible client:
                </p>
                <div class="mt-8 grid gap-4 sm:grid-cols-2">
                    <Card
                        v-for="tool in tools"
                        :key="tool.name"
                        class="transition-colors hover:bg-muted/30"
                    >
                        <CardContent class="flex items-start gap-3 p-6">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary/10">
                                <component :is="tool.icon" class="h-5 w-5 text-primary" aria-hidden="true" />
                            </div>
                            <div>
                                <p class="font-semibold">{{ tool.name }}</p>
                                <p class="mt-1 text-sm text-muted-foreground">{{ tool.description }}</p>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </section>

        <Separator class="mx-auto max-w-4xl" />

        <!-- Troubleshooting -->
        <section class="py-16" aria-labelledby="troubleshooting-heading">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <h2 id="troubleshooting-heading" class="text-2xl font-bold tracking-tight sm:text-3xl">
                    Troubleshooting
                </h2>
                <div class="mt-8 space-y-6">
                    <Card>
                        <CardContent class="p-6">
                            <h3 class="font-semibold">The browser doesn't open for authorization</h3>
                            <p class="mt-2 text-sm text-muted-foreground">
                                Make sure you're logged in to GeoSource before using the MCP server. The auth page requires an active session. If the browser still doesn't open, try restarting your MCP client.
                            </p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent class="p-6">
                            <h3 class="font-semibold">Tools aren't appearing in your client</h3>
                            <p class="mt-2 text-sm text-muted-foreground">
                                Fully quit and reopen your MCP client after saving your config. Check that <code class="rounded bg-muted px-1.5 py-0.5 text-xs">npx</code> is available in your system PATH.
                            </p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent class="p-6">
                            <h3 class="font-semibold">Want to re-authorize or switch accounts?</h3>
                            <p class="mt-2 text-sm text-muted-foreground">
                                Delete the stored token file at <code class="rounded bg-muted px-1.5 py-0.5 text-xs">~/.geosource-mcp/config.json</code> and restart your MCP client. The browser auth flow will trigger again on next use.
                            </p>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </section>

        <Separator class="mx-auto max-w-4xl" />

        <!-- CTA Section -->
        <section class="py-20" aria-labelledby="cta-heading">
            <div class="mx-auto max-w-4xl px-4 text-center sm:px-6 lg:px-8">
                <h2 id="cta-heading" class="text-2xl font-bold tracking-tight sm:text-3xl">
                    Ready to connect GeoSource via MCP?
                </h2>
                <p class="mx-auto mt-4 max-w-2xl text-muted-foreground">
                    Create a free account to get started. Run GEO scans, track AI citations, and monitor your performance directly from your preferred MCP client.
                </p>
                <div class="mt-8">
                    <Link :href="register().url">
                        <Button size="lg">
                            Get started free
                            <ArrowRight class="ml-2 h-4 w-4" aria-hidden="true" />
                        </Button>
                    </Link>
                </div>
            </div>
        </section>
    </main>

    <ResourceFooter />

    <!-- JSON-LD Structured Data -->
    <component :is="'script'" type="application/ld+json">{{ JSON.stringify(articleJsonLd) }}</component>
    <component :is="'script'" type="application/ld+json">{{ JSON.stringify(faqJsonLd) }}</component>
</template>
