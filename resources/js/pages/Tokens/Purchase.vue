<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import axios from 'axios';
import { Coins, Sparkles, TrendingUp, Zap, Check, Gift, Ticket } from 'lucide-vue-next';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Alert, AlertDescription } from '@/components/ui/alert';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

interface TokenPackage {
    id: number;
    name: string;
    tokens: number;
    price: number;
    formatted_price: string;
    price_per_token: number;
    savings_percent: number;
    is_popular: boolean;
}

interface Props {
    packages: TokenPackage[];
    balance: number;
    costs: Record<string, number>;
    labels: Record<string, string>;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Tokens',
        href: '/tokens',
    },
];

const processing = ref(false);
const processingPackageId = ref<number | null>(null);
const error = ref<string | null>(null);

const purchasePackage = async (packageId: number) => {
    processing.value = true;
    processingPackageId.value = packageId;
    error.value = null;

    try {
        const response = await axios.post('/tokens/checkout', {
            package_id: packageId,
        });

        if (response.data.checkout_url) {
            window.location.href = response.data.checkout_url;
        }
    } catch (err: any) {
        error.value = err.response?.data?.error || 'An error occurred. Please try again.';
        processing.value = false;
        processingPackageId.value = null;
    }
};

const getPackageIcon = (tokens: number) => {
    if (tokens >= 1000) return TrendingUp;
    if (tokens >= 500) return Zap;
    if (tokens >= 200) return Sparkles;
    return Coins;
};

const getPackageColor = (tokens: number) => {
    if (tokens >= 1000) return 'purple';
    if (tokens >= 500) return 'blue';
    if (tokens >= 200) return 'green';
    return 'gray';
};

const tokenUseCases = [
    // Scans
    { feature: 'scan_pro', description: 'Pro Scan (8 pillars)' },
    { feature: 'scan_full', description: 'Full Scan (12 pillars)' },
    // AI Citations (sorted by token cost descending)
    { feature: 'citation_chatgpt', description: 'ChatGPT Citation' },
    { feature: 'citation_claude', description: 'Claude Citation' },
    { feature: 'citation_perplexity', description: 'Perplexity Citation' },
    { feature: 'citation_gemini', description: 'Gemini Citation' },
    { feature: 'citation_deepseek', description: 'DeepSeek Citation' },
    // Search Citations
    { feature: 'citation_google', description: 'Google Search Citation' },
    { feature: 'citation_youtube', description: 'YouTube Citation' },
    { feature: 'citation_facebook', description: 'Facebook Citation' },
    // Other Features
    { feature: 'pdf_export', description: 'PDF Export' },
    { feature: 'scheduled_scan', description: 'Scheduled Scan (per run)' },
];

// Code redemption
const redeemForm = useForm({
    code: '',
});

const page = usePage();
const successMessage = computed(() => page.props.flash?.success as string | undefined);

const redeemCode = () => {
    redeemForm.post('/tokens/redeem', {
        preserveScroll: true,
        onSuccess: () => {
            redeemForm.reset();
        },
    });
};
</script>

