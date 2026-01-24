<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { marked } from 'marked';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import SkipNav from '@/components/resources/SkipNav.vue';
import ResourceHeader from '@/components/resources/ResourceHeader.vue';
import ResourceFooter from '@/components/resources/ResourceFooter.vue';
import ResourceBreadcrumb from '@/components/resources/ResourceBreadcrumb.vue';
import {
    ArrowRight,
    ArrowLeft,
    Calendar,
    Clock,
    User,
} from 'lucide-vue-next';

interface Author {
    id: number;
    name: string;
}

interface BlogPost {
    id: number;
    uuid: string;
    slug: string;
    title: string;
    excerpt: string;
    content: string;
    featured_image: string | null;
    meta_title: string | null;
    meta_description: string | null;
    published_at: string;
    tags: string[] | null;
    view_count: number;
    author: Author | null;
}

interface RelatedPost {
    id: number;
    uuid: string;
    slug: string;
    title: string;
    excerpt: string;
    published_at: string;
}

const props = defineProps<{
    post: BlogPost;
    relatedPosts: RelatedPost[];
}>();

const breadcrumbItems = computed(() => [
    { label: 'Blog', href: '/blog' },
    { label: props.post.title },
]);

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const readingTime = computed(() => {
    const words = props.post.content.split(/\s+/).length;
    return Math.max(1, Math.ceil(words / 200));
});

const renderedContent = computed(() => {
    return marked(props.post.content, {
        breaks: true,
        gfm: true,
    });
});

const metaTitle = computed(() => props.post.meta_title || props.post.title);
const metaDescription = computed(() => props.post.meta_description || props.post.excerpt);

const jsonLd = computed(() => ({
    '@context': 'https://schema.org',
    '@type': 'BlogPosting',
    headline: props.post.title,
    description: props.post.excerpt,
    url: `https://geosource.ai/blog/${props.post.slug}`,
    datePublished: props.post.published_at,
    dateModified: props.post.published_at,
    author: props.post.author ? {
        '@type': 'Person',
        name: props.post.author.name,
    } : {
        '@type': 'Organization',
        name: 'GeoSource.ai',
        url: 'https://geosource.ai',
    },
    publisher: {
        '@type': 'Organization',
        name: 'GeoSource.ai',
        url: 'https://geosource.ai',
    },
    mainEntityOfPage: {
        '@type': 'WebPage',
        '@id': `https://geosource.ai/blog/${props.post.slug}`,
    },
    image: props.post.featured_image || 'https://geosource.ai/og-image.png',
}));
</script>

