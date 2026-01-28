<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { marked, Renderer } from 'marked';
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
    ChevronDown,
    ChevronUp,
    List,
    HelpCircle,
    Share2,
    Twitter,
    Linkedin,
    Facebook,
    Link as LinkIcon,
    Check,
} from 'lucide-vue-next';
import { ref, onMounted, onUnmounted } from 'vue';

// Share functionality
const copied = ref(false);
const shareMenuOpen = ref(false);

const getShareUrl = () => {
    return `https://geosource.ai/blog/${props.post.slug}`;
};

const trackShare = async (platform: string) => {
    try {
        await fetch(`/blog/${props.post.slug}/share`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify({ platform }),
        });
    } catch (err) {
        // Silently fail - tracking is non-critical
    }
};

const shareOnTwitter = () => {
    trackShare('twitter');
    const url = encodeURIComponent(getShareUrl());
    const text = encodeURIComponent(props.post.title);
    window.open(`https://twitter.com/intent/tweet?url=${url}&text=${text}`, '_blank', 'width=550,height=420');
    shareMenuOpen.value = false;
};

const shareOnLinkedIn = () => {
    trackShare('linkedin');
    const url = encodeURIComponent(getShareUrl());
    window.open(`https://www.linkedin.com/sharing/share-offsite/?url=${url}`, '_blank', 'width=550,height=420');
    shareMenuOpen.value = false;
};

const shareOnFacebook = () => {
    trackShare('facebook');
    const url = encodeURIComponent(getShareUrl());
    window.open(`https://www.facebook.com/sharer/sharer.php?u=${url}`, '_blank', 'width=550,height=420');
    shareMenuOpen.value = false;
};

const copyLink = async () => {
    try {
        await navigator.clipboard.writeText(getShareUrl());
        trackShare('copy_link');
        copied.value = true;
        setTimeout(() => {
            copied.value = false;
            shareMenuOpen.value = false;
        }, 2000);
    } catch (err) {
        console.error('Failed to copy:', err);
    }
};

// Close share menu when clicking outside
const handleClickOutside = (event: MouseEvent) => {
    const target = event.target as HTMLElement;
    if (!target.closest('.share-menu-container')) {
        shareMenuOpen.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});

interface Author {
    id: number;
    name: string;
}

interface FAQItem {
    question: string;
    answer: string;
}

interface QuickLink {
    title: string;
    anchor: string;
}

