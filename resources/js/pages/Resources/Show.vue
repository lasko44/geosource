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
import BlockRenderer from '@/components/resource-blocks/BlockRenderer.vue';
import {
    BookOpen,
    ArrowRight,
    ArrowLeft,
    Calendar,
    Lightbulb,
    Code,
    BarChart,
    Target,
    Shield,
    Zap,
    FileText,
    Search,
    Settings,
    Award,
    TrendingUp,
    CheckCircle,
    List,
    Globe,
} from 'lucide-vue-next';
import { computed } from 'vue';

interface ContentBlock {
    type: string;
    props?: Record<string, unknown>;
    content?: string | ContentBlock[];
}

interface Resource {
    id: number;
    title: string;
    slug: string;
    category: string;
    category_icon: string;
    excerpt: string;
    intro: string | null;
    content_type: 'html' | 'blocks';
    content: string;
    content_blocks: ContentBlock[] | null;
    meta_title: string;
    meta_description: string;
    og_title: string;
    og_description: string;
    canonical_url: string;
    json_ld: Record<string, unknown> | null;
    faq_json_ld: Record<string, unknown> | null;
    published_at: string | null;
    formatted_date: string;
    url: string;
}

interface RelatedResource {
    title: string;
    slug: string;
}

interface NavResource {
    title: string;
    slug: string;
}

interface Props {
    resource: Resource;
    relatedResources: RelatedResource[];
    prevResource: NavResource | null;
    nextResource: NavResource | null;
}

const props = defineProps<Props>();

const breadcrumbItems = computed(() => [
    { label: 'Resources', href: '/resources' },
    { label: props.resource.title },
]);

// Icon mapping
const iconComponents: Record<string, unknown> = {
    BookOpen,
    Lightbulb,
    Code,
    BarChart,
    Target,
    Shield,
    Zap,
    FileText,
    Search,
    Settings,
    Award,
    TrendingUp,
    CheckCircle,
    List,
    Globe,
};

const categoryIcon = computed(() => {
    return iconComponents[props.resource.category_icon] || BookOpen;
});

// OG Image URL - use article-specific image if provided, otherwise default
const ogImageUrl = computed(() => {
    if (props.resource.og_image) {
        return props.resource.og_image;
    }
    // Default OG image
    return 'https://geosource.ai/og-image.png';
});
</script>

