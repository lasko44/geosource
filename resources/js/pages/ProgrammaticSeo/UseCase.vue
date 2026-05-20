<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import {
    AlertTriangle,
    ArrowRight,
    Calendar,
    CheckCircle,
    ChevronDown,
    ChevronUp,
    Info,
    Sparkles,
} from 'lucide-vue-next';
import SkipNav from '@/components/resources/SkipNav.vue';
import ResourceHeader from '@/components/resources/ResourceHeader.vue';
import ResourceFooter from '@/components/resources/ResourceFooter.vue';
import ResourceBreadcrumb from '@/components/resources/ResourceBreadcrumb.vue';
import SuggestedContent from '@/components/resources/SuggestedContent.vue';
import { register } from '@/routes';

interface UseCase {
    slug: string;
    title: string;
    meta_description: string;
    published_at: string;
    updated_at: string;
    hero_headline: string;
    hero_description: string;
    the_problem: string;
    steps: { title: string; description: string }[];
    mistakes: { title: string; description: string }[];
    faq: { question: string; answer: string }[];
    related_use_cases: string[];
}

interface UseCaseLink {
    slug: string;
    title: string;
}

const props = defineProps<{
    useCase: UseCase;
    allUseCases: UseCaseLink[];
}>();

const breadcrumbItems = [
    { label: 'Resources', href: '/resources' },
    { label: props.useCase.title },
];

const canonicalUrl = computed(() => `https://geosource.ai/how-to/${props.useCase.slug}`);

const openFaqIndex = ref<number | null>(null);

const toggleFaq = (index: number): void => {
    openFaqIndex.value = openFaqIndex.value === index ? null : index;
};

const relatedUseCases = computed(() =>
    props.allUseCases.filter((uc) => props.useCase.related_use_cases.includes(uc.slug)),
);

const otherUseCases = computed(() =>
    props.allUseCases.filter((uc) => uc.slug !== props.useCase.slug),
);

const formatDate = (dateStr: string) => {
    const d = new Date(dateStr + 'T00:00:00');
    return d.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
};

const articleJsonLd = computed(() => ({
    '@context': 'https://schema.org',
    '@type': 'HowTo',
    name: props.useCase.title,
    description: props.useCase.meta_description,
    url: canonicalUrl.value,
    datePublished: props.useCase.published_at,
    dateModified: props.useCase.updated_at,
    publisher: {
        '@type': 'Organization',
        name: 'GeoSource.ai',
        url: 'https://geosource.ai',
    },
    step: props.useCase.steps.map((step, index) => ({
        '@type': 'HowToStep',
        position: index + 1,
        name: step.title,
        text: step.description,
    })),
}));

