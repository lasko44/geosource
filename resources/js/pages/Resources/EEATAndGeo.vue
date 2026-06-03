<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import {
    ArrowRight,
    ArrowLeft,
    UserCheck,
    Lightbulb,
    CheckCircle,
    XCircle,
    Calendar,
} from 'lucide-vue-next';
import SkipNav from '@/components/resources/SkipNav.vue';
import ResourceHeader from '@/components/resources/ResourceHeader.vue';
import ResourceFooter from '@/components/resources/ResourceFooter.vue';
import ResourceBreadcrumb from '@/components/resources/ResourceBreadcrumb.vue';

const publishedDate = '2026-01-20';
const publishedDateFormatted = new Date(publishedDate).toLocaleDateString('en-US', { month: 'long', year: 'numeric' });

const breadcrumbItems = [
    { label: 'Resources', href: '/resources' },
    { label: 'E-E-A-T and GEO' },
];

const didNotMove = [
    'Author byline blocks with credentials',
    '"Reviewed by Dr. X" headers',
    '"About the author" sidebars and bio cards',
    'Trust badges, certifications, and award logos',
    'Editorial-policy and fact-checking disclosure pages',
    'Schema markup for author and reviewer roles',
];

const whatDidPredict = [
    {
        title: 'Answerability',
        description: 'Direct, declarative content that answers the question without making the reader dig for it.',
    },
    {
        title: 'Citation Quality',
        description: 'Pointing to authoritative external sources from inside your own content, with enough context that AI can verify the claim.',
    },
    {
        title: 'Definitions',
        description: 'Explicit "X is Y" statements near the top of the page, in the same vocabulary the reader is likely to use.',
    },
];

const relatedDefinitions = [
    { slug: 'what-is-geo', title: 'What Is Generative Engine Optimization (GEO)?' },
    { slug: 'what-is-a-geo-score', title: 'What Is a GEO Score?' },
];

const relatedResources = [
    { slug: 'ai-citations-and-geo', title: 'Citations and GEO: How AI Chooses Sources' },
    { slug: 'how-llms-cite-sources', title: 'How Large Language Models Choose Which Sources to Cite' },
    { slug: 'content-freshness-for-geo', title: 'Content Freshness and GEO' },
    { slug: 'readability-and-geo', title: 'Readability and GEO' },
    { slug: 'ai-accessibility-for-geo', title: 'AI Accessibility and GEO' },
];

const jsonLd = {
    '@context': 'https://schema.org',
    '@type': 'Article',
    headline: 'E-E-A-T and AI Citation: What Our Research Actually Found',
    description: 'E-E-A-T was Google\'s rubric for human quality raters. Across three of our studies, visible E-E-A-T signals did not positively predict whether AI assistants cite a page. Here is what did.',
    url: 'https://geosource.ai/resources/e-e-a-t-and-geo',
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
        '@id': 'https://geosource.ai/resources/e-e-a-t-and-geo',
    },
    about: {
        '@type': 'DefinedTerm',
        name: 'E-E-A-T',
        description: 'Experience, Expertise, Authoritativeness, and Trustworthiness — a framework originally designed for Google\'s human quality raters, not for AI assistant citation behavior.',
    },
};

const faqJsonLd = {
    '@context': 'https://schema.org',
    '@type': 'FAQPage',
    mainEntity: [
        {
            '@type': 'Question',
            name: 'Does E-E-A-T help with AI citations?',
            acceptedAnswer: {
                '@type': 'Answer',
                text: 'In our research, visible E-E-A-T signals — author bylines, "reviewed by" headers, credential boxes, trust badges — did not positively predict whether AI assistants cite a page. In our ecommerce study, heavy E-E-A-T optimization was inversely correlated with being recommended. AI assistants infer credibility from content (clear definitions, direct answers, citing authoritative sources), not from signaling artifacts.',
            },
        },
        {
            '@type': 'Question',
            name: 'Should I remove E-E-A-T signals from my site?',
            acceptedAnswer: {
                '@type': 'Answer',
                text: 'No. E-E-A-T still serves a purpose in traditional search and for human readers who want to evaluate whether to trust a page. If you already have author bios, reviewer credits, and editorial disclosures, leave them. Just do not expect them to move AI citation rates.',
            },
        },
        {
            '@type': 'Question',
            name: 'What does predict AI citation if not E-E-A-T?',
            acceptedAnswer: {
                '@type': 'Answer',
                text: 'Three things showed up consistently in our research: Answerability (direct answers to the question), Citation Quality (citing authoritative external sources from inside your content), and Definitions (explicit "X is Y" statements). Brand recognition and the exact phrasing of the user\'s query also matter — often more than any page-level signal.',
            },
        },
    ],
};
</script>

