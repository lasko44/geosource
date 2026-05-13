<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import SkipNav from '@/components/resources/SkipNav.vue';
import ResourceHeader from '@/components/resources/ResourceHeader.vue';
import ResourceFooter from '@/components/resources/ResourceFooter.vue';
import { ArrowRight, Layers, Monitor, Scale } from 'lucide-vue-next';
import { register } from '@/routes';

defineProps<{
    industries: { slug: string; name: string; title: string; meta_description: string }[];
    platforms: { slug: string; name: string; title: string; meta_description: string }[];
    comparisons: { slug: string; title: string; meta_description: string }[];
}>();

const canonicalUrl = 'https://geosource.ai/guides';
</script>

<template>
    <Head>
        <title>GEO Guides &amp; Resources | GeoSource.ai</title>
        <meta name="description" content="Explore our comprehensive GEO optimization guides. Learn how to optimize your content for AI search engines across industries, platforms, and tools." />
        <link rel="canonical" :href="canonicalUrl" />
        <meta property="og:title" content="GEO Guides & Resources | GeoSource.ai" />
        <meta property="og:description" content="Explore our comprehensive GEO optimization guides. Learn how to optimize your content for AI search engines across industries, platforms, and tools." />
        <meta property="og:url" :content="canonicalUrl" />
        <meta property="og:type" content="website" />
        <meta property="og:site_name" content="GeoSource.ai" />
    </Head>

    <div class="min-h-screen bg-background text-foreground">
        <SkipNav />
        <ResourceHeader />

        <main id="main-content">
            <!-- Hero Section -->
            <section class="border-b bg-gradient-to-b from-primary/5 to-background py-16 sm:py-20">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 text-center">
                    <h1 class="text-3xl font-bold tracking-tight sm:text-4xl lg:text-5xl">
                        GEO Optimization Guides
                    </h1>
                    <p class="mx-auto mt-4 max-w-2xl text-lg text-muted-foreground sm:text-xl">
                        Your resource hub for generative engine optimization. Explore guides tailored to your industry, platform, and toolset.
                    </p>
                </div>
            </section>

            <!-- Industry GEO Guides Section -->
            <section class="py-12 sm:py-16">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex items-center gap-3 mb-8">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary/10">
                            <Layers class="h-5 w-5 text-primary" aria-hidden="true" />
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold tracking-tight sm:text-3xl">Industry GEO Guides</h2>
                            <p class="mt-1 text-sm text-muted-foreground">Optimization strategies tailored to your industry</p>
                        </div>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <Link
                            v-for="industry in industries"
                            :key="industry.slug"
                            :href="`/geo-for-${industry.slug}`"
                            class="group block focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 rounded-lg"
                            :aria-label="`Read GEO guide for ${industry.name}`"
                        >
                            <Card class="h-full transition-colors hover:border-primary/30">
                                <CardContent class="p-6">
                                    <Badge variant="secondary" class="mb-3">{{ industry.name }}</Badge>
                                    <h3 class="mb-2 font-semibold group-hover:text-primary transition-colors">
                                        {{ industry.title }}
                                    </h3>
                                    <p class="text-sm text-muted-foreground leading-relaxed line-clamp-2">
                                        {{ industry.meta_description }}
                                    </p>
                                    <span class="mt-3 inline-flex items-center text-sm text-primary" aria-hidden="true">
                                        Read guide
                                        <ArrowRight class="ml-1 h-3 w-3 transition-transform group-hover:translate-x-1" />
                                    </span>
                                </CardContent>
                            </Card>
                        </Link>
                    </div>
                </div>
            </section>

            <Separator />

            <!-- Platform Optimization Section -->
            <section class="py-12 sm:py-16">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex items-center gap-3 mb-8">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary/10">
                            <Monitor class="h-5 w-5 text-primary" aria-hidden="true" />
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold tracking-tight sm:text-3xl">Platform Optimization</h2>
                            <p class="mt-1 text-sm text-muted-foreground">Optimize your content for specific AI platforms</p>
                        </div>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <Link
                            v-for="platform in platforms"
                            :key="platform.slug"
                            :href="`/optimize-for-${platform.slug}`"
                            class="group block focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 rounded-lg"
                            :aria-label="`Read optimization guide for ${platform.name}`"
                        >
                            <Card class="h-full transition-colors hover:border-primary/30">
                                <CardContent class="p-6">
                                    <Badge variant="secondary" class="mb-3">{{ platform.name }}</Badge>
                                    <h3 class="mb-2 font-semibold group-hover:text-primary transition-colors">
                                        {{ platform.title }}
                                    </h3>
                                    <p class="text-sm text-muted-foreground leading-relaxed line-clamp-2">
                                        {{ platform.meta_description }}
                                    </p>
                                    <span class="mt-3 inline-flex items-center text-sm text-primary" aria-hidden="true">
                                        Read guide
                                        <ArrowRight class="ml-1 h-3 w-3 transition-transform group-hover:translate-x-1" />
                                    </span>
                                </CardContent>
                            </Card>
                        </Link>
                    </div>
                </div>
            </section>

            <Separator />

            <!-- Tool Comparisons Section -->
            <section class="py-12 sm:py-16">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex items-center gap-3 mb-8">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary/10">
                            <Scale class="h-5 w-5 text-primary" aria-hidden="true" />
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold tracking-tight sm:text-3xl">Tool Comparisons</h2>
                            <p class="mt-1 text-sm text-muted-foreground">Compare GEO tools to find the right fit</p>
                        </div>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <Link
                            v-for="comp in comparisons"
                            :key="comp.slug"
                            :href="`/compare/${comp.slug}`"
                            class="group block focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 rounded-lg"
                            :aria-label="`Read comparison: ${comp.title}`"
                        >
                            <Card class="h-full transition-colors hover:border-primary/30">
                                <CardContent class="p-6">
                                    <Badge variant="secondary" class="mb-3">Comparison</Badge>
                                    <h3 class="mb-2 font-semibold group-hover:text-primary transition-colors">
                                        {{ comp.title }}
                                    </h3>
                                    <p class="text-sm text-muted-foreground leading-relaxed line-clamp-2">
                                        {{ comp.meta_description }}
                                    </p>
                                    <span class="mt-3 inline-flex items-center text-sm text-primary" aria-hidden="true">
                                        Read comparison
                                        <ArrowRight class="ml-1 h-3 w-3 transition-transform group-hover:translate-x-1" />
                                    </span>
                                </CardContent>
                            </Card>
                        </Link>
                    </div>
                </div>
            </section>

            <Separator />

            <!-- CTA Section -->
            <section class="py-16 sm:py-20">
                <div class="mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8">
                    <h2 class="text-2xl font-bold tracking-tight sm:text-3xl">
                        Start your free GEO scan
                    </h2>
                    <p class="mx-auto mt-4 max-w-xl text-muted-foreground">
                        See how your content performs across generative AI engines. Get actionable recommendations to improve your visibility.
                    </p>
                    <div class="mt-8">
                        <Link :href="register().url">
                            <Button size="lg" class="gap-2">
                                Get started free
                                <ArrowRight class="h-4 w-4" aria-hidden="true" />
                            </Button>
                        </Link>
                    </div>
                </div>
            </section>
        </main>

        <ResourceFooter />
    </div>
</template>