const faqJsonLd = computed(() => ({
    '@context': 'https://schema.org',
    '@type': 'FAQPage',
    mainEntity: props.useCase.faq.map((item) => ({
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
        <title>{{ useCase.title }}</title>
        <meta name="description" :content="useCase.meta_description" />
        <link rel="canonical" :href="canonicalUrl" />
        <meta property="og:title" :content="useCase.title" />
        <meta property="og:description" :content="useCase.meta_description" />
        <meta property="og:url" :content="canonicalUrl" />
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
                        <Sparkles class="mr-1.5 h-3 w-3" aria-hidden="true" />
                        How-To Guide
                    </Badge>
                    <h1 class="text-3xl font-bold tracking-tight sm:text-4xl lg:text-5xl">
                        {{ useCase.hero_headline }}
                    </h1>
                    <p class="max-w-2xl text-lg text-muted-foreground sm:text-xl">
                        {{ useCase.hero_description }}
                    </p>
                    <div class="flex items-center gap-4 text-sm text-muted-foreground">
                        <span class="flex items-center gap-1.5">
                            <Calendar class="h-3.5 w-3.5" aria-hidden="true" />
                            Published {{ formatDate(useCase.published_at) }}
                        </span>
                        <span>&middot;</span>
                        <span>Updated {{ formatDate(useCase.updated_at) }}</span>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <Link :href="register().url">
                            <Button size="lg">
                                <Sparkles class="mr-2 h-4 w-4" aria-hidden="true" />
                                Run a free GEO scan
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>
        </section>

        <!-- The Problem Section -->
        <section class="py-16" aria-labelledby="problem-heading">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <h2 id="problem-heading" class="text-2xl font-bold tracking-tight sm:text-3xl">
                    Why this matters now
                </h2>
                <Card class="mt-8 border-l-4 border-l-amber-500">
                    <CardContent class="flex gap-4 p-6">
                        <div class="flex-shrink-0">
                            <Info class="h-6 w-6 text-muted-foreground" aria-hidden="true" />
                        </div>
                        <p class="text-base leading-relaxed text-muted-foreground">
                            {{ useCase.the_problem }}
                        </p>
                    </CardContent>
                </Card>
            </div>
        </section>

        <Separator class="mx-auto max-w-4xl" />

        <!-- Steps Section -->
        <section class="py-16" aria-labelledby="steps-heading">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <h2 id="steps-heading" class="text-2xl font-bold tracking-tight sm:text-3xl">
                    Step-by-step guide
                </h2>
                <p class="mt-3 text-muted-foreground">
                    Follow these steps to improve your AI visibility.
                </p>
                <div class="mt-8 space-y-6">
                    <Card
                        v-for="(step, index) in useCase.steps"
                        :key="index"
                        class="transition-colors hover:bg-muted/30"
                    >
                        <CardContent class="p-6">
                            <div class="flex items-start gap-4">
                                <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-primary/10">
                                    <span class="text-sm font-bold text-primary">{{ index + 1 }}</span>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold">{{ step.title }}</h3>
                                    <p class="mt-2 text-sm leading-relaxed text-muted-foreground sm:text-base">
                                        {{ step.description }}
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </section>

        <Separator class="mx-auto max-w-4xl" />

        <!-- Common Mistakes Section -->
        <section class="py-16" aria-labelledby="mistakes-heading">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-destructive/10">
                        <AlertTriangle class="h-5 w-5 text-destructive" aria-hidden="true" />
                    </div>
                    <h2 id="mistakes-heading" class="text-2xl font-bold tracking-tight sm:text-3xl">
                        Common mistakes to avoid
                    </h2>
                </div>
                <div class="mt-8 grid gap-4 sm:grid-cols-2">
                    <Card
                        v-for="(mistake, index) in useCase.mistakes"
                        :key="index"
                        class="border-l-4 border-l-destructive/30"
                    >
                        <CardContent class="p-6">
                            <h3 class="font-semibold">{{ mistake.title }}</h3>
                            <p class="mt-2 text-sm leading-relaxed text-muted-foreground">
                                {{ mistake.description }}
                            </p>
                        </CardContent>
                    </Card>
                </div>
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
                        v-for="(item, index) in useCase.faq"
                        :key="index"
                        class="rounded-lg border transition-colors"
                        :class="openFaqIndex === index ? 'bg-muted/30' : ''"
                    >
                        <button
                            class="flex w-full items-center justify-between rounded-lg px-6 py-4 text-left focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
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

        <!-- Related Use Cases Section -->
        <section v-if="relatedUseCases.length > 0" class="py-16" aria-labelledby="related-heading">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <h2 id="related-heading" class="text-2xl font-bold tracking-tight sm:text-3xl">
                    Related guides
                </h2>
                <p class="mt-3 text-muted-foreground">
                    Continue learning with these related how-to guides.
                </p>
                <div class="mt-8 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <Link
                        v-for="related in relatedUseCases"
                        :key="related.slug"
                        :href="`/how-to/${related.slug}`"
                        class="group flex items-center justify-between rounded-lg border p-4 transition-colors hover:border-primary/30 hover:bg-muted/50 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
                    >
                        <span class="pr-2 text-sm font-medium">{{ related.title }}</span>
                        <ArrowRight
                            class="h-4 w-4 flex-shrink-0 text-muted-foreground transition-transform group-hover:translate-x-1 group-hover:text-primary"
                            aria-hidden="true"
                        />
                    </Link>
                </div>
            </div>
        </section>

        <Separator v-if="relatedUseCases.length > 0" class="mx-auto max-w-4xl" />

        <!-- Browse All Use Cases -->
        <section class="py-16" aria-labelledby="all-guides-heading">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <h2 id="all-guides-heading" class="text-2xl font-bold tracking-tight sm:text-3xl">
                    Browse all guides
                </h2>
                <div class="mt-8 grid gap-2 sm:grid-cols-2">
                    <Link
                        v-for="uc in otherUseCases"
                        :key="uc.slug"
                        :href="`/how-to/${uc.slug}`"
                        class="rounded-lg px-4 py-3 text-sm text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
                    >
                        {{ uc.title }}
                    </Link>
                </div>
            </div>
        </section>

        <Separator class="mx-auto max-w-4xl" />

        <SuggestedContent :current-slug="'how-to-' + useCase.slug" />

        <!-- CTA Section -->
        <section class="py-20" aria-labelledby="cta-heading">
            <div class="mx-auto max-w-4xl px-4 text-center sm:px-6 lg:px-8">
                <h2 id="cta-heading" class="text-2xl font-bold tracking-tight sm:text-3xl">
                    Ready to optimize for AI search?
                </h2>
                <p class="mx-auto mt-4 max-w-2xl text-muted-foreground">
                    Run a free GEO scan to see how your content scores across 12 AI optimization
                    pillars. Get actionable recommendations to improve your AI search visibility.
                </p>
                <div class="mt-8 flex flex-wrap items-center justify-center gap-4">
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