<template>
    <Head title="E-E-A-T and AI Citation: What Our Research Actually Found - GeoSource.ai">
        <meta name="description" content="E-E-A-T was Google's rubric for human quality raters. Across three of our studies, visible E-E-A-T signals did not positively predict AI citation. Here is what did." />
        <meta property="og:title" content="E-E-A-T and AI Citation: What Our Research Actually Found" />
        <meta property="og:description" content="Visible E-E-A-T signals did not move AI citation rates in our research. Here is what AI assistants actually reward." />
        <meta property="og:type" content="article" />
        <meta property="og:url" content="https://geosource.ai/resources/e-e-a-t-and-geo" />
        <meta property="og:site_name" content="GeoSource.ai" />
        <meta property="article:published_time" content="2026-01-20" />
        <meta property="article:modified_time" content="2026-06-03" />
        <meta property="article:author" content="GeoSource.ai" />
        <meta property="article:section" content="Trust Signals" />
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" content="E-E-A-T and AI Citation: What Our Research Actually Found" />
        <meta name="twitter:description" content="Visible E-E-A-T signals did not move AI citation rates in our research." />
        <meta name="twitter:site" content="@geosource_ai" />
        <meta name="robots" content="index, follow" />
        <link rel="canonical" href="https://geosource.ai/resources/e-e-a-t-and-geo" />
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
                            <UserCheck class="mr-1 h-3 w-3" aria-hidden="true" />
                            Trust Signals
                        </Badge>
                        <h1 class="text-3xl font-bold tracking-tight sm:text-4xl lg:text-5xl">
                            E-E-A-T and AI citation: what our research actually found
                        </h1>
                        <p class="mt-4 text-lg text-muted-foreground">
                            E-E-A-T was Google's framework for human quality raters. It is not the rubric AI assistants use when they decide which page to quote. Here is what our three studies showed — and what to do about it.
                        </p>
                        <div class="mt-4 flex items-center gap-2 text-sm text-muted-foreground">
                            <Calendar class="h-4 w-4" aria-hidden="true" />
                            <time :datetime="publishedDate">{{ publishedDateFormatted }}</time>
                        </div>
                    </header>

                    <!-- Intro -->
                    <section class="mb-12" aria-labelledby="intro-heading">
                        <h2 id="intro-heading" class="sr-only">Introduction</h2>
                        <p class="text-muted-foreground mb-4">
                            If you have done any SEO in the last five years, you know E-E-A-T: Experience, Expertise, Authoritativeness, Trustworthiness. It is the rubric Google's human search quality raters use when grading pages, and over time the SEO industry collapsed it into a set of visible artifacts — author bylines, "reviewed by" headers, credential sidebars, editorial-policy pages, trust badges.
                        </p>
                        <p class="text-muted-foreground mb-4">
                            When generative engines arrived, the natural assumption was that the same signals would transfer. They did not.
                        </p>
                        <p class="text-muted-foreground">
                            Across our <Link href="/research" class="text-primary hover:underline">research line</Link> — the original GEO citation study, an E-E-A-T-focused follow-up, and an ecommerce recommendation-survival study — the visible E-E-A-T signals did not positively predict whether AI assistants cite a page. In the ecommerce study, brands that leaned hard on E-E-A-T styling were recommended <em>less</em>, not more.
                        </p>
                    </section>

                    <Separator class="my-12" />

                    <!-- Key findings -->
                    <section class="mb-12" aria-labelledby="key-findings">
                        <h2 id="key-findings" class="text-2xl font-bold mb-6">Key findings</h2>
                        <ul class="space-y-4">
                            <li class="flex items-start gap-3">
                                <CheckCircle class="h-5 w-5 text-primary shrink-0 mt-1" aria-hidden="true" />
                                <span><strong>E-E-A-T was built for human reviewers, not AI assistants.</strong> The framework was designed so that contractors rating Google search results would converge on similar judgments. AI assistants choosing which source to quote do not run the same checklist.</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <CheckCircle class="h-5 w-5 text-primary shrink-0 mt-1" aria-hidden="true" />
                                <span><strong>Visible E-E-A-T signals did not move AI citation rates in our research.</strong> Author bylines, "Reviewed by Dr. X" labels, credential boxes, and trust badges showed no positive relationship with whether a page was cited.</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <CheckCircle class="h-5 w-5 text-primary shrink-0 mt-1" aria-hidden="true" />
                                <span><strong>In ecommerce, heavy E-E-A-T optimization was actively counterproductive.</strong> The brands AI assistants kept recommending through a shopping conversation were the ones with simple, direct pages — not the ones plastered with editorial credentials.</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <CheckCircle class="h-5 w-5 text-primary shrink-0 mt-1" aria-hidden="true" />
                                <span><strong>Credibility still matters — but AI infers it from content, not signaling.</strong> Clear definitions, direct answers, and references to authoritative external sources are how AI assistants gauge whether a page is worth quoting.</span>
                            </li>
                        </ul>
                    </section>

                    <Separator class="my-12" />

                    <!-- Why E-E-A-T does not transfer -->
                    <section class="mb-12" aria-labelledby="why-not">
                        <h2 id="why-not" class="text-2xl font-bold mb-6">Why E-E-A-T does not transfer to AI search</h2>
                        <p class="text-muted-foreground mb-4">
                            E-E-A-T is a <em>human rubric</em>. It was written so that a contractor rating "is this page trustworthy?" would notice the same things a thoughtful reader would: who wrote this, what are their qualifications, does the site take its own publishing seriously, has anyone vouched for the information.
                        </p>
                        <p class="text-muted-foreground mb-4">
                            That is a reasonable set of questions for a human evaluator. It is not how a generative engine assembles an answer. AI assistants are looking for passages that are directly relevant to the user's query, cleanly stated, internally consistent, and corroborated by other sources the model has already weighted as credible. None of that requires reading a byline.
                        </p>
                        <p class="text-muted-foreground">
                            The result: a page can be loaded with editorial signals and still be invisible to AI, and a page with no byline at all can be the one quoted — because what AI cares about is whether the answer is <em>on the page</em>, not whether the page <em>looks</em> trustworthy.
                        </p>
                    </section>

                    <Separator class="my-12" />

                    <!-- What did not move citation -->
                    <section class="mb-12" aria-labelledby="did-not-move">
                        <h2 id="did-not-move" class="text-2xl font-bold mb-6">What did not move AI citation</h2>
                        <p class="text-muted-foreground mb-6">
                            These are the artifacts the SEO industry built up around E-E-A-T. In our research, none of them positively predicted whether a page was cited by AI assistants:
                        </p>
                        <Card>
                            <CardContent class="pt-6">
                                <ul class="space-y-3">
                                    <li v-for="item in didNotMove" :key="item" class="flex items-start gap-3">
                                        <XCircle class="h-5 w-5 text-muted-foreground shrink-0 mt-0.5" aria-hidden="true" />
                                        <span>{{ item }}</span>
                                    </li>
                                </ul>
                            </CardContent>
                        </Card>
                        <p class="mt-6 text-muted-foreground">
                            This is not a claim that these elements hurt — for most informational content they were neutral. The point is that adding them is not how you increase AI citation rate. Investing more in this layer is investing in the wrong layer.
                        </p>
                        <Card class="mt-6 border-amber-500/50 bg-amber-500/5">
                            <CardContent class="pt-6">
                                <div class="flex items-start gap-3">
                                    <Lightbulb class="h-6 w-6 text-amber-500 shrink-0 mt-0.5" aria-hidden="true" />
                                    <p class="text-muted-foreground">
                                        <strong class="text-foreground">Ecommerce is the exception that is not really an exception.</strong> In our ecommerce study, heavy E-E-A-T styling on product and category pages correlated with <em>fewer</em> recommendations through a shopping conversation. The pages that survived the funnel were the ones that read like a clear product description, not a journalistic feature with editorial credits. Shoppers — and the AI assistants helping them — wanted product facts, not bylines.
                                    </p>
                                </div>
                            </CardContent>
                        </Card>
                    </section>

                    <Separator class="my-12" />

                    <!-- What did predict citation -->
                    <section class="mb-12" aria-labelledby="what-did">
                        <h2 id="what-did" class="text-2xl font-bold mb-6">What did predict AI citation</h2>
                        <p class="text-muted-foreground mb-6">
                            Three on-page signals showed up as positive predictors across our studies. They are content-level signals, not signaling artifacts — they live in the words on the page, not in a sidebar or a badge.
                        </p>
                        <div class="grid gap-6 sm:grid-cols-3">
                            <Card v-for="pillar in whatDidPredict" :key="pillar.title" class="h-full">
                                <CardContent class="pt-6">
                                    <h3 class="font-semibold mb-2">{{ pillar.title }}</h3>
                                    <p class="text-sm text-muted-foreground">{{ pillar.description }}</p>
                                </CardContent>
                            </Card>
                        </div>
                        <p class="mt-6 text-muted-foreground">
                            Two other factors mattered more than any individual page-level signal — and neither is something a content team can fully control:
                        </p>
                        <ul class="mt-4 space-y-3">
                            <li class="flex items-start gap-3 text-muted-foreground">
                                <CheckCircle class="h-5 w-5 text-primary shrink-0 mt-0.5" aria-hidden="true" />
                                <span><strong class="text-foreground">Brand recognition.</strong> Well-known brands got cited even with thin pages; lesser-known brands struggled even with strong content.</span>
                            </li>
                            <li class="flex items-start gap-3 text-muted-foreground">
                                <CheckCircle class="h-5 w-5 text-primary shrink-0 mt-0.5" aria-hidden="true" />
                                <span><strong class="text-foreground">Query phrasing.</strong> The same page could be cited or invisible depending on how the user's question was worded. This was the largest single factor we measured for informational queries.</span>
                            </li>
                        </ul>
                        <p class="mt-6 text-muted-foreground">
                            The full picture — including why citation rate alone is the wrong top-line metric and why platforms agree more than they disagree — is in our <Link href="/blog/what-predicts-ai-citations" class="text-primary hover:underline">cross-study synthesis</Link>.
                        </p>
                    </section>

                    <Separator class="my-12" />

                    <!-- What to do -->
                    <section class="mb-12" aria-labelledby="what-to-do">
                        <h2 id="what-to-do" class="text-2xl font-bold mb-6">What to do with this</h2>
                        <div class="space-y-6">
                            <div>
                                <h3 class="font-semibold mb-2">If you already invested in E-E-A-T for Google</h3>
                                <p class="text-muted-foreground">
                                    Leave it. Author bios, editorial policies, and reviewer credits still play a role in traditional search and they give human readers something to evaluate. The mistake would be doubling down on them as your AI citation strategy. Treat the existing layer as a hygiene investment, not a growth lever.
                                </p>
                            </div>
                            <div>
                                <h3 class="font-semibold mb-2">If you are planning new content</h3>
                                <p class="text-muted-foreground">
                                    Skip the credential theater. Spend your effort on the parts of the page AI assistants actually read: a clear definition near the top, a direct answer to the question the page exists to answer, and references to authoritative external sources where claims need backing. That is where the citation lift comes from in our research.
                                </p>
                            </div>
                            <div>
                                <h3 class="font-semibold mb-2">If you sell things</h3>
                                <p class="text-muted-foreground">
                                    Be careful about importing editorial conventions onto product pages. The brands that survived a multi-turn shopping conversation in our ecommerce study were the ones that read like clean product pages, not magazine articles. State what the product is, who it is for, and how it compares — not who reviewed the page.
                                </p>
                            </div>
                        </div>
                    </section>

                    <Separator class="my-12" />

                    <!-- Key takeaway -->
                    <section class="mb-12" aria-labelledby="takeaway">
                        <h2 id="takeaway" class="text-2xl font-bold mb-6">Key takeaway</h2>
                        <Card class="border-primary bg-primary/5">
                            <CardContent class="pt-6">
                                <p class="text-xl font-medium text-center">
                                    E-E-A-T is a framework for human reviewers, not a recipe for AI citation.<br />
                                    AI assistants reward clarity, direct answers, and good sourcing — not bylines, badges, or credential boxes.
                                </p>
                            </CardContent>
                        </Card>
                    </section>

                    <Separator class="my-12" />

                    <!-- Related Definitions -->
                    <section class="mb-12" aria-labelledby="related-definitions">
                        <h2 id="related-definitions" class="text-2xl font-bold mb-6">Related definitions</h2>
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
                        <h2 id="related" class="text-2xl font-bold mb-6">Related resources</h2>
                        <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3" aria-label="Related resources list">
                            <li v-for="article in relatedResources" :key="article.slug">
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
                    <div class="mt-12 flex items-center justify-between border-t pt-8">
                        <Link href="/resources" class="inline-flex items-center text-muted-foreground hover:text-foreground">
                            <ArrowLeft class="mr-2 h-4 w-4" aria-hidden="true" />
                            Back to Resources
                        </Link>
                        <Link href="/blog/what-predicts-ai-citations" class="inline-flex items-center text-primary hover:underline">
                            Read the cross-study synthesis
                            <ArrowRight class="ml-2 h-4 w-4" aria-hidden="true" />
                        </Link>
                    </div>
                </div>
            </article>

            <!-- CTA Section -->
            <section class="border-t bg-muted/30 py-12" aria-labelledby="cta-heading">
                <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 text-center">
                    <h2 id="cta-heading" class="text-2xl font-bold">See what AI assistants actually reward on your pages</h2>
                    <p class="mt-2 text-muted-foreground">Get a free read on the signals our research validated as drivers of AI citation.</p>
                    <div class="mt-6">
                        <Link href="/register">
                            <Button size="lg" class="gap-2">
                                Get your Citation Readiness Score
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
