<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import {
    CheckCircle,
    ChevronDown,
    ChevronUp,
    Info,
    ArrowRight,
    Sparkles,
    Target,
    ExternalLink,
} from 'lucide-vue-next';
import SkipNav from '@/components/resources/SkipNav.vue';
import ResourceHeader from '@/components/resources/ResourceHeader.vue';
import ResourceFooter from '@/components/resources/ResourceFooter.vue';
import ResourceBreadcrumb from '@/components/resources/ResourceBreadcrumb.vue';
import SuggestedContent from '@/components/resources/SuggestedContent.vue';
import { register } from '@/routes';

interface Platform {
    slug: string;
    name: string;
    title: string;
    meta_description: string;
    color: string;
    hero_headline: string;
    hero_description: string;
    how_it_works: string;
    ranking_factors: { factor: string; description: string }[];
    checklist: string[];
    faq: { question: string; answer: string }[];
}

interface PlatformLink {
    slug: string;
    name: string;
}

const props = defineProps<{
    platform: Platform;
    allPlatforms: PlatformLink[];
}>();

const breadcrumbItems = [
    { label: 'Resources', href: '/resources' },
    { label: `Optimize for ${props.platform.name}` },
];

const canonicalUrl = computed(() => `https://geosource.ai/optimize-for-${props.platform.slug}`);

const openFaqIndex = ref<number | null>(null);

const toggleFaq = (index: number): void => {
    openFaqIndex.value = openFaqIndex.value === index ? null : index;
};

const otherPlatforms = computed(() =>
    props.allPlatforms.filter((p) => p.slug !== props.platform.slug),
);

const badgeColorClass = computed(() => {
    const colorMap: Record<string, string> = {
        emerald: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400',
        teal: 'bg-teal-100 text-teal-800 dark:bg-teal-900/30 dark:text-teal-400',
        amber: 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400',
        blue: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
        violet: 'bg-violet-100 text-violet-800 dark:bg-violet-900/30 dark:text-violet-400',
    };
    return colorMap[props.platform.color] ?? colorMap.blue;
});

const accentBorderClass = computed(() => {
    const colorMap: Record<string, string> = {
        emerald: 'border-l-emerald-500',
        teal: 'border-l-teal-500',
        amber: 'border-l-amber-500',
        blue: 'border-l-blue-500',
        violet: 'border-l-violet-500',
    };
    return colorMap[props.platform.color] ?? colorMap.blue;
});

const articleJsonLd = computed(() => ({
    '@context': 'https://schema.org',
    '@type': 'Article',
    headline: props.platform.title,
    description: props.platform.meta_description,
    url: canonicalUrl.value,
    publisher: {
        '@type': 'Organization',
        name: 'GeoSource.ai',
        url: 'https://geosource.ai',
    },
}));

const faqJsonLd = computed(() => ({
    '@context': 'https://schema.org',
    '@type': 'FAQPage',
    mainEntity: props.platform.faq.map((item) => ({
        '@type': 'Question',
        name: item.question,
        acceptedAnswer: {
            '@type': 'Answer',
            text: item.answer,
        },
    })),
}));
</script>

