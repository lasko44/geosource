<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import SkipNav from '@/components/resources/SkipNav.vue';
import ResourceHeader from '@/components/resources/ResourceHeader.vue';
import ResourceFooter from '@/components/resources/ResourceFooter.vue';
import {
    Newspaper,
    ArrowRight,
    Calendar,
    Clock,
    Eye,
} from 'lucide-vue-next';

interface BlogPost {
    id: number;
    uuid: string;
    slug: string;
    title: string;
    excerpt: string;
    featured_image: string | null;
    featured_image_url: string | null;
    published_at: string;
    tags: string[] | null;
    view_count: number;
}

interface PaginatedPosts {
    data: BlogPost[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    next_page_url: string | null;
    prev_page_url: string | null;
}

const props = defineProps<{
    posts: PaginatedPosts;
}>();

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const jsonLd = {
    '@context': 'https://schema.org',
    '@type': 'Blog',
    name: 'GeoSource.ai Blog',
    description: 'Insights on Generative Engine Optimization (GEO), AI search, and content strategy for the age of AI.',
    url: 'https://geosource.ai/blog',
    publisher: {
        '@type': 'Organization',
        name: 'GeoSource.ai',
        url: 'https://geosource.ai',
    },
    blogPost: props.posts.data.map(post => ({
        '@type': 'BlogPosting',
        headline: post.title,
        description: post.excerpt,
        url: `https://geosource.ai/blog/${post.slug}`,
        datePublished: post.published_at,
    })),
};
</script>

<template>
    <Head title="Blog - GeoSource.ai">
        <meta name="description" content="Insights on Generative Engine Optimization (GEO), AI search, and content strategy. Learn how to optimize your content for ChatGPT, Perplexity, and other AI search engines." />
        <meta property="og:title" content="GeoSource.ai Blog" />
        <meta property="og:description" content="Insights on Generative Engine Optimization (GEO), AI search, and content strategy for the age of AI." />
        <meta property="og:type" content="website" />
        <meta property="og:url" content="https://geosource.ai/blog" />
        <meta property="og:site_name" content="GeoSource.ai" />
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" content="GeoSource.ai Blog" />
        <meta name="twitter:description" content="Insights on Generative Engine Optimization (GEO), AI search, and content strategy." />
        <meta name="robots" content="index, follow" />
        <link rel="canonical" href="https://geosource.ai/blog" />
        <component :is="'script'" type="application/ld+json">{{ JSON.stringify(jsonLd) }}</component>
    </Head>

    <div class="min-h-screen bg-background text-foreground">
        <SkipNav />
        <ResourceHeader />

        <main id="main-content" role="main">
            <!-- Hero Section -->
            <section aria-labelledby="hero-heading" class="relative overflow-hidden py-16 sm:py-24">
                <div class="absolute inset-0 -z-10 bg-[radial-gradient(45%_40%_at_50%_60%,hsl(var(--primary)/0.12),transparent)]" aria-hidden="true" />
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="mx-auto max-w-3xl text-center">
                        <Badge variant="secondary" class="mb-6">
                            <Newspaper class="mr-1 h-3 w-3" aria-hidden="true" />
                            Blog
                        </Badge>
                        <h1 id="hero-heading" class="text-4xl font-bold tracking-tight sm:text-5xl">
                            GEO Insights & Strategies
                        </h1>
                        <p class="mt-6 text-lg leading-8 text-muted-foreground">
                            Practical tips and strategies for optimizing your content for <strong class="text-foreground">AI search engines</strong>. Stay ahead in the age of generative AI.
                        </p>
                    </div>
                </div>
            </section>

            <!-- Blog Posts Grid -->
            <section aria-labelledby="posts-heading" class="border-t bg-muted/30 py-16">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <h2 id="posts-heading" class="sr-only">Blog Posts</h2>

                    <div v-if="posts.data.length === 0" class="text-center py-12">
                        <Newspaper class="mx-auto h-12 w-12 text-muted-foreground/50" />
                        <h3 class="mt-4 text-lg font-medium">No posts yet</h3>
                        <p class="mt-2 text-muted-foreground">Check back soon for new content!</p>
                    </div>

                    <ul v-else class="grid gap-8 md:grid-cols-2 lg:grid-cols-3" role="list">
                        <li v-for="post in posts.data" :key="post.uuid">
                            <Link
                                :href="`/blog/${post.slug}`"
                                class="group block h-full focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 rounded-lg"
                                :aria-label="`${post.title}: ${post.excerpt}`"
                            >
                                <Card class="h-full transition-all hover:border-primary/50 hover:shadow-lg">
                                    <div v-if="post.featured_image_url" class="aspect-video overflow-hidden rounded-t-lg">
                                        <img
                                            :src="post.featured_image_url"
                                            :alt="post.title"
                                            class="h-full w-full object-cover transition-transform group-hover:scale-105"
                                        />
                                    </div>
                                    <CardHeader>
                                        <div v-if="post.tags && post.tags.length > 0" class="flex flex-wrap gap-2 mb-2">
                                            <Badge v-for="tag in post.tags.slice(0, 3)" :key="tag" variant="outline" class="text-xs">
                                                {{ tag }}
                                            </Badge>
                                        </div>
                                        <CardTitle class="text-xl group-hover:text-primary transition-colors line-clamp-2">
                                            {{ post.title }}
                                        </CardTitle>
                                        <CardDescription class="text-base line-clamp-3">
                                            {{ post.excerpt }}
                                        </CardDescription>
                                    </CardHeader>
                                    <CardContent>
                                        <div class="flex items-center justify-between text-sm text-muted-foreground">
                                            <div class="flex items-center gap-4">
                                                <span class="inline-flex items-center gap-1">
                                                    <Calendar class="h-3.5 w-3.5" aria-hidden="true" />
                                                    <time :datetime="post.published_at">{{ formatDate(post.published_at) }}</time>
                                                </span>
                                            </div>
                                            <span class="inline-flex items-center text-primary font-medium" aria-hidden="true">
                                                Read
                                                <ArrowRight class="ml-1 h-4 w-4 transition-transform group-hover:translate-x-1" />
                                            </span>
                                        </div>
                                    </CardContent>
                                </Card>
                            </Link>
                        </li>
                    </ul>

                    <!-- Pagination -->
                    <nav v-if="posts.last_page > 1" class="mt-12 flex justify-center gap-2" aria-label="Blog pagination">
                        <Link
                            v-if="posts.prev_page_url"
                            :href="posts.prev_page_url"
                            class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2"
                        >
                            Previous
                        </Link>
                        <span class="inline-flex items-center justify-center h-10 px-4 py-2 text-sm text-muted-foreground">
                            Page {{ posts.current_page }} of {{ posts.last_page }}
                        </span>
                        <Link
                            v-if="posts.next_page_url"
                            :href="posts.next_page_url"
                            class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2"
                        >
                            Next
                        </Link>
                    </nav>
                </div>
            </section>

            <!-- CTA Section -->
            <section aria-labelledby="cta-heading" class="border-t py-16">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="mx-auto max-w-2xl text-center">
                        <h2 id="cta-heading" class="text-2xl font-bold tracking-tight sm:text-3xl">
                            Ready to optimize for AI search?
                        </h2>
                        <p class="mt-4 text-lg text-muted-foreground">
                            Get your free GEO Score and see how your content performs with AI systems.
                        </p>
                        <div class="mt-8">
                            <Link href="/register" class="inline-block focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 rounded-md">
                                <Button size="lg" class="gap-2">
                                    Get Free GEO Score
                                    <ArrowRight class="h-4 w-4" aria-hidden="true" />
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