<template>
    <Head :title="`${metaTitle} - GeoSource.ai Blog`">
        <meta name="description" :content="metaDescription" />
        <meta property="og:title" :content="metaTitle" />
        <meta property="og:description" :content="metaDescription" />
        <meta property="og:type" content="article" />
        <meta property="og:url" :content="`https://geosource.ai/blog/${post.slug}`" />
        <meta property="og:site_name" content="GeoSource.ai" />
        <meta property="article:published_time" :content="post.published_at" />
        <meta v-if="post.author" property="article:author" :content="post.author.name" />
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" :content="metaTitle" />
        <meta name="twitter:description" :content="metaDescription" />
        <meta name="twitter:site" content="@geosourceai" />
        <meta name="robots" content="index, follow" />
        <link rel="canonical" :href="`https://geosource.ai/blog/${post.slug}`" />
        <component :is="'script'" type="application/ld+json">{{ JSON.stringify(jsonLd) }}</component>
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
                        <div v-if="post.tags && post.tags.length > 0" class="flex flex-wrap gap-2 mb-4">
                            <Badge v-for="tag in post.tags" :key="tag" variant="secondary">
                                {{ tag }}
                            </Badge>
                        </div>
                        <h1 class="text-3xl font-bold tracking-tight sm:text-4xl lg:text-5xl">
                            {{ post.title }}
                        </h1>
                        <p class="mt-4 text-xl text-muted-foreground">
                            {{ post.excerpt }}
                        </p>
                        <div class="mt-6 flex flex-wrap items-center gap-4 text-sm text-muted-foreground">
                            <span class="flex items-center gap-1.5">
                                <Calendar class="h-4 w-4" aria-hidden="true" />
                                <time :datetime="post.published_at">{{ formatDate(post.published_at) }}</time>
                            </span>
                            <span class="flex items-center gap-1.5">
                                <Clock class="h-4 w-4" aria-hidden="true" />
                                {{ readingTime }} min read
                            </span>
                            <span v-if="post.author" class="flex items-center gap-1.5">
                                <User class="h-4 w-4" aria-hidden="true" />
                                {{ post.author.name }}
                            </span>
                        </div>
                    </header>

                    <!-- Featured Image -->
                    <div v-if="post.featured_image" class="mb-12 overflow-hidden rounded-lg">
                        <img
                            :src="post.featured_image"
                            :alt="post.title"
                            class="w-full h-auto"
                        />
                    </div>

                    <!-- Content -->
                    <div
                        class="prose prose-lg dark:prose-invert max-w-none prose-headings:scroll-mt-20 prose-a:text-primary prose-a:no-underline hover:prose-a:underline prose-img:rounded-lg"
                        v-html="renderedContent"
                    />

                    <Separator class="my-12" />

                    <!-- Back to Blog -->
                    <div class="flex items-center justify-between">
                        <Link
                            href="/blog"
                            class="inline-flex items-center gap-2 text-muted-foreground hover:text-foreground transition-colors"
                        >
                            <ArrowLeft class="h-4 w-4" />
                            Back to Blog
                        </Link>
                        <Link href="/register">
                            <Button class="gap-2">
                                Get Your GEO Score
                                <ArrowRight class="h-4 w-4" />
                            </Button>
                        </Link>
                    </div>
                </div>
            </article>

            <!-- Related Posts -->
            <section v-if="relatedPosts.length > 0" class="border-t bg-muted/30 py-16">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <h2 class="text-2xl font-bold mb-8 text-center">Related Articles</h2>
                    <ul class="grid gap-6 md:grid-cols-3" role="list">
                        <li v-for="related in relatedPosts" :key="related.uuid">
                            <Link
                                :href="`/blog/${related.slug}`"
                                class="group block h-full focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 rounded-lg"
                            >
                                <Card class="h-full transition-colors hover:border-primary/50">
                                    <CardHeader>
                                        <CardTitle class="text-lg group-hover:text-primary transition-colors line-clamp-2">
                                            {{ related.title }}
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <p class="text-muted-foreground line-clamp-2 mb-4">
                                            {{ related.excerpt }}
                                        </p>
                                        <span class="inline-flex items-center text-sm font-medium text-primary">
                                            Read more
                                            <ArrowRight class="ml-1 h-4 w-4 transition-transform group-hover:translate-x-1" />
                                        </span>
                                    </CardContent>
                                </Card>
                            </Link>
                        </li>
                    </ul>
                </div>
            </section>

            <!-- CTA Section -->
            <section class="border-t py-16">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="mx-auto max-w-2xl text-center">
                        <h2 class="text-2xl font-bold tracking-tight sm:text-3xl">
                            Ready to optimize for AI search?
                        </h2>
                        <p class="mt-4 text-lg text-muted-foreground">
                            Get your free GEO Score and see how your content performs with AI systems.
                        </p>
                        <div class="mt-8">
                            <Link href="/register" class="inline-block">
                                <Button size="lg" class="gap-2">
                                    Get Free GEO Score
                                    <ArrowRight class="h-4 w-4" />
                                </Button>
                            </Link>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <ResourceFooter />
    </div>
</template>

<style scoped>
/* Prose styles for markdown content */
:deep(.prose h2) {
    margin-top: 3rem;
    margin-bottom: 1.5rem;
}
:deep(.prose h3) {
    margin-top: 2rem;
    margin-bottom: 1rem;
}
:deep(.prose p) {
    margin-bottom: 1rem;
}
:deep(.prose ul),
:deep(.prose ol) {
    margin-top: 1rem;
    margin-bottom: 1rem;
    padding-left: 1.5rem;
}
:deep(.prose li) {
    margin-bottom: 0.5rem;
}
:deep(.prose blockquote) {
    border-left-width: 4px;
    border-color: hsl(var(--primary) / 0.5);
    padding-left: 1rem;
    font-style: italic;
    margin-top: 1.5rem;
    margin-bottom: 1.5rem;
}
:deep(.prose code) {
    background-color: hsl(var(--muted));
    padding: 0.125rem 0.375rem;
    border-radius: 0.25rem;
    font-size: 0.875rem;
}
:deep(.prose pre) {
    background-color: hsl(var(--muted));
    padding: 1rem;
    border-radius: 0.5rem;
    overflow-x: auto;
    margin-top: 1.5rem;
    margin-bottom: 1.5rem;
}
:deep(.prose pre code) {
    background-color: transparent;
    padding: 0;
}
:deep(.prose table) {
    width: 100%;
    border-collapse: collapse;
    margin-top: 1.5rem;
    margin-bottom: 1.5rem;
}
:deep(.prose th),
:deep(.prose td) {
    border: 1px solid hsl(var(--border));
    padding: 0.75rem;
    text-align: left;
}
:deep(.prose th) {
    background-color: hsl(var(--muted));
    font-weight: 600;
}
:deep(.prose strong) {
    font-weight: 600;
}
:deep(.prose a) {
    color: hsl(var(--primary));
}
:deep(.prose a:hover) {
    text-decoration: underline;
}
</style>
