<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import {
    ArrowRight,
    ArrowLeft,
    Image,
    CheckCircle,
    XCircle,
    FileText,
    ShoppingCart,
    BookOpen,
    Accessibility,
    Calendar,
} from 'lucide-vue-next';
import SkipNav from '@/components/resources/SkipNav.vue';
import ResourceHeader from '@/components/resources/ResourceHeader.vue';
import ResourceFooter from '@/components/resources/ResourceFooter.vue';
import ResourceBreadcrumb from '@/components/resources/ResourceBreadcrumb.vue';

const publishedDate = '2026-01-20';
const modifiedDate = '2026-06-03';
const formattedDate = new Date(modifiedDate).toLocaleDateString('en-US', { month: 'long', year: 'numeric' });

const breadcrumbItems = [
    { label: 'Resources', href: '/resources' },
    { label: 'Multimedia and GEO' },
];

const keyFindings = [
    'For informational content, multimedia is neutral. Use it when it helps the reader, not for AI visibility.',
    'For commercial content, heavy multimedia is associated with fewer AI recommendations, not more.',
    'AI assistants extract from text. Multimedia that lacks text equivalents gives the model nothing to quote.',
    'Accessibility-driven multimedia (alt text, captions, transcripts) is the version that actually helps.',
];

const accessibilityWins = [
    'Descriptive alt text that explains what the image shows and why it matters',
    'Full transcripts for videos and podcasts',
    'Captions and chapter markers on demos and walkthroughs',
    'Text summaries paired with infographics and charts',
];

const decorativeRedFlags = [
    'Hero videos that autoplay over the answer the reader came for',
    'Image galleries with no descriptive text around them',
    'Embedded explainer videos with no transcript or written summary',
    'Carousels and lightboxes that hide product details behind clicks',
    'Replacing written specs with screenshots of specs',
];

const relatedDefinitions = [
    { slug: 'what-is-geo', title: 'What Is Generative Engine Optimization (GEO)?' },
    { slug: 'what-is-a-geo-score', title: 'What Is a GEO Score?' },
];

const relatedResources = [
    { slug: 'e-e-a-t-and-geo', title: 'E-E-A-T and Generative Engine Optimization (GEO)' },
    { slug: 'ai-accessibility-for-geo', title: 'AI Accessibility and GEO' },
    { slug: 'readability-and-geo', title: 'Readability and GEO' },
    { slug: 'question-coverage-for-geo', title: 'Question Coverage and GEO' },
    { slug: 'ai-citations-and-geo', title: 'Citations and GEO: How AI Chooses Sources' },
];

const jsonLd = {
    '@context': 'https://schema.org',
    '@type': 'Article',
    headline: 'Multimedia and GEO: When Images and Video Help, and When They Hurt',
    description: 'Our ecommerce study found heavy multimedia was associated with fewer AI recommendations, not more. Here is when multimedia helps for GEO and when it quietly works against you.',
    url: 'https://geosource.ai/resources/multimedia-and-geo',
    datePublished: '2026-01-20',
    dateModified: '2026-06-03',
    author: {
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
        '@id': 'https://geosource.ai/resources/multimedia-and-geo',
    },
};

const faqJsonLd = {
    '@context': 'https://schema.org',
    '@type': 'FAQPage',
    mainEntity: [
        {
            '@type': 'Question',
            name: 'Does adding images and video improve AI citation?',
            acceptedAnswer: {
                '@type': 'Answer',
                text: 'Not on its own. Our ecommerce recommendation study found pages with heavy multimedia were recommended less often by AI assistants, not more. Multimedia is neutral for informational content and a drag on commercial content unless every visual is mirrored in clear text.',
            },
        },
        {
            '@type': 'Question',
            name: 'Should I remove images and video from my site?',
            acceptedAnswer: {
                '@type': 'Answer',
                text: 'No. If multimedia serves the reader, keep it. The point is to stop adding multimedia expecting an AI citation lift, and to make sure every visual has a text equivalent the model can actually parse.',
            },
        },
        {
            '@type': 'Question',
            name: 'What kind of multimedia still helps for GEO?',
            acceptedAnswer: {
                '@type': 'Answer',
                text: 'Accessibility-driven multimedia. Alt text, captions, transcripts, and written summaries convert visual content into text an AI assistant can quote. Decorative multimedia without text equivalents is empty calories.',
            },
        },
    ],
};
</script>