<template>
    <Head title="Buy Tokens" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-8 p-6">
            <!-- Header -->
            <div class="text-center">
                <h1 class="text-3xl font-bold tracking-tight text-foreground">Buy Tokens</h1>
                <p class="mt-2 text-muted-foreground">
                    Purchase tokens to use for scans, citations, and exports. No subscription required.
                </p>
                <div class="mt-4 inline-flex items-center gap-2 rounded-full bg-primary/10 px-4 py-2">
                    <Coins class="h-5 w-5 text-primary" />
                    <span class="font-semibold text-primary">Your Balance: {{ balance }} tokens</span>
                </div>
            </div>

            <!-- Error Message -->
            <div v-if="error" class="mx-auto max-w-md">
                <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-center text-sm text-red-600 dark:border-red-800 dark:bg-red-500/10 dark:text-red-400">
                    {{ error }}
                </div>
            </div>

            <!-- Package Cards -->
            <div class="mx-auto grid max-w-5xl gap-6 pt-4 md:grid-cols-2 lg:grid-cols-4">
                <Card
                    v-for="pkg in packages"
                    :key="pkg.id"
                    class="relative flex flex-col transition-all hover:shadow-lg"
                    :class="{
                        'border-primary ring-2 ring-primary': pkg.is_popular,
                        'border-border': !pkg.is_popular,
                    }"
                >
                    <Badge
                        v-if="pkg.is_popular"
                        class="absolute -top-3 left-1/2 -translate-x-1/2"
                        variant="default"
                    >
                        Most Popular
                    </Badge>
                    <Badge
                        v-else-if="pkg.savings_percent >= 40"
                        class="absolute -top-3 left-1/2 -translate-x-1/2 bg-green-500"
                    >
                        Best Value
                    </Badge>

                    <CardHeader class="pb-4 text-center">
                        <div class="mx-auto mb-2 rounded-full p-3"
                            :class="{
                                'bg-purple-100 dark:bg-purple-500/10': getPackageColor(pkg.tokens) === 'purple',
                                'bg-blue-100 dark:bg-blue-500/10': getPackageColor(pkg.tokens) === 'blue',
                                'bg-green-100 dark:bg-green-500/10': getPackageColor(pkg.tokens) === 'green',
                                'bg-gray-100 dark:bg-gray-800': getPackageColor(pkg.tokens) === 'gray',
                            }"
                        >
                            <component
                                :is="getPackageIcon(pkg.tokens)"
                                class="h-8 w-8"
                                :class="{
                                    'text-purple-600 dark:text-purple-400': getPackageColor(pkg.tokens) === 'purple',
                                    'text-blue-600 dark:text-blue-400': getPackageColor(pkg.tokens) === 'blue',
                                    'text-green-600 dark:text-green-400': getPackageColor(pkg.tokens) === 'green',
                                    'text-gray-600 dark:text-gray-400': getPackageColor(pkg.tokens) === 'gray',
                                }"
                            />
                        </div>
                        <CardTitle class="text-2xl">{{ pkg.tokens }}</CardTitle>
                        <CardDescription>tokens</CardDescription>
                    </CardHeader>

                    <CardContent class="flex flex-1 flex-col text-center">
                        <div class="mb-4">
                            <span class="text-3xl font-bold tracking-tight text-foreground">{{ pkg.formatted_price }}</span>
                        </div>

                        <div class="mb-4 space-y-1 text-sm text-muted-foreground">
                            <p>${{ pkg.price_per_token.toFixed(3) }} per token</p>
                            <p v-if="pkg.savings_percent > 0" class="font-medium text-green-600 dark:text-green-400">
                                Save {{ pkg.savings_percent }}%
                            </p>
                        </div>

                        <Button
                            class="mt-auto w-full"
                            :variant="pkg.is_popular ? 'default' : 'outline'"
                            :disabled="processing"
                            @click="purchasePackage(pkg.id)"
                        >
                            <span v-if="processing && processingPackageId === pkg.id">Processing...</span>
                            <span v-else>Buy Now</span>
                        </Button>
                    </CardContent>
                </Card>
            </div>

            <!-- Token Costs Reference -->
            <div class="mx-auto w-full max-w-3xl pt-8">
                <h2 class="mb-6 text-center text-2xl font-bold">What Can You Do With Tokens?</h2>
                <Card>
                    <CardContent class="pt-6">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div
                                v-for="useCase in tokenUseCases"
                                :key="useCase.feature"
                                class="flex items-center justify-between rounded-lg border p-3"
                            >
                                <div class="flex items-center gap-3">
                                    <Check class="h-4 w-4 text-green-500" />
                                    <span class="text-sm">{{ useCase.description }}</span>
                                </div>
                                <Badge variant="secondary">
                                    {{ costs[useCase.feature] }} {{ costs[useCase.feature] === 1 ? 'token' : 'tokens' }}
                                </Badge>
                            </div>
                        </div>

                        <div class="mt-6 rounded-lg bg-muted/50 p-4 text-center">
                            <p class="text-sm text-muted-foreground">
                                <strong>Basic Scans (5 pillars)</strong> are always <strong class="text-green-600">FREE</strong> - no tokens required!
                            </p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Redeem Code Section -->
            <div class="mx-auto w-full max-w-md pt-8">
                <Card>
                    <CardHeader class="text-center">
                        <div class="mx-auto mb-2 rounded-full bg-primary/10 p-3">
                            <Ticket class="h-6 w-6 text-primary" />
                        </div>
                        <CardTitle>Have a Code?</CardTitle>
                        <CardDescription>
                            Enter a promo code or gift code to redeem free tokens
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <!-- Success Message -->
                        <Alert v-if="successMessage" class="mb-4 border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-500/10">
                            <Gift class="h-4 w-4 text-green-600" />
                            <AlertDescription class="text-green-700 dark:text-green-300">
                                {{ successMessage }}
                            </AlertDescription>
                        </Alert>

                        <form @submit.prevent="redeemCode" class="space-y-4">
                            <div class="space-y-2">
                                <Label for="code">Token Code</Label>
                                <Input
                                    id="code"
                                    v-model="redeemForm.code"
                                    placeholder="Enter your code"
                                    class="uppercase"
                                    :disabled="redeemForm.processing"
                                />
                                <p v-if="redeemForm.errors.code" class="text-sm text-red-600 dark:text-red-400">
                                    {{ redeemForm.errors.code }}
                                </p>
                            </div>
                            <Button
                                type="submit"
                                class="w-full"
                                :disabled="redeemForm.processing || !redeemForm.code.trim()"
                            >
                                <Gift v-if="!redeemForm.processing" class="mr-2 h-4 w-4" />
                                {{ redeemForm.processing ? 'Redeeming...' : 'Redeem Code' }}
                            </Button>
                        </form>
                    </CardContent>
                </Card>
            </div>

            <!-- FAQ Section -->
            <div class="mx-auto w-full max-w-3xl pt-4">
                <h2 class="mb-6 text-center text-2xl font-bold">Frequently Asked Questions</h2>
                <div class="space-y-4">
                    <Card>
                        <CardHeader>
                            <CardTitle class="text-base">Do tokens expire?</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p class="text-sm text-muted-foreground">
                                No, your tokens never expire. Use them whenever you need them.
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle class="text-base">Can I get a refund?</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p class="text-sm text-muted-foreground">
                                Unused tokens can be refunded within 30 days of purchase. Contact support for assistance.
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle class="text-base">What's the difference between Basic, Pro, and Full scans?</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p class="text-sm text-muted-foreground">
                                Basic scans analyze 5 GEO pillars, Pro scans analyze 8 pillars, and Full scans analyze all 12 pillars for comprehensive optimization insights.
                            </p>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
