<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Globe, Menu } from 'lucide-vue-next';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import ThemeSwitcher from '@/components/ThemeSwitcher.vue';
</script>

<template>
    <header role="banner" class="sticky top-0 z-50 border-b bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60">
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
            <Link href="/" class="flex items-center gap-2" aria-label="GeoSource.ai Home">
                <Globe class="h-8 w-8 text-primary" aria-hidden="true" />
                <span class="text-xl font-bold">GeoSource.ai</span>
            </Link>
            <!-- Desktop Navigation -->
            <nav aria-label="Main navigation" class="hidden items-center gap-2 sm:flex">
                <Link href="/pricing">
                    <Button variant="ghost">Pricing</Button>
                </Link>
                <Link href="/resources">
                    <Button variant="ghost">Resources</Button>
                </Link>
                <Link v-if="$page.props.auth.user" href="/dashboard">
                    <Button variant="outline">Dashboard</Button>
                </Link>
                <template v-else>
                    <Link href="/login">
                        <Button variant="ghost">Log in</Button>
                    </Link>
                    <Link href="/register">
                        <Button>Get Started</Button>
                    </Link>
                </template>
                <ThemeSwitcher />
            </nav>

            <!-- Mobile Navigation -->
            <div class="flex items-center gap-2 sm:hidden">
                <ThemeSwitcher />
                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <Button variant="ghost" size="icon" aria-label="Open navigation menu" aria-haspopup="true">
                            <Menu class="h-5 w-5" aria-hidden="true" />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="w-48" role="menu">
                        <DropdownMenuItem as-child role="menuitem">
                            <Link href="/pricing" class="w-full">
                                Pricing
                            </Link>
                        </DropdownMenuItem>
                        <DropdownMenuItem as-child role="menuitem">
                            <Link href="/resources" class="w-full">
                                Resources
                            </Link>
                        </DropdownMenuItem>
                        <DropdownMenuItem v-if="$page.props.auth.user" as-child role="menuitem">
                            <Link href="/dashboard" class="w-full">
                                Dashboard
                            </Link>
                        </DropdownMenuItem>
                        <template v-else>
                            <DropdownMenuItem as-child role="menuitem">
                                <Link href="/login" class="w-full">
                                    Log in
                                </Link>
                            </DropdownMenuItem>
                            <DropdownMenuItem as-child role="menuitem">
                                <Link href="/register" class="w-full font-medium text-primary">
                                    Get Started
                                </Link>
                            </DropdownMenuItem>
                        </template>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </div>
    </header>
</template>