<template>
    <Head>
        <title>{{ platform.title }}</title>
        <meta name="description" :content="platform.meta_description" />
        <link rel="canonical" :href="canonicalUrl" />
        <meta property="og:title" :content="platform.title" />
        <meta property="og:description" :content="platform.meta_description" />
        <meta property="og:url" :content="canonicalUrl" />
        <meta property="og:type" content="article" />
        <meta property="og:site_name" content="GeoSource.ai" />
    </Head>

    <SkipNav />
    <ResourceHeader />

    <main id="main-content">
        <ResourceBreadcrumb :items="breadcrumbItems" />

        <!-- Hero Section -->
        <section class="border-b bg-gradient-to-b from-muted/50 to-background py-16 sm:py-20">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col items-start gap-6">
                    <Badge variant="secondary" :class="badgeColorClass">
                        {{ platform.name }}
                    </Badge>
                    <h1 class="text-3xl font-bold tracking-tight sm:text-4xl lg:text-5xl">
                        {{ platform.hero_headline }}
                    </h1>
                    <p class="max-w-2xl text-lg text-muted-foreground sm:text-xl">
                        {{ platform.hero_description }}
                    </p>
                    <div class="flex flex-wrap gap-3">
                        <Link :href="register().url">
                            <Button size="lg">
                                <Sparkles class="mr-2 h-4 w-4" aria-hidden="true" />
                                Check your {{ platform.name }} readiness
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>
        </section>

        <!-- How it works section -->
        <section class="py-16" aria-labelledby="how-it-works-heading">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <h2 id="how-it-works-heading" class="text-2xl font-bold tracking-tight sm:text-3xl">
                    How {{ platform.name }} discovers content
                </h2>
                <Card class="mt-8 border-l-4" :class="accentBorderClass">
                    <CardContent class="flex gap-4 p-6">
                        <div class="flex-shrink-0">
                            <Info class="h-6 w-6 text-muted-foreground" aria-hidden="true" />
                        </div>
                        <p class="text-base leading-relaxed text-muted-foreground">
                            {{ platform.how_it_works }}
                        </p>
                    </CardContent>
                </Card>
            </div>
        </section>

        <Separator class="mx-auto max-w-4xl" />

        <!-- Ranking Factors section -->
        <section class="py-16" aria-labelledby="ranking-factors-heading">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <h2 id="ranking-factors-heading" class="text-2xl font-bold tracking-tight sm:text-3xl">
                    What {{ platform.name }} looks for in content
                </h2>
                <p class="mt-3 text-muted-foreground">
                    Key factors that influence how {{ platform.name }} evaluates and surfaces your content.
                </p>
                <div class="mt-8 grid gap-4 sm:grid-cols-2">
                    <Card
                        v-for="(item, index) in platform.ranking_factors"
                        :key="index"
                        class="transition-colors hover:bg-muted/30"
                    >
                        <CardContent class="p-6">
                            <div class="flex items-start gap-3">
                                <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-primary/10">
                                    <Target class="h-4 w-4 text-primary" aria-hidden="true" />
                                </div>
                                <div>
                                    <h3 class="font-semibold">{{ item.factor }}</h3>
                                    <p class="mt-1 text-sm text-muted-foreground">{{ item.description }}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </section>

        <Separator class="mx-auto max-w-4xl" />

        <!-- Optimization Checklist section -->
        <section class="py-16" aria-labelledby="checklist-heading">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <h2 id="checklist-heading" class="text-2xl font-bold tracking-tight sm:text-3xl">
                    {{ platform.name }} optimization checklist
                </h2>
                <p class="mt-3 text-muted-foreground">
                    Action items to improve your visibility on {{ platform.name }}.
                </p>
                <Card class="mt-8">
                    <CardContent class="p-6">
                        <ul class="space-y-4" role="list">
                            <li
                                v-for="(item, index) in platform.checklist"
                                :key="index"
                                class="flex items-start gap-3"
                            >
                                <CheckCircle
                                    class="mt-0.5 h-5 w-5 flex-shrink-0 text-emerald-500 dark:text-emerald-400"
                                    aria-hidden="true"
                                />
                                <span class="text-sm leading-relaxed sm:text-base">{{ item }}</span>
                            </li>
                        </ul>
                    </CardContent>
                </Card>
            </div>
        </section>

        <Separator class="mx-auto max-w-4xl" />

        <!-- FAQ Section -->
        <section class="py-16" aria-labelledby="faq-heading">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <h2 id="faq-heading" class="text-2xl font-bold tracking-tight sm:text-3xl">
                    Frequently asked questions
                </h2>
                <div class="mt-8 space-y-3">
                    <div
                        v-for="(item, index) in platform.faq"
                        :key="index"
                        class="rounded-lg border transition-colors"
                        :class="openFaqIndex === index ? 'bg-muted/30' : ''"
                    >
                        <button
                            class="flex w-full items-center justify-between px-6 py-4 text-left focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 rounded-lg"
                            :aria-expanded="openFaqIndex === index"
                            :aria-controls="`faq-answer-${index}`"
                            @click="toggleFaq(index)"
                        >
                            <span class="pr-4 font-medium">{{ item.question }}</span>
                            <ChevronUp
                                v-if="openFaqIndex === index"
                                class="h-5 w-5 flex-shrink-0 text-muted-foreground"
                                aria-hidden="true"
                            />
                            <ChevronDown
                                v-else
                                class="h-5 w-5 flex-shrink-0 text-muted-foreground"
                                aria-hidden="true"
                            />
                        </button>
                        <div
                            v-show="openFaqIndex === index"
                            :id="`faq-answer-${index}`"
                            role="region"
                            class="px-6 pb-4"
                        >
                            <p class="text-sm leading-relaxed text-muted-foreground sm:text-base">
                                {{ item.answer }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <Separator class="mx-auto max-w-4xl" />

        <!-- Other Platforms Section -->
        <section class="py-16" aria-labelledby="other-platforms-heading">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <h2 id="other-platforms-heading" class="text-2xl font-bold tracking-tight sm:text-3xl">
                    Optimize for other platforms
                </h2>
                <p class="mt-3 text-muted-foreground">
                    Explore optimization guides for additional AI platforms.
                </p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <Link
                        v-for="other in otherPlatforms"
                        :key="other.slug"
                        :href="`/optimize-for-${other.slug}`"
                    >
                        <Button variant="outline" class="gap-2">
                            {{ other.name }}
                            <ExternalLink class="h-3.5 w-3.5" aria-hidden="true" />
                        </Button>
                    </Link>
                </div>
            </div>
        </section>

        <Separator class="mx-auto max-w-4xl" />

        <SuggestedContent :current-slug="'optimize-for-' + platform.slug" />

        <!-- CTA Section -->
        <section class="py-20" aria-labelledby="cta-heading">
            <div class="mx-auto max-w-4xl px-4 text-center sm:px-6 lg:px-8">
                <h2 id="cta-heading" class="text-2xl font-bold tracking-tight sm:text-3xl">
                    Check your {{ platform.name }} readiness
                </h2>
                <p class="mx-auto mt-4 max-w-2xl text-muted-foreground">
                    Run a GEO scan to see how your content performs on {{ platform.name }} and get
                    actionable recommendations to improve your AI search visibility.
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
