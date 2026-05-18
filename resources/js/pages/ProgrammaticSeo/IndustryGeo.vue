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
import { ChevronDown, Lightbulb, AlertTriangle, ArrowRight, Layers, Calendar } from 'lucide-vue-next';
import { register } from '@/routes';
import { ref, computed } from 'vue';

interface Industry {
    slug: string;
    name: string;
    title: string;
    meta_description: string;
    hero_headline: string;
    hero_description: string;
    published_at: string;
    updated_at: string;
    challenges: string[];
    tips: { title: string; description: string }[];
    faq: { question: string; answer: string }[];
    related_industries: string[];
}

interface IndustryLink {
    slug: string;
    name: string;
}

const props = defineProps<{
    industry: Industry;
    allIndustries: IndustryLink[];
}>();

const canonicalUrl = computed(() => `https://geosource.ai/geo-for-${props.industry.slug}`);

const breadcrumbItems = computed(() => [
    { label: 'Resources', href: '/resources' },
    { label: `GEO for ${props.industry.name}` },
]);

const relatedIndustryLinks = computed(() =>
    props.allIndustries.filter((i) => props.industry.related_industries.includes(i.slug)),
);

const otherIndustries = computed(() =>
    props.allIndustries.filter((i) => i.slug !== props.industry.slug),
);

const openFaqIndex = ref<number | null>(null);
const toggleFaq = (index: number) => {
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
        headline: props.industry.hero_headline,
        description: props.industry.meta_description,
        url: canonicalUrl.value,
        datePublished: props.industry.published_at,
        dateModified: props.industry.updated_at,
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
        mainEntity: props.industry.faq.map((item) => ({
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
        <title>{{ industry.title }}</title>
        <meta name="description" :content="industry.meta_description" />
        <link rel="canonical" :href="canonicalUrl" />
        <meta property="og:title" :content="industry.title" />
        <meta property="og:description" :content="industry.meta_description" />
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
                        <Layers class="mr-1.5 h-3 w-3" aria-hidden="true" />
                        {{ industry.name }}
                    </Badge>
                    <h1 class="text-3xl font-bold tracking-tight sm:text-4xl lg:text-5xl">
                        {{ industry.hero_headline }}
                    </h1>
                    <p class="mt-4 max-w-2xl text-lg text-muted-foreground sm:text-xl">
                        {{ industry.hero_description }}
                    </p>
                    <div class="mt-4 flex items-center gap-4 text-sm text-muted-foreground">
                        <span class="flex items-center gap-1.5">
                            <Calendar class="h-3.5 w-3.5" aria-hidden="true" />
                            Published {{ formatDate(industry.published_at) }}
                        </span>
                        <span>&middot;</span>
                        <span>Updated {{ formatDate(industry.updated_at) }}</span>
                    </div>
                </div>
            </section>

            <!-- Challenges Section -->
            <section class="py-12 sm:py-16">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <div class="flex items-center gap-3 mb-8">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-destructive/10">
                            <AlertTriangle class="h-5 w-5 text-destructive" aria-hidden="true" />
                        </div>
                        <h2 class="text-2xl font-bold tracking-tight sm:text-3xl">
                            AI visibility challenges for {{ industry.name }}
                        </h2>
                    </div>
                    <ol class="space-y-4">
                        <li
                            v-for="(challenge, index) in industry.challenges"
                            :key="index"
                            class="flex gap-4 rounded-lg border p-4"
                        >
                            <span
                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-muted text-sm font-semibold"
                            >
                                {{ index + 1 }}
                            </span>
                            <p class="text-muted-foreground leading-relaxed pt-1">{{ challenge }}</p>
                        </li>
                    </ol>
                </div>
            </section>

            <Separator />

            <!-- Tips Section -->
            <section class="py-12 sm:py-16">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <div class="flex items-center gap-3 mb-8">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary/10">
                            <Lightbulb class="h-5 w-5 text-primary" aria-hidden="true" />
                        </div>
                        <h2 class="text-2xl font-bold tracking-tight sm:text-3xl">
                            How to optimize {{ industry.name }} content for AI
                        </h2>
                    </div>
                    <div class="grid gap-6 sm:grid-cols-2">
                        <Card v-for="(tip, index) in industry.tips" :key="index" class="transition-colors hover:border-primary/30">
                            <CardContent class="p-6">
                                <div class="mb-3 flex h-8 w-8 items-center justify-center rounded-full bg-primary/10 text-sm font-bold text-primary">
                                    {{ index + 1 }}
                                </div>
                                <h3 class="mb-2 text-lg font-semibold">{{ tip.title }}</h3>
                                <p class="text-sm text-muted-foreground leading-relaxed">{{ tip.description }}</p>
                            </CardContent>
                        </Card>
                    </div>
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
                            v-for="(item, index) in industry.faq"
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

            <!-- Related Industries Section -->
            <section v-if="relatedIndustryLinks.length > 0" class="py-12 sm:py-16">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <h2 class="mb-6 text-2xl font-bold tracking-tight sm:text-3xl">
                        Related industries
                    </h2>
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        <Link
                            v-for="related in relatedIndustryLinks"
                            :key="related.slug"
                            :href="`/geo-for-${related.slug}`"
                            class="group flex items-center justify-between rounded-lg border p-4 transition-colors hover:border-primary/30 hover:bg-muted/50 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
                        >
                            <span class="font-medium">GEO for {{ related.name }}</span>
                            <ArrowRight class="h-4 w-4 text-muted-foreground transition-transform group-hover:translate-x-1 group-hover:text-primary" aria-hidden="true" />
                        </Link>
                    </div>
                </div>
            </section>

            <Separator v-if="relatedIndustryLinks.length > 0" />

            <!-- Browse All Industries Section -->
            <section class="py-12 sm:py-16">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <h2 class="mb-6 text-2xl font-bold tracking-tight sm:text-3xl">
                        Browse all industry guides
                    </h2>
                    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        <Link
                            v-for="ind in otherIndustries"
                            :key="ind.slug"
                            :href="`/geo-for-${ind.slug}`"
                            class="rounded-lg px-4 py-3 text-sm text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
                        >
                            GEO for {{ ind.name }}
                        </Link>
                    </div>
                </div>
            </section>

            <Separator />

            <SuggestedContent :current-slug="'geo-for-' + industry.slug" />

            <!-- CTA Section -->
            <section class="py-16 sm:py-20">
                <div class="mx-auto max-w-4xl px-4 text-center sm:px-6 lg:px-8">
                    <h2 class="text-2xl font-bold tracking-tight sm:text-3xl">
                        Start optimizing your {{ industry.name }} content
                    </h2>
                    <p class="mx-auto mt-4 max-w-xl text-muted-foreground">
                        Get actionable insights to improve your AI visibility and ensure your {{ industry.name }} content is cited by generative engines.
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