<template>
    <Head title="Multimedia and GEO: When It Helps, When It Hurts - GeoSource.ai">
        <meta name="description" content="Our ecommerce study found heavy multimedia was associated with fewer AI recommendations, not more. Here is when multimedia helps for GEO and when it quietly works against you." />
        <meta property="og:title" content="Multimedia and GEO: When It Helps, When It Hurts" />
        <meta property="og:description" content="The conventional 'add images and video for AI visibility' advice did not hold up in our recommendation-survival data." />
        <meta property="og:type" content="article" />
        <meta property="og:url" content="https://geosource.ai/resources/multimedia-and-geo" />
        <meta property="og:site_name" content="GeoSource.ai" />
        <meta property="article:published_time" :content="publishedDate" />
        <meta property="article:modified_time" :content="modifiedDate" />
        <meta property="article:author" content="GeoSource.ai" />
        <meta property="article:section" content="Media" />
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" content="Multimedia and GEO: When It Helps, When It Hurts" />
        <meta name="twitter:description" content="The conventional 'add images and video for AI visibility' advice did not hold up in our data." />
        <meta name="twitter:site" content="@geosource_ai" />
        <meta name="robots" content="index, follow" />
        <link rel="canonical" href="https://geosource.ai/resources/multimedia-and-geo" />
        <component :is="'script'" type="application/ld+json">{{ JSON.stringify(jsonLd) }}</component>
        <component :is="'script'" type="application/ld+json">{{ JSON.stringify(faqJsonLd) }}</component>
    </Head>

    <div class="min-h-screen bg-background text-foreground">
        <!-- Skip Navigation -->
        <SkipNav />

        <!-- Navigation -->
        <ResourceHeader />

        <main id="main-content" role="main">
            <!-- Breadcrumb -->
            <ResourceBreadcrumb :items="breadcrumbItems" />

            <!-- Article -->
            <article class="py-12">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <!-- Header -->
                    <header class="mb-12">
                        <Badge variant="secondary" class="mb-4">
                            <Image class="mr-1 h-3 w-3" aria-hidden="true" />
                            Content Strategy
                        </Badge>
                        <h1 class="text-3xl font-bold tracking-tight sm:text-4xl lg:text-5xl">
                            Multimedia and GEO: When It Helps, When It Hurts
                        </h1>
                        <p class="mt-4 text-lg text-muted-foreground">
                            The conventional advice says load up on images and video for AI visibility. Our recommendation-survival research suggests the opposite for commercial content, and "it depends" for everything else.
                        </p>
                        <div class="mt-4 flex items-center gap-2 text-sm text-muted-foreground">
                            <Calendar class="h-4 w-4" aria-hidden="true" />
                            <time :datetime="modifiedDate">Updated {{ formattedDate }}</time>
                        </div>
                    </header>

                    <!-- Intro -->
                    <section class="mb-12" aria-labelledby="intro-heading">
                        <h2 id="intro-heading" class="sr-only">Introduction</h2>
                        <p class="text-muted-foreground mb-4">
                            For years the advice on multimedia and <Link href="/resources/what-is-geo" class="text-primary hover:underline">Generative Engine Optimization</Link> has been: more is better. Add images. Embed video. Pad the page with diagrams. AI assistants will reward you with citations.
                        </p>
                        <p class="text-muted-foreground mb-4">
                            That guidance has not survived contact with our data. In our <Link href="/blog/ecommerce-recommendation-survival" class="text-primary hover:underline">ecommerce recommendation-survival study</Link>, pages with heavy multimedia were <em>recommended less often</em> by AI assistants in the shopping journey, not more. We rewrote this guide to match what the research actually showed. See <Link href="/research" class="text-primary hover:underline">all our studies</Link> for the broader picture.
                        </p>
                    </section>

                    <!-- Key Findings -->
                    <section class="mb-12" aria-labelledby="key-findings">
                        <h2 id="key-findings" class="text-2xl font-bold mb-6">Key findings</h2>
                        <Card class="border-primary/50 bg-primary/5">
                            <CardContent class="pt-6">
                                <ul class="space-y-3">
                                    <li v-for="finding in keyFindings" :key="finding" class="flex items-start gap-3">
                                        <CheckCircle class="h-5 w-5 text-primary shrink-0 mt-0.5" aria-hidden="true" />
                                        <span>{{ finding }}</span>
                                    </li>
                                </ul>
                            </CardContent>
                        </Card>
                    </section>

                    <Separator class="my-12" />

                    <!-- The Contrarian Take -->
                    <section class="mb-12" aria-labelledby="contrarian">
                        <h2 id="contrarian" class="text-2xl font-bold mb-6">The conventional advice doesn't hold</h2>
                        <p class="text-muted-foreground mb-4">
                            The standard pitch is that multimedia signals topical depth, engagement, and effort — all of which AI assistants supposedly reward. It is a clean story. It is also wrong for the part of the funnel that matters most to ecommerce: the recommendation.
                        </p>
                        <p class="text-muted-foreground mb-4">
                            When we measured which product pages AI assistants actually recommended in real shopping queries, multimedia load was inversely associated with recommendation strength. The pages that survived to the final answer were not the ones with the most video and imagery. They were the ones with the cleanest, most scannable, text-dense product information.
                        </p>
                        <p class="text-muted-foreground">
                            This is one of six cross-study patterns we keep seeing: <strong>content type sets the citation ceiling</strong>, and shopping content rewards different signals than informational content. Treating multimedia as a universal lever is the mistake.
                        </p>
                    </section>

                    <Separator class="my-12" />

                    <!-- Informational vs Commercial -->
                    <section class="mb-12" aria-labelledby="info-vs-commercial">
                        <h2 id="info-vs-commercial" class="text-2xl font-bold mb-6">Informational vs. commercial content</h2>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <Card>
                                <CardContent class="pt-6">
                                    <h3 class="font-semibold mb-3 flex items-center gap-2">
                                        <BookOpen class="h-5 w-5 text-primary" aria-hidden="true" />
                                        Informational content
                                    </h3>
                                    <p class="text-sm text-muted-foreground mb-3">
                                        Definitions, how-tos, condition guides, glossaries, explainers.
                                    </p>
                                    <p class="text-sm text-muted-foreground mb-3">
                                        Multimedia is roughly <strong>neutral</strong>. Use it when it genuinely helps a reader: a diagram that clarifies a concept, a demo that shows the step, an annotated screenshot that saves a paragraph of prose.
                                    </p>
                                    <p class="text-sm text-muted-foreground">
                                        Do not add multimedia expecting an AI citation lift. The text around it is what does the work.
                                    </p>
                                </CardContent>
                            </Card>
                            <Card class="border-red-500/30">
                                <CardContent class="pt-6">
                                    <h3 class="font-semibold mb-3 flex items-center gap-2">
                                        <ShoppingCart class="h-5 w-5 text-red-500" aria-hidden="true" />
                                        Commercial content
                                    </h3>
                                    <p class="text-sm text-muted-foreground mb-3">
                                        Product pages, category pages, comparison pages, shopping content.
                                    </p>
                                    <p class="text-sm text-muted-foreground mb-3">
                                        Heavy multimedia <strong>hurts</strong>. AI assistants appear to prefer clean, scannable, text-dense pages when answering "which one should I buy?"
                                    </p>
                                    <p class="text-sm text-muted-foreground">
                                        Lead with text. Specs, materials, sizing, use cases, differentiators — all in writing. Imagery supports the buyer; it should not replace the description.
                                    </p>
                                </CardContent>
                            </Card>
                        </div>
                    </section>

                    <Separator class="my-12" />

                    <!-- Why this might be happening -->
                    <section class="mb-12" aria-labelledby="hypothesis">
                        <h2 id="hypothesis" class="text-2xl font-bold mb-6">Why this might be happening</h2>
                        <p class="text-muted-foreground mb-4">
                            A working hypothesis, offered tentatively: AI assistants extract from text. Multimedia-heavy pages tend to have <em>less clear text per visible byte</em> — more pixels, fewer parseable sentences. That means the model has less raw material to quote, summarize, or anchor a recommendation against.
                        </p>
                        <p class="text-muted-foreground mb-4">
                            A product page with eight hero images and a forty-word description gives the assistant almost nothing to work with. The same product page with crisp specs, clear use cases, and an honest pros-and-cons paragraph gives the assistant something to actually cite.
                        </p>
                        <p class="text-muted-foreground">
                            This lines up with another cross-study finding: <strong>brand recognition swamps page quality</strong>, but among pages of similar brand strength, text density and structural clarity are what differentiate the ones that get recommended from the ones that get skipped.
                        </p>
                    </section>

                    <Separator class="my-12" />

                    <!-- Accessibility wins -->
                    <section class="mb-12" aria-labelledby="accessibility">
                        <h2 id="accessibility" class="text-2xl font-bold mb-6">Where multimedia still earns its keep</h2>
                        <p class="text-muted-foreground mb-6">
                            The version of multimedia that holds up is the accessible kind. Alt text, captions, and transcripts convert visual content into text the model can parse — and the same text that helps screen-reader users helps AI assistants. Treat accessibility as the brief, not as an afterthought.
                        </p>
                        <Card>
                            <CardContent class="pt-6">
                                <ul class="space-y-3">
                                    <li v-for="item in accessibilityWins" :key="item" class="flex items-start gap-3">
                                        <Accessibility class="h-5 w-5 text-primary shrink-0 mt-0.5" aria-hidden="true" />
                                        <span>{{ item }}</span>
                                    </li>
                                </ul>
                            </CardContent>
                        </Card>
                        <p class="text-muted-foreground mt-6">
                            See <Link href="/resources/ai-accessibility-for-geo" class="text-primary hover:underline">AI Accessibility and GEO</Link> for the longer treatment.
                        </p>
                    </section>

                    <Separator class="my-12" />

                    <!-- Red flags -->
                    <section class="mb-12" aria-labelledby="red-flags">
                        <h2 id="red-flags" class="text-2xl font-bold mb-6">Decorative multimedia is empty calories</h2>
                        <p class="text-muted-foreground mb-6">
                            These patterns look polished to a human visitor and contribute nothing the assistant can actually use. On commercial pages they may actively suppress recommendation strength.
                        </p>
                        <Card class="border-red-500/30 bg-red-500/5">
                            <CardContent class="pt-6">
                                <ul class="space-y-3">
                                    <li v-for="item in decorativeRedFlags" :key="item" class="flex items-start gap-3">
                                        <XCircle class="h-5 w-5 text-red-500 shrink-0 mt-0.5" aria-hidden="true" />
                                        <span>{{ item }}</span>
                                    </li>
                                </ul>
                            </CardContent>
                        </Card>
                    </section>

                    <Separator class="my-12" />

                    <!-- Practical guidance -->
                    <section class="mb-12" aria-labelledby="practical">
                        <h2 id="practical" class="text-2xl font-bold mb-6">What to do instead</h2>
                        <div class="space-y-4">
                            <Card>
                                <CardContent class="pt-6">
                                    <h3 class="font-semibold mb-2">1. Don't strip multimedia that serves the user</h3>
                                    <p class="text-sm text-muted-foreground">
                                        If a video genuinely shows a buyer what they need to see, keep it. The point is to stop <em>adding</em> multimedia expecting an AI citation lift, not to gut your existing experience.
                                    </p>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardContent class="pt-6">
                                    <h3 class="font-semibold mb-2">2. Audit text density on commercial pages</h3>
                                    <p class="text-sm text-muted-foreground">
                                        Open your top product pages. Strip the imagery in your head. Is there enough text left for an AI assistant to write a recommendation? If not, the text is doing too little.
                                    </p>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardContent class="pt-6">
                                    <h3 class="font-semibold mb-2">3. Mirror every visual in text</h3>
                                    <p class="text-sm text-muted-foreground">
                                        If the image shows a spec, write the spec. If the video walks through setup, write the steps. Treat visuals as a second channel, not the only channel.
                                    </p>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardContent class="pt-6">
                                    <h3 class="font-semibold mb-2">4. Invest in alt text and transcripts</h3>
                                    <p class="text-sm text-muted-foreground">
                                        This is the form of multimedia work that genuinely compounds. It helps real users, and it gives AI assistants something to extract.
                                    </p>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardContent class="pt-6">
                                    <h3 class="font-semibold mb-2">5. Optimize the validated pillars first</h3>
                                    <p class="text-sm text-muted-foreground">
                                        Answerability, citation quality, and clear definitions move the needle in our studies. Multimedia is, at best, a wrapper around those.
                                    </p>
                                </CardContent>
                            </Card>
                        </div>
                    </section>

                    <Separator class="my-12" />

                    <!-- Big picture -->
                    <section class="mb-12" aria-labelledby="big-picture">
                        <h2 id="big-picture" class="text-2xl font-bold mb-6">The bigger picture</h2>
                        <Card class="border-amber-500/50 bg-amber-500/5">
                            <CardContent class="pt-6">
                                <div class="flex items-start gap-3">
                                    <FileText class="h-6 w-6 text-amber-500 shrink-0 mt-0.5" aria-hidden="true" />
                                    <div class="space-y-3">
                                        <p class="font-medium text-foreground">
                                            Multimedia is a wrapper around your text. It is not a substitute for it.
                                        </p>
                                        <p class="text-sm text-muted-foreground">
                                            AI assistants quote what they can read. Pages that give the model strong, scannable text win recommendations. Pages that hide their substance behind imagery and video lose them — especially in the commercial moment when a buyer asks "which one should I get?"
                                        </p>
                                        <p class="text-sm text-muted-foreground">
                                            Add multimedia for the reader. Add text for the AI assistant. Make sure those two audiences are looking at the same content.
                                        </p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </section>

                    <Separator class="my-12" />

                    <!-- Related Definitions -->
                    <section class="mb-12" aria-labelledby="related-definitions">
                        <h2 id="related-definitions" class="text-2xl font-bold mb-6">Related Definitions</h2>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <Link
                                v-for="article in relatedDefinitions"
                                :key="article.slug"
                                :href="`/resources/${article.slug}`"
                                class="group"
                            >
                                <Card class="h-full transition-colors hover:border-primary/50">
                                    <CardContent class="pt-6">
                                        <p class="font-medium group-hover:text-primary transition-colors">
                                            {{ article.title }}
                                        </p>
                                        <span class="inline-flex items-center text-sm text-primary mt-2">
                                            Read more
                                            <ArrowRight class="ml-1 h-3 w-3 transition-transform group-hover:translate-x-1" aria-hidden="true" />
                                        </span>
                                    </CardContent>
                                </Card>
                            </Link>
                        </div>
                    </section>

                    <Separator class="my-12" />

                    <!-- Related Resources -->
                    <section aria-labelledby="related">
                        <h2 id="related" class="text-2xl font-bold mb-6">Related Resources</h2>
                        <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3" aria-label="Related resources list">
                            <li
                                v-for="article in relatedResources"
                                :key="article.slug"
                            >
                                <Link
                                    :href="`/resources/${article.slug}`"
                                    class="group block h-full"
                                    :aria-label="`Read more about ${article.title}`"
                                >
                                    <Card class="h-full transition-colors hover:border-primary/50">
                                        <CardContent class="pt-6">
                                            <p class="font-medium group-hover:text-primary transition-colors">
                                                {{ article.title }}
                                            </p>
                                            <span class="inline-flex items-center text-sm text-primary mt-2">
                                                Read more
                                                <ArrowRight class="ml-1 h-3 w-3 transition-transform group-hover:translate-x-1" aria-hidden="true" />
                                            </span>
                                        </CardContent>
                                    </Card>
                                </Link>
                            </li>
                        </ul>
                    </section>

                    <!-- Navigation -->
                    <nav class="mt-12 flex items-center justify-between border-t pt-8" aria-label="Article navigation">
                        <Link href="/resources/question-coverage-for-geo" class="inline-flex items-center text-muted-foreground hover:text-foreground">
                            <ArrowLeft class="mr-2 h-4 w-4" aria-hidden="true" />
                            Previous: Question Coverage
                        </Link>
                        <Link href="/resources" class="inline-flex items-center text-primary hover:underline">
                            View All Resources
                            <ArrowRight class="ml-2 h-4 w-4" aria-hidden="true" />
                        </Link>
                    </nav>
                </div>
            </article>

            <!-- CTA Section -->
            <section class="border-t bg-muted/30 py-12" aria-labelledby="cta-heading">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 text-center">
                    <h2 id="cta-heading" class="text-2xl font-bold">See how your pages actually read to AI assistants</h2>
                    <p class="mt-2 text-muted-foreground">Get a Citation Readiness Score and find out where text density, structure, and answerability stand on your most important pages.</p>
                    <div class="mt-6">
                        <Link href="/register">
                            <Button size="lg" class="gap-2">
                                Get Your Citation Readiness Score
                                <ArrowRight class="h-4 w-4" aria-hidden="true" />
                            </Button>
                        </Link>
                    </div>
                </div>
            </section>
        </main>

        <!-- Footer -->
        <ResourceFooter />
    </div>
</template>