interface BlogPost {
    id: number;
    uuid: string;
    slug: string;
    title: string;
    excerpt: string;
    content: string;
    featured_image: string | null;
    featured_image_url: string | null;
    social_image_url: string | null;
    meta_title: string | null;
    meta_description: string | null;
    schema_json: Record<string, unknown> | null;
    published_at: string;
    tags: string[] | null;
    view_count: number;
    author: Author | null;
    faq: FAQItem[] | null;
    quick_links: QuickLink[] | null;
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

// Custom renderer to add IDs to headings for anchor links
const slugify = (text: string) => {
    return text
        .toLowerCase()
        .replace(/[^a-z0-9\s-]/g, '')
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-')
        .trim();
};

const renderer = new Renderer();
renderer.heading = ({ tokens, depth }) => {
    const text = tokens.map(t => ('text' in t ? t.text : '')).join('');
    const id = slugify(text);
    return `<h${depth} id="${id}">${text}</h${depth}>`;
};

const renderedContent = computed(() => {
    return marked(props.post.content, {
        breaks: true,
        gfm: true,
        renderer,
    });
});

const metaTitle = computed(() => props.post.meta_title || props.post.title);
const metaDescription = computed(() => props.post.meta_description || props.post.excerpt);

// Quick links (table of contents)
const hasQuickLinks = computed(() => props.post.quick_links && props.post.quick_links.length > 0);
const quickLinksOpen = ref(true);

// FAQ
const hasFaq = computed(() => props.post.faq && props.post.faq.length > 0);

// Generate FAQ Schema for SEO
const faqSchema = computed(() => {
    if (!hasFaq.value) return null;
    return {
        '@context': 'https://schema.org',
        '@type': 'FAQPage',
        mainEntity: props.post.faq?.map(item => ({
            '@type': 'Question',
            name: item.question,
            acceptedAnswer: {
                '@type': 'Answer',
                text: item.answer,
            },
        })),
    };
});

const scrollToSection = (anchor: string) => {
    const element = document.getElementById(anchor);
    if (element) {
        element.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
};

// Use stored schema_json if available, otherwise generate dynamically
const jsonLd = computed(() => {
    if (props.post.schema_json) {
        return props.post.schema_json;
    }

    // Fallback to dynamically generated schema
    return {
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
        image: props.post.social_image_url || 'https://geosource.ai/og-image.png',
    };
});
</script>

<template>
    <Head :title="`${metaTitle} - GeoSource.ai Blog`">
        <meta name="description" :content="metaDescription" />
        <meta property="og:title" :content="metaTitle" />
        <meta property="og:description" :content="metaDescription" />
        <meta property="og:type" content="article" />
        <meta property="og:url" :content="`https://geosource.ai/blog/${post.slug}`" />
        <meta property="og:site_name" content="GeoSource.ai" />
        <meta property="og:image" :content="post.social_image_url || 'https://geosource.ai/og-image.png'" />
        <meta property="og:image:width" content="1200" />
        <meta property="og:image:height" content="630" />
        <meta property="article:published_time" :content="post.published_at" />
        <meta v-if="post.author" property="article:author" :content="post.author.name" />
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" :content="metaTitle" />
        <meta name="twitter:description" :content="metaDescription" />
        <meta name="twitter:image" :content="post.social_image_url || 'https://geosource.ai/og-image.png'" />
        <meta name="twitter:site" content="@geosourceai" />
        <meta name="robots" content="index, follow" />
        <link rel="canonical" :href="`https://geosource.ai/blog/${post.slug}`" />
        <component :is="'script'" type="application/ld+json">{{ JSON.stringify(jsonLd) }}</component>
        <component v-if="faqSchema" :is="'script'" type="application/ld+json">{{ JSON.stringify(faqSchema) }}</component>
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
                        <div class="mt-6 flex flex-wrap items-center justify-between gap-4">
                            <div class="flex flex-wrap items-center gap-4 text-sm text-muted-foreground">
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

                            <!-- Share Button -->
                            <div class="relative share-menu-container">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    @click="shareMenuOpen = !shareMenuOpen"
                                    class="gap-2"
                                    :class="{ 'bg-muted': shareMenuOpen }"
                                >
                                    <Share2 class="h-4 w-4" />
                                    Share
                                </Button>

                                <!-- Share Dropdown -->
                                <Transition
                                    enter-active-class="transition ease-out duration-100"
                                    enter-from-class="transform opacity-0 scale-95"
                                    enter-to-class="transform opacity-100 scale-100"
                                    leave-active-class="transition ease-in duration-75"
                                    leave-from-class="transform opacity-100 scale-100"
                                    leave-to-class="transform opacity-0 scale-95"
                                >
                                    <div
                                        v-show="shareMenuOpen"
                                        class="absolute right-0 top-full mt-2 z-50 w-52 rounded-md border bg-popover p-1 shadow-md"
                                    >
                                        <button
                                            @click="shareOnTwitter"
                                            class="flex w-full items-center gap-3 rounded-sm px-2 py-1.5 text-sm outline-none hover:bg-accent hover:text-accent-foreground transition-colors"
                                        >
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                                                <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                                            </svg>
                                            Share on X
                                        </button>
                                        <button
                                            @click="shareOnLinkedIn"
                                            class="flex w-full items-center gap-3 rounded-sm px-2 py-1.5 text-sm outline-none hover:bg-accent hover:text-accent-foreground transition-colors"
                                        >
                                            <Linkedin class="h-4 w-4" />
                                            Share on LinkedIn
                                        </button>
                                        <button
                                            @click="shareOnFacebook"
                                            class="flex w-full items-center gap-3 rounded-sm px-2 py-1.5 text-sm outline-none hover:bg-accent hover:text-accent-foreground transition-colors"
                                        >
                                            <Facebook class="h-4 w-4" />
                                            Share on Facebook
                                        </button>
                                        <div class="my-1 h-px bg-border"></div>
                                        <button
                                            @click="copyLink"
                                            class="flex w-full items-center gap-3 rounded-sm px-2 py-1.5 text-sm outline-none hover:bg-accent hover:text-accent-foreground transition-colors"
                                            :class="{ 'text-green-600 dark:text-green-400': copied }"
                                        >
                                            <Check v-if="copied" class="h-4 w-4" />
                                            <LinkIcon v-else class="h-4 w-4" />
                                            {{ copied ? 'Copied!' : 'Copy link' }}
                                        </button>
                                    </div>
                                </Transition>
                            </div>
                        </div>
                    </header>

                    <!-- Featured Image -->
                    <div v-if="post.featured_image_url" class="mb-12 overflow-hidden rounded-lg">
                        <img
                            :src="post.featured_image_url"
                            :alt="post.title"
                            class="w-full h-auto"
                        />
                    </div>

                    <!-- Quick Links / Table of Contents -->
                    <div v-if="hasQuickLinks" class="mb-10 rounded-lg border bg-muted/30 p-6">
                        <button
                            @click="quickLinksOpen = !quickLinksOpen"
                            class="flex w-full items-center justify-between text-left"
                        >
                            <div class="flex items-center gap-2">
                                <List class="h-5 w-5 text-primary" />
                                <span class="font-semibold">In This Article</span>
                            </div>
                            <ChevronUp v-if="quickLinksOpen" class="h-5 w-5 text-muted-foreground" />
                            <ChevronDown v-else class="h-5 w-5 text-muted-foreground" />
                        </button>
                        <nav v-show="quickLinksOpen" class="mt-4" aria-label="Table of contents">
                            <ol class="space-y-2 list-decimal list-inside">
                                <li v-for="(link, index) in post.quick_links" :key="index">
                                    <button
                                        @click="scrollToSection(link.anchor)"
                                        class="text-primary hover:underline focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 rounded"
                                    >
                                        {{ link.title }}
                                    </button>
                                </li>
                            </ol>
                        </nav>
                    </div>

                    <!-- Content -->
                    <div
                        class="prose prose-lg dark:prose-invert max-w-none prose-headings:scroll-mt-20 prose-a:text-primary prose-a:no-underline hover:prose-a:underline prose-img:rounded-lg"
                        v-html="renderedContent"
                    />

                    <!-- FAQ Section -->
                    <section v-if="hasFaq" class="mt-12 rounded-lg border bg-muted/30 p-6" id="faq">
                        <div class="flex items-center gap-2 mb-6">
                            <HelpCircle class="h-6 w-6 text-primary" />
                            <h2 class="text-2xl font-bold">Frequently Asked Questions</h2>
                        </div>
                        <div class="space-y-3">
                            <details
                                v-for="(item, index) in post.faq"
                                :key="index"
                                class="group rounded-lg border bg-background"
                            >
                                <summary class="flex cursor-pointer items-center justify-between p-4 font-medium hover:bg-muted/50 transition-colors rounded-lg">
                                    <span>{{ item.question }}</span>
                                    <ChevronDown class="h-5 w-5 text-muted-foreground transition-transform group-open:rotate-180" />
                                </summary>
                                <div class="px-4 pb-4 text-muted-foreground">
                                    {{ item.answer }}
                                </div>
                            </details>
                        </div>
                    </section>

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
