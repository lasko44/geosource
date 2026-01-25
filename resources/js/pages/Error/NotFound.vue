<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import ThemeSwitcher from '@/components/ThemeSwitcher.vue';
import {
    Home,
    LayoutDashboard,
    BookOpen,
    Search,
    ArrowRight,
    Globe,
    Brain,
    FileText,
} from 'lucide-vue-next';
import { computed } from 'vue';

const page = usePage();
const isAuthenticated = computed(() => !!page.props.auth?.user);

const resources = [
    {
        title: 'What is GEO?',
        description: 'Learn how Generative Engine Optimization helps AI understand your content.',
        icon: Brain,
        href: '/blog',
    },
    {
        title: 'Start Scanning',
        description: 'Analyze your website and get actionable recommendations.',
        icon: Search,
        href: '/register',
        authHref: '/dashboard',
    },
    {
        title: 'Read Our Blog',
        description: 'Tips, guides, and insights on optimizing for AI.',
        icon: BookOpen,
        href: '/blog',
    },
];
</script>

<template>
    <Head title="Page Not Found" />

    <div class="relative flex min-h-svh flex-col items-center justify-center bg-background p-6 md:p-10">
        <!-- Theme Switcher -->
        <div class="absolute right-4 top-4">
            <ThemeSwitcher />
        </div>

        <div class="w-full max-w-2xl">
            <!-- Logo and Branding -->
            <div class="mb-8 flex flex-col items-center">
                <Link href="/" class="flex items-center gap-2 text-primary hover:opacity-80 transition-opacity">
                    <AppLogoIcon class="h-10 w-10" />
                    <span class="text-xl font-semibold">GeoSource</span>
                </Link>
            </div>

            <!-- 404 Content -->
            <div class="text-center mb-10">
                <div class="mb-6">
                    <span class="text-8xl font-bold text-primary/20">404</span>
                </div>
                <h1 class="text-3xl font-bold tracking-tight mb-3">
                    Page not found
                </h1>
                <p class="text-lg text-muted-foreground max-w-md mx-auto">
                    The page you're looking for doesn't exist or has been moved.
                    Let's get you back on track.
                </p>
            </div>

            <!-- Quick Actions -->
            <div class="flex flex-wrap justify-center gap-3 mb-10">
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
                <Link v-else href="/register">
                    <Button size="lg">
                        <Globe class="mr-2 h-4 w-4" />
                        Get Started Free
                    </Button>
                </Link>
            </div>

            <!-- Marketing Section - Resources -->
            <div class="border-t pt-10">
                <h2 class="text-center text-sm font-medium text-muted-foreground uppercase tracking-wider mb-6">
                    While you're here, explore GEO
                </h2>
                <div class="grid gap-4 sm:grid-cols-3">
                    <Link
                        v-for="resource in resources"
                        :key="resource.title"
                        :href="isAuthenticated && resource.authHref ? resource.authHref : resource.href"
                        class="group"
                    >
                        <Card class="h-full transition-all hover:shadow-md hover:border-primary/50">
                            <CardHeader class="pb-2">
                                <div class="flex items-center gap-3">
                                    <div class="rounded-lg bg-primary/10 p-2 text-primary group-hover:bg-primary group-hover:text-primary-foreground transition-colors">
                                        <component :is="resource.icon" class="h-5 w-5" />
                                    </div>
                                    <CardTitle class="text-base">{{ resource.title }}</CardTitle>
                                </div>
                            </CardHeader>
                            <CardContent>
                                <CardDescription class="text-sm">
                                    {{ resource.description }}
                                </CardDescription>
                            </CardContent>
                        </Card>
                    </Link>
                </div>
            </div>

            <!-- Bottom CTA for non-authenticated users -->
            <div v-if="!isAuthenticated" class="mt-10 text-center">
                <p class="text-sm text-muted-foreground mb-4">
                    Ready to optimize your content for AI?
                </p>
                <Link href="/register">
                    <Button variant="default" size="lg" class="gap-2">
                        Start Your Free GEO Scan
                        <ArrowRight class="h-4 w-4" />
                    </Button>
                </Link>
            </div>

            <!-- Footer -->
            <div class="mt-12 text-center text-sm text-muted-foreground">
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
