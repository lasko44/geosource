<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import ThemeSwitcher from '@/components/ThemeSwitcher.vue';
import { Home, LayoutDashboard, RefreshCw } from 'lucide-vue-next';
import { computed } from 'vue';

const page = usePage();
const isAuthenticated = computed(() => !!page.props.auth?.user);
</script>

<template>
    <Head title="Something Went Wrong" />

    <div class="relative flex min-h-svh flex-col items-center justify-center bg-background p-6 md:p-10">
        <div class="absolute right-4 top-4">
            <ThemeSwitcher />
        </div>

        <div class="w-full max-w-lg text-center">
            <div class="mb-8 flex flex-col items-center">
                <Link href="/" class="flex items-center gap-2 text-primary hover:opacity-80 transition-opacity">
                    <AppLogoIcon class="h-10 w-10" />
                    <span class="text-xl font-semibold">GeoSource</span>
                </Link>
            </div>

            <div class="mb-10">
                <span class="text-8xl font-bold text-primary/20">500</span>
            </div>
            <h1 class="text-3xl font-bold tracking-tight mb-3">
                Something went wrong
            </h1>
            <p class="text-lg text-muted-foreground max-w-md mx-auto mb-8">
                We hit an unexpected error. Our team has been notified. Please try again.
            </p>

            <div class="flex flex-wrap justify-center gap-3">
                <Button variant="outline" size="lg" @click="() => window.location.reload()">
                    <RefreshCw class="mr-2 h-4 w-4" />
                    Try Again
                </Button>
                <Link href="/">
                    <Button variant="outline" size="lg">
                        <Home class="mr-2 h-4 w-4" />
                        Home
                    </Button>
                </Link>
                <Link v-if="isAuthenticated" href="/dashboard">
                    <Button size="lg">
                        <LayoutDashboard class="mr-2 h-4 w-4" />
                        Dashboard
                    </Button>
                </Link>
            </div>

            <div class="mt-12 text-sm text-muted-foreground">
                <p>
                    Need help?
                    <a href="mailto:support@geosource.ai" class="text-primary hover:underline">
                        Contact support
                    </a>
                </p>
            </div>
        </div>
    </div>
</template>
