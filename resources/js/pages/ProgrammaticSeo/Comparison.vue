<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import SkipNav from '@/components/resources/SkipNav.vue';
import ResourceHeader from '@/components/resources/ResourceHeader.vue';
import ResourceFooter from '@/components/resources/ResourceFooter.vue';
import ResourceBreadcrumb from '@/components/resources/ResourceBreadcrumb.vue';
import SuggestedContent from '@/components/resources/SuggestedContent.vue';
import { Calendar, ChevronDown, ArrowRight, Scale, CheckCircle, Lightbulb } from 'lucide-vue-next';
import { register } from '@/routes';
import { ref, computed } from 'vue';

interface Comparison {
    slug: string;
    title: string;
    meta_description: string;
    published_at: string;
    updated_at: string;
    tool_a: string;
    tool_b: string;
    tool_b_description: string;
    focus_a: string;
    focus_b: string;
    comparison_rows: { feature: string; tool_a: string; tool_b: string }[];
    when_to_use_a: string;
    when_to_use_b: string;
    why_both: string;
    faq: { question: string; answer: string }[];
}

interface ComparisonLink {
    slug: string;
    title: string;
}

const props = defineProps<{
    comparison: Comparison;
    allComparisons: ComparisonLink[];
}>();

const canonicalUrl = computed(() => `https://geosource.ai/compare/${props.comparison.slug}`);

const breadcrumbItems = computed(() => [
    { label: 'Resources', href: '/resources' },
    { label: props.comparison.title },
]);

const otherComparisons = computed(() =>
    props.allComparisons.filter((c) => c.slug !== props.comparison.slug),
);

const openFaqIndex = ref<number | null>(null);
const toggleFaq = (index: number): void => {
    openFaqIndex.value = openFaqIndex.value === index ? null : index;
};

const formatDate = (dateStr: string) => {
    const d = new Date(dateStr + 'T00:00:00');
    return d.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
};

const articleJsonLd = computed(() =>
    JSON.stringify({
        '@context': 'https://schema.org',
        '@type': 'Article',
        headline: props.comparison.title,
        description: props.comparison.meta_description,
        url: canonicalUrl.value,
        datePublished: props.comparison.published_at,
        dateModified: props.comparison.updated_at,
        publisher: {
            '@type': 'Organization',
            name: 'GeoSource.ai',
            url: 'https://geosource.ai',
        },
    }),
);

const faqJsonLd = computed(() =>
    JSON.stringify({
        '@context': 'https://schema.org',
        '@type': 'FAQPage',
        mainEntity: props.comparison.faq.map((item) => ({
            '@type': 'Question',
            name: item.question,
            acceptedAnswer: {
                '@type': 'Answer',
                text: item.answer,
            },
        })),
    }),
);
</script>