<template>
    <Head :title="`${resource.meta_title} - GeoSource.ai`">
        <meta name="description" :content="resource.meta_description" />
        <meta property="og:title" :content="resource.og_title" />
        <meta property="og:description" :content="resource.og_description" />
        <meta property="og:type" content="article" />
        <meta property="og:url" :content="resource.url" />
        <meta property="og:image" :content="ogImageUrl" />
        <meta property="og:image:width" content="1200" />
        <meta property="og:image:height" content="630" />
        <meta property="og:site_name" content="GeoSource.ai" />
        <meta v-if="resource.published_at" property="article:published_time" :content="resource.published_at" />
        <meta property="article:author" content="GeoSource.ai" />
        <meta property="article:section" :content="resource.category" />
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" :content="resource.og_title" />
        <meta name="twitter:description" :content="resource.og_description" />
        <meta name="twitter:image" :content="ogImageUrl" />
        <meta name="twitter:site" content="@geosourceai" />
        <meta name="robots" content="index, follow" />
        <link rel="canonical" :href="resource.canonical_url" />
        <component v-if="resource.json_ld" :is="'script'" type="application/ld+json">{{ JSON.stringify(resource.json_ld) }}</component>
        <component v-if="resource.faq_json_ld" :is="'script'" type="application/ld+json">{{ JSON.stringify(resource.faq_json_ld) }}</component>
    </Head>

    <div class="min-h-screen bg-background text-foreground">
        <SkipNav />
        <ResourceHeader />

        <main id="main-content" role="main">
            <ResourceBreadcrumb :items="breadcrumbItems" />

            <!-- Article -->
            <article class="py-12">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <!-- Header -->
                    <header class="mb-12">
                        <Badge variant="secondary" class="mb-4">
                            <component :is="categoryIcon" class="mr-1 h-3 w-3" aria-hidden="true" />
                            {{ resource.category }}
                        </Badge>
                        <h1 class="text-3xl font-bold tracking-tight sm:text-4xl lg:text-5xl">
                            {{ resource.title }}
                        </h1>
                        <p v-if="resource.intro" class="mt-4 text-lg text-muted-foreground">
                            {{ resource.intro }}
                        </p>
                        <p v-else class="mt-4 text-lg text-muted-foreground">
                            {{ resource.excerpt }}
                        </p>
                        <div class="mt-4 flex items-center gap-2 text-sm text-muted-foreground">
                            <Calendar class="h-4 w-4" aria-hidden="true" />
                            <time :datetime="resource.published_at || undefined">{{ resource.formatted_date }}</time>
                        </div>
                    </header>

                    <!-- Main Content - Blocks -->
                    <BlockRenderer
                        v-if="resource.content_type === 'blocks' && resource.content_blocks"
                        :blocks="resource.content_blocks"
                    />

                    <!-- Main Content - HTML -->
                    <div
                        v-else
                        class="prose prose-lg dark:prose-invert max-w-none
                        prose-headings:scroll-mt-20
                        prose-h2:text-2xl prose-h2:font-bold prose-h2:mb-6 prose-h2:mt-12
                        prose-h3:text-xl prose-h3:font-semibold prose-h3:mb-4 prose-h3:mt-8
                        prose-p:text-muted-foreground prose-p:leading-relaxed
                        prose-a:text-primary prose-a:no-underline hover:prose-a:underline
                        prose-strong:text-foreground prose-strong:font-semibold
                        prose-ul:my-6 prose-ul:list-disc prose-ul:pl-6
                        prose-ol:my-6 prose-ol:list-decimal prose-ol:pl-6
                        prose-li:text-muted-foreground prose-li:my-2
                        prose-blockquote:border-l-4 prose-blockquote:border-primary prose-blockquote:pl-4 prose-blockquote:italic
                        prose-code:bg-muted prose-code:px-1.5 prose-code:py-0.5 prose-code:rounded prose-code:text-sm
                        prose-pre:bg-muted prose-pre:p-4 prose-pre:rounded-lg
                        prose-table:w-full prose-table:border-collapse
                        prose-th:border prose-th:border-border prose-th:p-3 prose-th:text-left prose-th:font-semibold prose-th:bg-muted
                        prose-td:border prose-td:border-border prose-td:p-3
                        prose-img:rounded-lg prose-img:shadow-md
                        [&_.highlight-box]:bg-primary/5 [&_.highlight-box]:border [&_.highlight-box]:border-primary/50 [&_.highlight-box]:rounded-lg [&_.highlight-box]:p-6 [&_.highlight-box]:my-6
                        [&_.info-box]:bg-blue-500/5 [&_.info-box]:border [&_.info-box]:border-blue-500/50 [&_.info-box]:rounded-lg [&_.info-box]:p-4 [&_.info-box]:my-6
                        [&_.warning-box]:bg-amber-500/5 [&_.warning-box]:border [&_.warning-box]:border-amber-500/50 [&_.warning-box]:rounded-lg [&_.warning-box]:p-4 [&_.warning-box]:my-6
                        [&_.success-box]:bg-green-500/5 [&_.success-box]:border [&_.success-box]:border-green-500/50 [&_.success-box]:rounded-lg [&_.success-box]:p-4 [&_.success-box]:my-6
                    "
                        v-html="resource.content"
                    />

                    <Separator class="my-12" />

                    <!-- Related Resources -->
                    <section v-if="relatedResources.length > 0" aria-labelledby="related">
                        <h2 id="related" class="text-2xl font-bold mb-6">Related Resources</h2>
                        <ul class="grid gap-4 sm:grid-cols-3" role="list">
                            <li v-for="article in relatedResources" :key="article.slug">
                                <Link
                                    :href="`/resources/${article.slug}`"
                                    class="group block h-full focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 rounded-lg"
                                    :aria-label="`Read more about ${article.title}`"
                                >
                                    <Card class="h-full transition-colors hover:border-primary/50">
                                        <CardContent class="pt-6">
                                            <p class="font-medium group-hover:text-primary transition-colors">
                                                {{ article.title }}
                                            </p>
                                            <span class="inline-flex items-center text-sm text-primary mt-2" aria-hidden="true">
                                                Read more
                                                <ArrowRight class="ml-1 h-3 w-3 transition-transform group-hover:translate-x-1" />
                                            </span>
                                        </CardContent>
                                    </Card>
                                </Link>
                            </li>
                        </ul>
                    </section>

                    <!-- Navigation -->
                    <nav aria-label="Article navigation" class="mt-12 flex items-center justify-between border-t pt-8">
                        <Link
                            v-if="prevResource"
                            :href="`/resources/${prevResource.slug}`"
                            class="inline-flex items-center text-muted-foreground hover:text-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 rounded"
                        >
                            <ArrowLeft class="mr-2 h-4 w-4" aria-hidden="true" />
                            {{ prevResource.title }}
                        </Link>
                        <Link
                            v-else
                            href="/resources"
                            class="inline-flex items-center text-muted-foreground hover:text-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 rounded"
                        >
                            <ArrowLeft class="mr-2 h-4 w-4" aria-hidden="true" />
                            Back to Resources
                        </Link>
                        <Link
                            v-if="nextResource"
                            :href="`/resources/${nextResource.slug}`"
                            class="inline-flex items-center text-primary hover:underline focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 rounded"
                        >
                            Next: {{ nextResource.title }}
                            <ArrowRight class="ml-2 h-4 w-4" aria-hidden="true" />
                        </Link>
                    </nav>
                </div>
            </article>

            <!-- CTA Section -->
            <section aria-labelledby="cta-heading" class="border-t bg-muted/30 py-12">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 text-center">
                    <h2 id="cta-heading" class="text-2xl font-bold">Ready to optimize for AI?</h2>
                    <p class="mt-2 text-muted-foreground">Get your GEO Score and start improving your AI visibility.</p>
                    <div class="mt-6">
                        <Link href="/register" class="inline-block focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 rounded-md">
                            <Button size="lg" class="gap-2">
                                Get Your GEO Score
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