<template>
    <Head>
        <title>{{ comparison.title }} | GeoSource.ai</title>
        <meta name="description" :content="comparison.meta_description" />
        <link rel="canonical" :href="canonicalUrl" />
        <meta property="og:title" :content="comparison.title" />
        <meta property="og:description" :content="comparison.meta_description" />
        <meta property="og:url" :content="canonicalUrl" />
        <meta property="og:type" content="article" />
        <meta property="og:site_name" content="GeoSource.ai" />
        <component :is="'script'" type="application/ld+json">{{ articleJsonLd }}</component>
        <component :is="'script'" type="application/ld+json">{{ faqJsonLd }}</component>
    </Head>

    <div class="min-h-screen bg-background text-foreground">
        <SkipNav />
        <ResourceHeader />

        <main id="main-content">
            <ResourceBreadcrumb :items="breadcrumbItems" />

            <!-- Hero Section -->
            <section class="border-b bg-gradient-to-b from-primary/5 to-background py-16 sm:py-20">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <Badge variant="secondary" class="mb-4">
                        <Scale class="mr-1.5 h-3 w-3" aria-hidden="true" />
                        Comparison
                    </Badge>
                    <h1 class="text-3xl font-bold tracking-tight sm:text-4xl lg:text-5xl">
                        {{ comparison.title }}
                    </h1>
                    <p class="mt-4 max-w-2xl text-lg text-muted-foreground sm:text-xl">
                        An in-depth comparison of {{ comparison.tool_a }} and {{ comparison.tool_b }} to help you choose the right tool for your GEO strategy.
                    </p>
                    <div class="mt-4 flex items-center gap-4 text-sm text-muted-foreground">
                        <span class="flex items-center gap-1.5">
                            <Calendar class="h-3.5 w-3.5" aria-hidden="true" />
                            Published {{ formatDate(comparison.published_at) }}
                        </span>
                        <span>&middot;</span>
                        <span>Updated {{ formatDate(comparison.updated_at) }}</span>
                    </div>
                </div>
            </section>

            <!-- At a Glance Section -->
            <section class="py-12 sm:py-16">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <h2 class="mb-8 text-2xl font-bold tracking-tight sm:text-3xl">
                        At a glance
                    </h2>
                    <div class="grid gap-6 sm:grid-cols-2">
                        <Card class="transition-colors hover:border-primary/30">
                            <CardContent class="p-6">
                                <h3 class="mb-2 text-lg font-semibold">{{ comparison.tool_a }}</h3>
                                <p class="text-sm text-muted-foreground leading-relaxed">{{ comparison.focus_a }}</p>
                            </CardContent>
                        </Card>
                        <Card class="transition-colors hover:border-primary/30">
                            <CardContent class="p-6">
                                <h3 class="mb-2 text-lg font-semibold">{{ comparison.tool_b }}</h3>
                                <p class="mb-2 text-sm text-muted-foreground leading-relaxed">{{ comparison.tool_b_description }}</p>
                                <p class="text-sm text-muted-foreground leading-relaxed">{{ comparison.focus_b }}</p>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </section>

            <Separator />

            <!-- Feature Comparison Table Section -->
            <section class="py-12 sm:py-16">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <h2 class="mb-8 text-2xl font-bold tracking-tight sm:text-3xl">
                        Feature comparison
                    </h2>
                    <div class="overflow-x-auto rounded-lg border">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="border-b bg-muted/50">
                                    <th class="px-6 py-4 text-sm font-semibold">Feature</th>
                                    <th class="px-6 py-4 text-sm font-semibold">{{ comparison.tool_a }}</th>
                                    <th class="px-6 py-4 text-sm font-semibold">{{ comparison.tool_b }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(row, index) in comparison.comparison_rows"
                                    :key="index"
                                    class="border-b last:border-b-0 transition-colors hover:bg-muted/30"
                                >
                                    <td class="px-6 py-4 text-sm font-medium">{{ row.feature }}</td>
                                    <td class="px-6 py-4 text-sm text-muted-foreground">{{ row.tool_a }}</td>
                                    <td class="px-6 py-4 text-sm text-muted-foreground">{{ row.tool_b }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <Separator />

            <!-- When to Use Each Section -->
            <section class="py-12 sm:py-16">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <h2 class="mb-8 text-2xl font-bold tracking-tight sm:text-3xl">
                        When to use each tool
                    </h2>
                    <div class="grid gap-6 sm:grid-cols-2">
                        <div class="rounded-lg border p-6">
                            <div class="mb-4 flex items-center gap-3">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary/10">
                                    <CheckCircle class="h-5 w-5 text-primary" aria-hidden="true" />
                                </div>
                                <h3 class="text-lg font-semibold">When to use {{ comparison.tool_a }}</h3>
                            </div>
                            <p class="text-sm text-muted-foreground leading-relaxed">{{ comparison.when_to_use_a }}</p>
                        </div>
                        <div class="rounded-lg border p-6">
                            <div class="mb-4 flex items-center gap-3">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary/10">
                                    <CheckCircle class="h-5 w-5 text-primary" aria-hidden="true" />
                                </div>
                                <h3 class="text-lg font-semibold">When to use {{ comparison.tool_b }}</h3>
                            </div>
                            <p class="text-sm text-muted-foreground leading-relaxed">{{ comparison.when_to_use_b }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <Separator />

            <!-- Why Use Both Section -->
            <section class="py-12 sm:py-16">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary/10">
                            <Lightbulb class="h-5 w-5 text-primary" aria-hidden="true" />
                        </div>
                        <h2 class="text-2xl font-bold tracking-tight sm:text-3xl">
                            Why use both together
                        </h2>
                    </div>
                    <Card>
                        <CardContent class="p-6">
                            <p class="text-muted-foreground leading-relaxed">{{ comparison.why_both }}</p>
                        </CardContent>
                    </Card>
                </div>
            </section>

            <Separator />

            <!-- FAQ Section -->
            <section class="py-12 sm:py-16">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <h2 class="mb-8 text-2xl font-bold tracking-tight sm:text-3xl">
                        Frequently asked questions
                    </h2>
                    <div class="space-y-3">
                        <div
                            v-for="(item, index) in comparison.faq"
                            :key="index"
                            class="rounded-lg border"
                        >
                            <button
                                class="flex w-full items-center justify-between px-6 py-4 text-left font-medium transition-colors hover:bg-muted/50 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 rounded-lg"
                                :aria-expanded="openFaqIndex === index"
                                :aria-controls="`faq-panel-${index}`"
                                @click="toggleFaq(index)"
                            >
                                <span>{{ item.question }}</span>
                                <ChevronDown
                                    class="h-5 w-5 shrink-0 text-muted-foreground transition-transform duration-200"
                                    :class="{ 'rotate-180': openFaqIndex === index }"
                                    aria-hidden="true"
                                />
                            </button>
                            <div
                                v-show="openFaqIndex === index"
                                :id="`faq-panel-${index}`"
                                role="region"
                                class="px-6 pb-4"
                            >
                                <p class="text-muted-foreground leading-relaxed">{{ item.answer }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <Separator />

            <!-- More Comparisons Section -->
            <section v-if="otherComparisons.length > 0" class="py-12 sm:py-16">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <h2 class="mb-6 text-2xl font-bold tracking-tight sm:text-3xl">
                        More comparisons
                    </h2>
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        <Link
                            v-for="comp in otherComparisons"
                            :key="comp.slug"
                            :href="`/compare/${comp.slug}`"
                            class="group flex items-center justify-between rounded-lg border p-4 transition-colors hover:border-primary/30 hover:bg-muted/50 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
                        >
                            <span class="font-medium">{{ comp.title }}</span>
                            <ArrowRight class="h-4 w-4 text-muted-foreground transition-transform group-hover:translate-x-1 group-hover:text-primary" aria-hidden="true" />
                        </Link>
                    </div>
                </div>
            </section>

            <Separator v-if="otherComparisons.length > 0" />

            <SuggestedContent :current-slug="'compare-' + comparison.slug" />

            <!-- CTA Section -->
            <section class="py-16 sm:py-20">
                <div class="mx-auto max-w-4xl px-4 text-center sm:px-6 lg:px-8">
                    <h2 class="text-2xl font-bold tracking-tight sm:text-3xl">
                        Try GeoSource.ai free
                    </h2>
                    <p class="mx-auto mt-4 max-w-xl text-muted-foreground">
                        Get actionable insights to improve your AI visibility. See how your content performs across generative engines.
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
