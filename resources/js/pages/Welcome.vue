<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import ThemeSwitcher from '@/components/ThemeSwitcher.vue';
import { dashboard, login, register } from '@/routes';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Search,
    Brain,
    Target,
    FileText,
    CheckCircle,
    XCircle,
    Users,
    BarChart3,
    Zap,
    Globe,
    ArrowRight,
    Sparkles,
    BookOpen,
    Database,
    Link as LinkIcon,
    MessageSquare,
    Building2,
    TrendingUp,
    Menu,
    Mail,
    Shield,
    Eye,
    Bell,
    ChevronDown,
    ChevronUp,
    Terminal,
    Copy,
    Check,
} from 'lucide-vue-next';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog';
import { ref, onMounted } from 'vue';
import { useForm } from '@inertiajs/vue3';

withDefaults(
    defineProps<{
        canRegister: boolean;
        experiment: { variant: string };
    }>(),
    {
        canRegister: true,
        experiment: () => ({ variant: 'control' }),
    },
);

// Guest scan form for experiment variant
const scanForm = useForm({ url: '' });

// Guest citation check form for experiment variant
const citationForm = useForm({ domain: '', query: '' });

// FAQ accordion state
const openFaqIndex = ref<number | null>(null);
const toggleFaq = (index: number) => {
    openFaqIndex.value = openFaqIndex.value === index ? null : index;
};

// MCP promo modal (shown once per visitor)
const MCP_PROMO_KEY = 'geosource_mcp_promo_seen';
const showMcpPromo = ref(false);

onMounted(() => {
    if (localStorage.getItem(MCP_PROMO_KEY) !== 'true') {
        // Small delay so the page loads first
        setTimeout(() => { showMcpPromo.value = true; }, 1500);
    }
});

const dismissMcpPromo = () => {
    showMcpPromo.value = false;
    localStorage.setItem(MCP_PROMO_KEY, 'true');
};

// MCP config copy
const copied = ref(false);
const mcpConfig = `{
  "mcpServers": {
    "geosource": {
      "command": "npx",
      "args": ["-y", "geosource-mcp"]
    }
  }
}`;
const copyConfig = () => {
    navigator.clipboard.writeText(mcpConfig);
    copied.value = true;
    setTimeout(() => { copied.value = false; }, 2000);
};

// Animate on mount
const mounted = ref(false);
onMounted(() => { mounted.value = true; });

// Demo mockup pillars — illustrative only. Shows the validated drivers
// of AI citation from our research line, not the full scoring breakdown.
const pillars = [
    { name: 'Answerability', score: 17, max: 20, description: 'Direct, declarative answers near the top of the page.' },
    { name: 'Citation Quality', score: 15, max: 20, description: 'Linking to authoritative external sources.' },
    { name: 'Clear Definitions', score: 18, max: 20, description: 'Explicit "X is Y" statements early on.' },
    { name: 'Readability', score: 14, max: 20, description: 'Short sentences, clean structure, scannable prose.' },
    { name: 'AI Accessibility', score: 13, max: 20, description: 'Crawlers and LLMs can actually reach and parse the page.' },
];

const faqItems = [
    {
        question: 'What is Generative Engine Optimization (GEO)?',
        answer: 'Generative Engine Optimization (GEO) is the practice of structuring content so AI assistants like ChatGPT, Perplexity, Claude, and Gemini can confidently cite it when answering user questions. Unlike SEO, GEO focuses on whether AI assistants can extract and quote your content, not on ranking in a search results page.',
    },
    {
        question: 'What are the differences between GEO and SEO?',
        answer: 'SEO optimizes for ranking links. GEO optimizes for being quoted. A page can rank well in Google and still be invisible to AI assistants — they pick sources based on whether the page contains a clear, citable answer, not on backlinks or keyword density. Most teams need both, but they are different problems.',
    },
    {
        question: 'How is the GEO Score calculated?',
        answer: 'GeoSource.ai analyzes pages across multiple research-grounded pillars and returns a composite GEO Score plus a Citation Readiness Score. The Citation Readiness Score focuses specifically on the signals our research line validated as predictors of AI citation — Answerability, Citation Quality, and Definitions. For ecommerce pages we also produce a Recommendation Readiness Score, which weights signals differently because our research showed AI assistants behave differently on commercial queries.',
    },
    {
        question: 'How do I optimize a page for AI search?',
        answer: 'Based on our research line: put the direct answer in the first sentence of each major section. Cite authoritative external sources from inside your content. Use explicit "X is Y" definitions for key terms near the top. Make sure AI crawlers can actually reach the page (server-side rendering, no aggressive bot blocking). Brand recognition and how the user phrases the question matter at least as much as anything on the page itself.',
    },
    {
        question: 'What makes GeoSource.ai different?',
        answer: 'GeoSource.ai is backed by an original research line — four studies of how AI assistants actually cite and recommend content. Our scoring reflects what those studies validated as drivers of citation, not generic SEO checklists adapted for AI. We also track real citations across ChatGPT, Perplexity, Claude, and Gemini so you can see what is actually happening, not just predicted scores.',
    },
    {
        question: 'Are FAQ sections, E-E-A-T signals, or topical authority useful for AI search?',
        answer: 'Less than the conventional advice suggests. In our research, visible E-E-A-T signals (author bylines, "expert reviewed by" badges) did not positively predict AI citation, and topical-authority depth was a weak or negative predictor. FAQ sections still help with accessibility and traditional SEO, but they are not the citation lever. The signals our studies validated are direct answers, citation quality, and clear definitions.',
    },
    {
        question: 'Which AI platforms does GeoSource.ai support?',
        answer: 'GeoSource.ai analyzes and tracks citations across ChatGPT (OpenAI), Perplexity, Claude (Anthropic), and Google Gemini. Our research found these platforms agree more than they disagree on which sources to cite, so the same optimizations tend to work across all of them.',
    },
    {
        question: 'Does GEO replace SEO?',
        answer: 'No. GEO complements SEO. SEO helps users find your page; GEO helps AI assistants quote it. You need both — and they reward different things, which is why an SEO-strong page can still be invisible to AI search.',
    },
];
</script>

<template>
    <Head>
        <title>GEO Score Tool for AI Search Optimization | GeoSource.ai</title>
        <meta name="description" content="Free GEO score tool to measure AI search visibility. Optimize content for ChatGPT, Perplexity & Claude with actionable recommendations." />
        <meta name="keywords" content="GEO score tool, AI search visibility tool, Generative Engine Optimization, AI content analyzer, AI citation tracker, GEO vs SEO, AI search optimization, MCP server" />
        <meta name="robots" content="index, follow" />
        <meta property="og:title" content="GEO Score Tool for AI Search Optimization | GeoSource.ai" />
        <meta property="og:description" content="Free GEO score tool to measure AI search visibility. Optimize content for ChatGPT, Perplexity & Claude." />
        <meta property="og:type" content="website" />
        <meta property="og:url" content="https://geosource.ai/" />
        <meta property="og:image" content="https://geosource.ai/images/og/geosource-homepage.png" />
        <meta property="og:image:width" content="1200" />
        <meta property="og:image:height" content="630" />
        <meta property="og:site_name" content="GeoSource.ai" />
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:site" content="@geosource_ai" />
        <meta name="twitter:creator" content="@geosource_ai" />
        <meta name="twitter:title" content="GEO Score Tool for AI Search Optimization | GeoSource.ai" />
        <meta name="twitter:description" content="Free GEO score tool to measure AI search visibility. Optimize content for ChatGPT, Perplexity & Claude." />
        <meta name="twitter:image" content="https://geosource.ai/images/og/geosource-homepage.png" />
        <link rel="canonical" href="https://geosource.ai/" />
        <component :is="'script'" type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@graph": [
                {
                    "@type": "Organization",
                    "@id": "https://geosource.ai/#organization",
                    "name": "GeoSource.ai",
                    "url": "https://geosource.ai",
                    "logo": "https://geosource.ai/images/logo.png",
                    "description": "GeoSource.ai is the leading GEO score tool and AI search optimization platform for measuring and improving content visibility in AI search engines.",
                    "sameAs": ["https://x.com/geosource_ai"]
                },
                {
                    "@type": "WebSite",
                    "@id": "https://geosource.ai/#website",
                    "url": "https://geosource.ai",
                    "name": "GeoSource.ai",
                    "publisher": {"@id": "https://geosource.ai/#organization"},
                    "description": "GEO Score Tool and AI Search Visibility Platform"
                },
                {
                    "@type": "SoftwareApplication",
                    "@id": "https://geosource.ai/#software",
                    "name": "GeoSource.ai GEO Score Tool",
                    "applicationCategory": "BusinessApplication",
                    "operatingSystem": "Web",
                    "description": "Research-backed GEO score tool that measures AI search optimization across multiple research-grounded pillars. Backed by an original four-study research line on what actually predicts AI citation.",
                    "offers": {
                        "@type": "AggregateOffer",
                        "lowPrice": "0",
                        "highPrice": "99",
                        "priceCurrency": "USD",
                        "offerCount": "3"
                    },
                    "featureList": [
                        "GEO Score Analysis (0-100)",
                        "Citation Readiness Score",
                        "Recommendation Readiness Score for ecommerce",
                        "AI Citation Tracker across ChatGPT, Perplexity, Claude, Gemini",
                        "Research-grounded pillar scoring",
                        "AI Visibility Optimization Recommendations",
                        "MCP Server for Claude Desktop Integration"
                    ]
                },
                {
                    "@type": "FAQPage",
                    "@id": "https://geosource.ai/#faq",
                    "mainEntity": [
                        {
                            "@type": "Question",
                            "name": "What is Generative Engine Optimization (GEO)?",
                            "acceptedAnswer": {
                                "@type": "Answer",
                                "text": "Generative Engine Optimization (GEO) is the process of optimizing website content so AI systems like ChatGPT, Perplexity, Claude, and Gemini can clearly understand, trust, and reference it when generating answers. Unlike SEO, GEO focuses on structure, definitions, answerability, authority, and machine readability."
                            }
                        },
                        {
                            "@type": "Question",
                            "name": "What are the GEO vs SEO differences?",
                            "acceptedAnswer": {
                                "@type": "Answer",
                                "text": "SEO focuses on ranking pages in search engine results. GEO focuses on whether AI models can confidently summarize, cite, or reference your content inside generated responses. A page can rank well in Google but still be invisible to AI systems. SEO helps users find your page while GEO helps AI systems use your page."
                            }
                        },
                        {
                            "@type": "Question",
                            "name": "What is a GEO score?",
                            "acceptedAnswer": {
                                "@type": "Answer",
                                "text": "A GEO score measures how optimized a webpage is for AI search engines. GeoSource.ai analyzes pages across research-grounded pillars and returns a composite GEO Score plus a Citation Readiness Score that focuses on the signals our research line validated as drivers of AI citation."
                            }
                        },
                        {
                            "@type": "Question",
                            "name": "How to optimize for AI search engines?",
                            "acceptedAnswer": {
                                "@type": "Answer",
                                "text": "Put the direct answer in the first sentence of each section. Cite authoritative external sources from inside your content. Use explicit definitions for key terms near the top. Make sure AI crawlers can reach the page. Brand recognition and how the user phrases the question matter at least as much as on-page signals."
                            }
                        },
                        {
                            "@type": "Question",
                            "name": "Which AI platforms does GeoSource.ai support?",
                            "acceptedAnswer": {
                                "@type": "Answer",
                                "text": "GeoSource.ai analyzes content for visibility in major AI platforms, including ChatGPT (OpenAI), Perplexity, Claude, and Gemini. The analysis simulates how large language models evaluate content."
                            }
                        }
                    ]
                }
            ]
        }
        </component>
    </Head>

    <div class="min-h-screen bg-background text-foreground">
        <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-50 focus:px-4 focus:py-2 focus:bg-primary focus:text-primary-foreground focus:rounded">
            Skip to main content
        </a>

        <!-- MCP Promo Modal -->
        <Dialog :open="showMcpPromo" @update:open="(val: boolean) => { if (!val) dismissMcpPromo(); }">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-primary/10">
                        <Terminal class="h-7 w-7 text-primary" />
                    </div>
                    <DialogTitle class="text-center text-xl">GEOSource now works with MCP</DialogTitle>
                    <DialogDescription class="text-center">
                        Run scans, track citations, and monitor GEO performance from Claude Desktop, Cursor, Windsurf, or any MCP-compatible AI client.
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-3 pt-1">
                    <ul class="space-y-2.5 text-sm text-muted-foreground">
                        <li class="flex items-start gap-2.5">
                            <CheckCircle class="mt-0.5 h-4 w-4 shrink-0 text-emerald-500" />
                            Ask your AI assistant to scan any page and get a GEO score instantly
                        </li>
                        <li class="flex items-start gap-2.5">
                            <CheckCircle class="mt-0.5 h-4 w-4 shrink-0 text-emerald-500" />
                            Track whether ChatGPT, Perplexity, and Gemini are citing your content
                        </li>
                        <li class="flex items-start gap-2.5">
                            <CheckCircle class="mt-0.5 h-4 w-4 shrink-0 text-emerald-500" />
                            Get AI-powered optimization suggestions without leaving your workflow
                        </li>
                    </ul>
                </div>

                <div class="flex flex-col gap-2 pt-2">
                    <Link :href="$page.props.auth.user ? '/mcp' : register()" @click="dismissMcpPromo">
                        <Button class="w-full gap-2">
                            {{ $page.props.auth.user ? 'View Setup Guide' : 'Get Started Free' }}
                            <ArrowRight class="h-4 w-4" />
                        </Button>
                    </Link>
                    <Button variant="ghost" class="w-full" @click="dismissMcpPromo">
                        Maybe later
                    </Button>
                </div>
            </DialogContent>
        </Dialog>

        <!-- Navigation -->
        <header class="sticky top-0 z-50 border-b bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60" role="banner">
            <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-2">
                    <Globe class="h-7 w-7 text-primary" aria-hidden="true" />
                    <span class="text-lg font-bold">GeoSource<span class="text-primary">.ai</span></span>
                </div>
                <nav class="hidden items-center gap-1 sm:flex" aria-label="Main navigation">
                    <Link href="/pricing">
                        <Button variant="ghost">Pricing</Button>
                    </Link>
                    <Link href="/research">
                        <Button variant="ghost">Research</Button>
                    </Link>
                    <Link href="/resources">
                        <Button variant="ghost">Learn</Button>
                    </Link>
                    <Link
                        v-if="$page.props.auth.user"
                        :href="dashboard()"
                    >
                        <Button variant="outline">Dashboard</Button>
                    </Link>
                    <template v-else>
                        <Link :href="login()">
                            <Button variant="ghost">Log in</Button>
                        </Link>
                        <Link v-if="canRegister" :href="register()">
                            <Button>Get Started Free</Button>
                        </Link>
                    </template>
                    <ThemeSwitcher />
                </nav>
                <div class="flex items-center gap-2 sm:hidden">
                    <ThemeSwitcher />
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button variant="ghost" size="icon" aria-label="Open menu">
                                <Menu class="h-5 w-5" aria-hidden="true" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="w-48">
                            <DropdownMenuItem as-child><Link href="/pricing" class="w-full">Pricing</Link></DropdownMenuItem>
                            <DropdownMenuItem as-child><Link href="/research" class="w-full">Research</Link></DropdownMenuItem>
                            <DropdownMenuItem as-child><Link href="/resources" class="w-full">Learn</Link></DropdownMenuItem>
                            <DropdownMenuItem v-if="$page.props.auth.user" as-child><Link :href="dashboard()" class="w-full">Dashboard</Link></DropdownMenuItem>
                            <template v-else>
                                <DropdownMenuItem as-child><Link :href="login()" class="w-full">Log in</Link></DropdownMenuItem>
                                <DropdownMenuItem v-if="canRegister" as-child><Link :href="register()" class="w-full font-medium text-primary">Get Started</Link></DropdownMenuItem>
                            </template>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </div>
        </header>

        <main id="main-content" role="main">
            <!-- ============================================ -->
            <!-- HERO -->
            <!-- ============================================ -->
            <section class="relative overflow-hidden bg-muted/30 pb-20 pt-16 dark:bg-[#0c0a14] sm:pb-28 sm:pt-24" aria-labelledby="hero-heading">
                <!-- Background effects -->
                <div class="pointer-events-none absolute inset-0" aria-hidden="true">
                    <div class="absolute left-1/2 top-0 h-[600px] w-[900px] -translate-x-1/2 -translate-y-1/4 rounded-full bg-[radial-gradient(ellipse,hsl(262_80%_55%/0.08),transparent_70%)] dark:bg-[radial-gradient(ellipse,hsl(262_80%_55%/0.15),transparent_70%)]" />
                    <div class="absolute right-0 top-1/3 h-[400px] w-[400px] rounded-full bg-[radial-gradient(circle,hsl(170_80%_45%/0.04),transparent_70%)] dark:bg-[radial-gradient(circle,hsl(170_80%_45%/0.06),transparent_70%)]" />
                </div>

                <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="grid items-center gap-12 lg:grid-cols-2 lg:gap-16">
                        <!-- Left: Copy -->
                        <div
                            class="transition-all duration-700"
                            :class="mounted ? 'translate-y-0 opacity-100' : 'translate-y-6 opacity-0'"
                        >
                            <div class="mb-6 inline-flex items-center gap-2 rounded-full border border-primary/30 bg-primary/10 px-4 py-1.5 text-sm text-primary">
                                <Sparkles class="h-3.5 w-3.5" aria-hidden="true" />
                                Now available as an MCP server
                            </div>

                            <h1 id="hero-heading" class="text-4xl font-bold leading-[1.1] tracking-tight sm:text-5xl lg:text-[3.5rem]">
                                Is your content visible to
                                <span class="bg-gradient-to-r from-primary to-emerald-500 bg-clip-text text-transparent">AI search?</span>
                            </h1>

                            <p class="mt-6 max-w-lg text-lg leading-relaxed text-muted-foreground">
                                <strong class="text-foreground">GeoSource.ai is a Generative Engine Optimization (GEO) platform</strong> that measures whether AI search engines like <a href="https://openai.com/index/searchgpt-prototype/" class="text-primary hover:underline" target="_blank" rel="noopener">ChatGPT</a>, <a href="https://www.perplexity.ai/" class="text-primary hover:underline" target="_blank" rel="noopener">Perplexity</a>, and <a href="https://www.anthropic.com/claude" class="text-primary hover:underline" target="_blank" rel="noopener">Claude</a> can cite your content. Backed by <a href="/blog/geo-citation-study" class="text-primary hover:underline">original citation research</a> — we measure what actually predicts AI citations.
                            </p>

                            <!-- Experiment: scan_input variant shows URL input -->
                            <div v-if="experiment.variant === 'scan_input'" class="mt-8">
                                <form
                                    class="flex flex-col gap-3 sm:flex-row"
                                    @submit.prevent="scanForm.post('/experiment/scan')"
                                >
                                    <div class="relative flex-1">
                                        <Search class="absolute left-3.5 top-1/2 h-4.5 w-4.5 -translate-y-1/2 text-muted-foreground" aria-hidden="true" />
                                        <input
                                            v-model="scanForm.url"
                                            type="url"
                                            placeholder="https://example.com/your-page"
                                            required
                                            class="h-12 w-full rounded-lg border bg-background pl-11 pr-4 text-base shadow-sm transition-colors placeholder:text-muted-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                                        />
                                    </div>
                                    <Button
                                        type="submit"
                                        size="lg"
                                        class="h-12 shrink-0 gap-2 px-6 text-base font-semibold"
                                        :disabled="scanForm.processing"
                                    >
                                        Scan Free
                                        <ArrowRight class="h-4 w-4" aria-hidden="true" />
                                    </Button>
                                </form>
                                <p v-if="scanForm.errors.url" class="mt-2 text-sm text-red-500">{{ scanForm.errors.url }}</p>
                                <span class="mt-3 block text-sm text-muted-foreground">No credit card required. Creates a free account to view results.</span>
                            </div>

                            <!-- Experiment: citation_check variant shows domain + query input -->
                            <div v-else-if="experiment.variant === 'citation_check'" class="mt-8">
                                <form
                                    class="flex flex-col gap-3"
                                    @submit.prevent="citationForm.post('/experiment/citation-check')"
                                >
                                    <div class="flex flex-col gap-3 sm:flex-row">
                                        <div class="relative flex-1">
                                            <Globe class="absolute left-3.5 top-1/2 h-4.5 w-4.5 -translate-y-1/2 text-muted-foreground" aria-hidden="true" />
                                            <input
                                                v-model="citationForm.domain"
                                                type="text"
                                                placeholder="yourdomain.com"
                                                required
                                                class="h-12 w-full rounded-lg border bg-background pl-11 pr-4 text-base shadow-sm transition-colors placeholder:text-muted-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                                            />
                                        </div>
                                        <div class="relative flex-1">
                                            <Search class="absolute left-3.5 top-1/2 h-4.5 w-4.5 -translate-y-1/2 text-muted-foreground" aria-hidden="true" />
                                            <input
                                                v-model="citationForm.query"
                                                type="text"
                                                placeholder="e.g. best project management tools"
                                                required
                                                class="h-12 w-full rounded-lg border bg-background pl-11 pr-4 text-base shadow-sm transition-colors placeholder:text-muted-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                                            />
                                        </div>
                                    </div>
                                    <Button
                                        type="submit"
                                        size="lg"
                                        class="h-12 gap-2 px-6 text-base font-semibold sm:self-start"
                                        :disabled="citationForm.processing"
                                    >
                                        Check If AI Cites You
                                        <ArrowRight class="h-4 w-4" aria-hidden="true" />
                                    </Button>
                                </form>
                                <p v-if="citationForm.errors.domain" class="mt-2 text-sm text-red-500">{{ citationForm.errors.domain }}</p>
                                <p v-if="citationForm.errors.query" class="mt-2 text-sm text-red-500">{{ citationForm.errors.query }}</p>
                                <span class="mt-3 block text-sm text-muted-foreground">Free check across ChatGPT, Perplexity, and Claude. No account required.</span>
                            </div>

                            <!-- Fallback: CTA button -->
                            <div v-else class="mt-8 flex flex-wrap items-center gap-4">
                                <Link :href="register()">
                                    <Button size="lg" class="h-12 gap-2 px-6 text-base font-semibold">
                                        Scan Your First Page Free
                                        <ArrowRight class="h-4 w-4" aria-hidden="true" />
                                    </Button>
                                </Link>
                                <span class="text-sm text-muted-foreground">No credit card required</span>
                            </div>

                            <div class="mt-10 flex flex-wrap items-center gap-6 text-sm text-muted-foreground">
                                <span class="flex items-center gap-1.5">
                                    <CheckCircle class="h-4 w-4 text-emerald-500" aria-hidden="true" />
                                    Free forever tier
                                </span>
                                <span class="flex items-center gap-1.5">
                                    <CheckCircle class="h-4 w-4 text-emerald-500" aria-hidden="true" />
                                    Research-grounded scoring
                                </span>
                                <span class="flex items-center gap-1.5">
                                    <CheckCircle class="h-4 w-4 text-emerald-500" aria-hidden="true" />
                                    AI-powered fixes
                                </span>
                            </div>
                        </div>

                        <!-- Right: Product Mockup -->
                        <div
                            class="relative transition-all delay-200 duration-700"
                            :class="mounted ? 'translate-y-0 opacity-100' : 'translate-y-10 opacity-0'"
                            aria-hidden="true"
                        >
                            <!-- Scan Result Card -->
                            <div class="rounded-2xl border bg-card p-6 shadow-2xl shadow-primary/5">
                                <!-- Top bar -->
                                <div class="mb-5 flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <div class="h-3 w-3 rounded-full bg-red-500/60" />
                                        <div class="h-3 w-3 rounded-full bg-yellow-500/60" />
                                        <div class="h-3 w-3 rounded-full bg-green-500/60" />
                                    </div>
                                    <span class="rounded-md bg-muted px-2.5 py-1 font-mono text-xs text-muted-foreground">geosource.ai/scans/a3f8...</span>
                                </div>

                                <!-- Score header -->
                                <div class="flex items-center gap-5">
                                    <div class="flex h-20 w-20 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-400/20 to-emerald-500/10 ring-1 ring-emerald-400/20">
                                        <span class="text-3xl font-black text-emerald-500 dark:text-emerald-400">B+</span>
                                    </div>
                                    <div>
                                        <div class="flex items-baseline gap-2">
                                            <span class="text-4xl font-bold">74.2</span>
                                            <span class="text-lg text-muted-foreground">/ 100</span>
                                        </div>
                                        <p class="mt-0.5 font-mono text-xs text-muted-foreground">example.com/blog/ai-trends</p>
                                    </div>
                                </div>

                                <!-- Mini pillar bars -->
                                <div class="mt-6 space-y-3">
                                    <div v-for="pillar in pillars" :key="pillar.name" class="group">
                                        <div class="mb-1 flex items-center justify-between text-xs">
                                            <span class="text-muted-foreground">{{ pillar.name }}</span>
                                            <span class="font-mono text-muted-foreground">{{ pillar.score }}/{{ pillar.max }}</span>
                                        </div>
                                        <div class="h-1.5 overflow-hidden rounded-full bg-muted">
                                            <div
                                                class="h-full rounded-full transition-all duration-1000"
                                                :class="[
                                                    pillar.score / pillar.max >= 0.8 ? 'bg-emerald-500 dark:bg-emerald-400' :
                                                    pillar.score / pillar.max >= 0.6 ? 'bg-blue-500 dark:bg-blue-400' :
                                                    pillar.score / pillar.max >= 0.4 ? 'bg-yellow-500 dark:bg-yellow-400' : 'bg-red-500 dark:bg-red-400'
                                                ]"
                                                :style="{ width: mounted ? `${(pillar.score / pillar.max) * 100}%` : '0%' }"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <!-- Suggestion preview -->
                                <div class="mt-5 rounded-lg border border-amber-500/20 bg-amber-50 px-4 py-3 dark:bg-amber-500/5">
                                    <div class="flex items-start gap-2">
                                        <Zap class="mt-0.5 h-3.5 w-3.5 shrink-0 text-amber-500 dark:text-amber-400" />
                                        <p class="text-xs leading-relaxed text-amber-800 dark:text-amber-200/80">
                                            <span class="font-medium text-amber-900 dark:text-amber-300">High priority:</span>
                                            Add explicit definitions for key terms. AI systems need clear "X is Y" statements to cite confidently.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ============================================ -->
            <!-- MCP INTEGRATION — front and center -->
            <!-- ============================================ -->
            <section class="relative border-y bg-muted/50 dark:bg-muted/20" aria-labelledby="mcp-heading">
                <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 sm:py-20 lg:px-8">
                    <div class="grid items-center gap-10 lg:grid-cols-2 lg:gap-16">
                        <!-- Left: Info -->
                        <div>
                            <Badge variant="secondary" class="mb-4">
                                <Terminal class="mr-1.5 h-3 w-3" aria-hidden="true" />
                                New: MCP Integration
                            </Badge>
                            <h2 id="mcp-heading" class="text-3xl font-bold tracking-tight sm:text-4xl">
                                Run GEO scans from any MCP client
                            </h2>
                            <p class="mt-4 text-lg text-muted-foreground">
                                Connect GEOSource via the Model Context Protocol. Use Claude Desktop, Cursor, Windsurf, or any MCP-compatible client to scan pages, track citations, and monitor your GEO performance.
                            </p>
                            <ul class="mt-6 space-y-3 text-sm text-muted-foreground">
                                <li class="flex items-center gap-2.5">
                                    <div class="flex h-5 w-5 items-center justify-center rounded-full bg-emerald-500/10">
                                        <CheckCircle class="h-3.5 w-3.5 text-emerald-500 dark:text-emerald-400" aria-hidden="true" />
                                    </div>
                                    Secure browser-based OAuth — credentials never leave your browser
                                </li>
                                <li class="flex items-center gap-2.5">
                                    <div class="flex h-5 w-5 items-center justify-center rounded-full bg-emerald-500/10">
                                        <CheckCircle class="h-3.5 w-3.5 text-emerald-500 dark:text-emerald-400" aria-hidden="true" />
                                    </div>
                                    13 tools: scans, citations, tokens, dashboard, and more
                                </li>
                                <li class="flex items-center gap-2.5">
                                    <div class="flex h-5 w-5 items-center justify-center rounded-full bg-emerald-500/10">
                                        <CheckCircle class="h-3.5 w-3.5 text-emerald-500 dark:text-emerald-400" aria-hidden="true" />
                                    </div>
                                    Works with any MCP-compatible client — one-command setup
                                </li>
                            </ul>
                            <div class="mt-8 flex flex-wrap gap-3">
                                <Link :href="$page.props.auth.user ? '/mcp' : register()">
                                    <Button size="lg" class="gap-2">
                                        {{ $page.props.auth.user ? 'Setup Guide' : 'Get Started Free' }}
                                        <ArrowRight class="h-4 w-4" aria-hidden="true" />
                                    </Button>
                                </Link>
                            </div>
                        </div>

                        <!-- Right: Terminal -->
                        <div class="relative" aria-hidden="true">
                            <div class="overflow-hidden rounded-xl border bg-zinc-950 shadow-2xl">
                                <!-- Terminal header -->
                                <div class="flex items-center justify-between border-b border-white/5 px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <div class="h-2.5 w-2.5 rounded-full bg-[#ff5f57]" />
                                        <div class="h-2.5 w-2.5 rounded-full bg-[#febc2e]" />
                                        <div class="h-2.5 w-2.5 rounded-full bg-[#28c840]" />
                                    </div>
                                    <span class="font-mono text-[11px] text-zinc-600">mcp_config.json</span>
                                    <button
                                        class="flex items-center gap-1 rounded px-2 py-0.5 text-xs text-zinc-500 transition-colors hover:bg-white/5 hover:text-zinc-300"
                                        @click="copyConfig"
                                    >
                                        <Check v-if="copied" class="h-3 w-3 text-emerald-400" />
                                        <Copy v-else class="h-3 w-3" />
                                        {{ copied ? 'Copied' : 'Copy' }}
                                    </button>
                                </div>
                                <!-- Code (always dark for terminal aesthetic) -->
                                <pre class="overflow-x-auto p-5 font-mono text-[13px] leading-relaxed"><span class="text-zinc-600">&#123;</span>
  <span class="text-violet-400">"mcpServers"</span><span class="text-zinc-600">:</span> <span class="text-zinc-600">&#123;</span>
    <span class="text-violet-400">"geosource"</span><span class="text-zinc-600">:</span> <span class="text-zinc-600">&#123;</span>
      <span class="text-violet-400">"command"</span><span class="text-zinc-600">:</span> <span class="text-emerald-400">"npx"</span><span class="text-zinc-600">,</span>
      <span class="text-violet-400">"args"</span><span class="text-zinc-600">:</span> <span class="text-zinc-600">[</span><span class="text-emerald-400">"-y"</span><span class="text-zinc-600">,</span> <span class="text-emerald-400">"geosource-mcp"</span><span class="text-zinc-600">]</span>
    <span class="text-zinc-600">&#125;</span>
  <span class="text-zinc-600">&#125;</span>
<span class="text-zinc-600">&#125;</span></pre>
                            </div>
                            <!-- Floating tool badge -->
                            <div class="absolute -bottom-4 -right-2 rounded-lg border bg-card px-3 py-2 shadow-lg sm:-right-4">
                                <div class="flex items-center gap-2 text-xs text-muted-foreground">
                                    <span class="flex h-5 w-5 items-center justify-center rounded bg-emerald-500/10">
                                        <CheckCircle class="h-3 w-3 text-emerald-500 dark:text-emerald-400" />
                                    </span>
                                    13 tools connected
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ============================================ -->
            <!-- GEO vs SEO -->
            <!-- ============================================ -->
            <section class="py-20 sm:py-28" aria-labelledby="seo-geo-heading">
                <article class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="mx-auto max-w-3xl">
                        <h2 id="seo-geo-heading" class="text-center text-3xl font-bold tracking-tight sm:text-4xl">
                            GEO vs SEO: the shift you can't ignore
                        </h2>
                        <p class="mx-auto mt-4 max-w-2xl text-center text-lg text-muted-foreground">
                            <strong>AI search vs SEO</strong> is the defining question for content strategy. AI platforms like <a href="https://blog.google/products/search/generative-ai-google-search-may-2024/" class="text-primary hover:underline" target="_blank" rel="noopener">Google AI Overviews</a> and <a href="https://www.perplexity.ai/" class="text-primary hover:underline" target="_blank" rel="noopener">Perplexity</a> don't rank pages — they select answers. If your content isn't optimized for AI citation, it's invisible.
                        </p>

                        <div class="mt-10 overflow-hidden rounded-xl border">
                            <table class="w-full text-left text-sm">
                                <thead>
                                    <tr class="border-b bg-muted/50">
                                        <th class="px-6 py-4 font-semibold text-muted-foreground">Traditional SEO</th>
                                        <th class="px-6 py-4 font-semibold">Generative Engine Optimization</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    <tr v-for="(row, i) in [
                                        ['Optimizes for page rankings', 'Optimizes for AI citations'],
                                        ['Targets keywords', 'Targets semantic clarity'],
                                        ['Measures backlinks', 'Measures content structure & authority'],
                                        ['Helps users find your page', 'Helps AI systems use your page'],
                                        ['Result: blue link in SERPs', 'Result: cited in AI answers'],
                                    ]" :key="i" class="group">
                                        <td class="px-6 py-3.5 text-muted-foreground">{{ row[0] }}</td>
                                        <td class="px-6 py-3.5 font-medium">{{ row[1] }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-10 grid gap-4 sm:grid-cols-3">
                            <Card v-for="point in [
                                { icon: Brain, title: 'Answerable', desc: 'A direct, declarative answer to the question, near the top of the page' },
                                { icon: LinkIcon, title: 'Well-sourced', desc: 'Citing authoritative external sources so AI can verify the claim' },
                                { icon: FileText, title: 'Citable', desc: 'Clean text the model can lift as a self-contained quote' },
                            ]" :key="point.title" class="text-center">
                                <CardContent class="flex flex-col items-center pt-6">
                                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-primary/10">
                                        <component :is="point.icon" class="h-5 w-5 text-primary" aria-hidden="true" />
                                    </div>
                                    <h3 class="mt-3 font-semibold">{{ point.title }}</h3>
                                    <p class="mt-1.5 text-sm text-muted-foreground">{{ point.desc }}</p>
                                </CardContent>
                            </Card>
                        </div>

                        <aside class="mt-10 rounded-xl border bg-card p-8 text-center" role="note">
                            <p class="text-lg text-muted-foreground">If AI can't confidently summarize your content,</p>
                            <p class="mt-1 text-2xl font-bold text-primary">it won't reference it.</p>
                        </aside>
                    </div>
                </article>
            </section>

            <!-- ============================================ -->
            <!-- RESEARCH PROOF — first section after hero -->
            <!-- ============================================ -->
            <section class="border-t bg-primary/5 py-20 sm:py-28" aria-labelledby="research-heading">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="mx-auto max-w-3xl text-center mb-12">
                        <Badge variant="secondary" class="mb-4">
                            <BarChart3 class="mr-1.5 h-3 w-3" aria-hidden="true" />
                            Original Research
                        </Badge>
                        <h2 id="research-heading" class="text-3xl font-bold tracking-tight sm:text-4xl">
                            Backed by data, not assumptions
                        </h2>
                        <p class="mt-4 text-muted-foreground text-lg">
                            Our four-study research line tested which content signals actually predict whether AI assistants cite a website — and which signals don't, even when conventional wisdom says they should. The scoring engine reflects what the studies validated, not generic SEO checklists adapted for AI.
                        </p>
                    </div>

                    <div class="mx-auto max-w-4xl">
                        <div class="grid gap-6 sm:grid-cols-3 mb-8">
                            <div class="rounded-xl border bg-card p-6">
                                <MessageSquare class="h-6 w-6 text-primary mb-3" aria-hidden="true" />
                                <div class="text-sm font-semibold">Answerability is the lever</div>
                                <div class="text-sm text-muted-foreground mt-2 leading-relaxed">Pages that put the direct answer near the top are cited far more often than pages that bury it under preamble.</div>
                            </div>
                            <div class="rounded-xl border bg-card p-6">
                                <LinkIcon class="h-6 w-6 text-primary mb-3" aria-hidden="true" />
                                <div class="text-sm font-semibold">Citation quality matters</div>
                                <div class="text-sm text-muted-foreground mt-2 leading-relaxed">Pages that themselves cite authoritative external sources are more likely to be cited by AI assistants.</div>
                            </div>
                            <div class="rounded-xl border bg-card p-6">
                                <XCircle class="h-6 w-6 text-amber-500 mb-3" aria-hidden="true" />
                                <div class="text-sm font-semibold">E-E-A-T didn't move the needle</div>
                                <div class="text-sm text-muted-foreground mt-2 leading-relaxed">Visible E-E-A-T signals — author bylines, "expert reviewed by" labels — did not positively predict citation in our studies.</div>
                            </div>
                        </div>

                        <div class="text-center flex flex-col sm:flex-row sm:justify-center gap-3">
                            <Link href="/research" class="inline-flex items-center justify-center gap-2 text-primary font-medium hover:underline">
                                Browse the research line
                                <ArrowRight class="h-4 w-4" aria-hidden="true" />
                            </Link>
                            <span class="hidden sm:inline text-muted-foreground">·</span>
                            <Link href="/blog/what-predicts-ai-citations" class="inline-flex items-center justify-center gap-2 text-primary font-medium hover:underline">
                                Read the cross-study synthesis
                                <ArrowRight class="h-4 w-4" aria-hidden="true" />
                            </Link>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ============================================ -->
            <!-- HOW IT WORKS -->
            <!-- ============================================ -->
            <section class="border-t bg-muted/30 py-20 sm:py-28" aria-labelledby="how-heading">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="mx-auto max-w-3xl">
                        <h2 id="how-heading" class="text-center text-3xl font-bold tracking-tight sm:text-4xl">
                            How to optimize for AI search engines
                        </h2>
                        <p class="mx-auto mt-4 max-w-2xl text-center text-lg text-muted-foreground">
                            GeoSource.ai is an AI content performance scanner. Enter any URL and see exactly how AI systems evaluate your content.
                        </p>

                        <div class="mt-12 space-y-8">
                            <div v-for="(step, i) in [
                                {
                                    num: '01',
                                    title: 'Paste any URL',
                                    desc: 'Enter a page URL. GeoSource fetches and analyzes your content the way AI systems do — evaluating structure, definitions, authority, and machine readability.',
                                },
                                {
                                    num: '02',
                                    title: 'Get your GEO score',
                                    desc: 'Receive a composite GEO Score plus a Citation Readiness Score — our research-grounded predictor of AI citation likelihood. Ecommerce pages also get a Recommendation Readiness Score. Each pillar shows what to fix, with letter grades from A+ to F.',
                                },
                                {
                                    num: '03',
                                    title: 'Fix what matters',
                                    desc: 'AI-powered suggestions tell you exactly what to change — from adding definitions to restructuring headings. Prioritized by impact so you fix the highest-value issues first.',
                                },
                            ]" :key="i" class="flex gap-6">
                                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border bg-background font-mono text-lg font-bold text-primary">
                                    {{ step.num }}
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold">{{ step.title }}</h3>
                                    <p class="mt-1.5 text-muted-foreground">{{ step.desc }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-10 text-center">
                            <Link :href="register()">
                                <Button size="lg" class="gap-2">
                                    Start Your Free Scan
                                    <ArrowRight class="h-4 w-4" aria-hidden="true" />
                                </Button>
                            </Link>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ============================================ -->
            <!-- GEO SCORE EXPLAINED -->
            <!-- ============================================ -->
            <section class="border-t py-20 sm:py-28" aria-labelledby="score-heading">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="mx-auto max-w-3xl">
                        <h2 id="score-heading" class="text-center text-3xl font-bold tracking-tight sm:text-4xl">
                            How the GEO Score is built
                        </h2>

                        <div class="mt-8 rounded-xl border-l-4 border-primary bg-primary/5 p-6">
                            <p class="text-lg font-semibold">Two scores, not one</p>
                            <p class="mt-2 text-muted-foreground">
                                Every scan returns a composite <strong>GEO Score</strong> plus a <strong>Citation Readiness Score</strong> tuned to the signals our research validated as drivers of AI citation. Ecommerce pages also receive a <strong>Recommendation Readiness Score</strong>, weighted differently because our research showed AI assistants behave differently on shopping queries — the same signals that help informational content can actively hurt commercial content.
                            </p>
                        </div>

                        <!-- Validated drivers -->
                        <div class="mt-10">
                            <p class="text-center text-sm font-semibold uppercase tracking-wider text-muted-foreground mb-4">What our research validated as drivers</p>
                            <div class="grid gap-3 sm:grid-cols-3">
                                <div class="rounded-xl border-2 border-primary/30 bg-primary/5 p-5">
                                    <MessageSquare class="h-5 w-5 text-primary mb-2" aria-hidden="true" />
                                    <div class="text-sm font-semibold">Answerability</div>
                                    <div class="text-xs text-muted-foreground mt-1.5 leading-relaxed">Direct, declarative answers near the top of the page.</div>
                                </div>
                                <div class="rounded-xl border-2 border-primary/30 bg-primary/5 p-5">
                                    <LinkIcon class="h-5 w-5 text-primary mb-2" aria-hidden="true" />
                                    <div class="text-sm font-semibold">Citation Quality</div>
                                    <div class="text-xs text-muted-foreground mt-1.5 leading-relaxed">Citing authoritative external sources from inside your content.</div>
                                </div>
                                <div class="rounded-xl border-2 border-primary/30 bg-primary/5 p-5">
                                    <BookOpen class="h-5 w-5 text-primary mb-2" aria-hidden="true" />
                                    <div class="text-sm font-semibold">Definitions</div>
                                    <div class="text-xs text-muted-foreground mt-1.5 leading-relaxed">Explicit "X is Y" statements early on so the model can lift them.</div>
                                </div>
                            </div>
                        </div>

                        <!-- Supporting pillars -->
                        <div class="mt-8">
                            <p class="text-center text-sm font-semibold uppercase tracking-wider text-muted-foreground mb-4">Plus supporting pillars</p>
                            <div class="grid gap-2 sm:grid-cols-3">
                                <div class="flex items-center gap-3 rounded-lg border p-3">
                                    <Eye class="h-4 w-4 shrink-0 text-primary" aria-hidden="true" />
                                    <span class="text-sm font-medium">Readability</span>
                                </div>
                                <div class="flex items-center gap-3 rounded-lg border p-3">
                                    <Globe class="h-4 w-4 shrink-0 text-primary" aria-hidden="true" />
                                    <span class="text-sm font-medium">AI Accessibility</span>
                                </div>
                                <div class="flex items-center gap-3 rounded-lg border p-3">
                                    <TrendingUp class="h-4 w-4 shrink-0 text-primary" aria-hidden="true" />
                                    <span class="text-sm font-medium">Content Freshness</span>
                                </div>
                            </div>
                        </div>

                        <p class="mt-8 text-center text-sm text-muted-foreground">
                            See the underlying <Link href="/research" class="text-primary hover:underline">research line</Link> or the <Link href="/blog/what-predicts-ai-citations" class="text-primary hover:underline">cross-study synthesis</Link> for how each pillar was validated.
                        </p>
                    </div>
                </div>
            </section>

            <!-- ============================================ -->
            <!-- AI CITATION TRACKING -->
            <!-- ============================================ -->
            <section class="border-t bg-muted/30 py-20 sm:py-28" aria-labelledby="citation-heading">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="mx-auto max-w-3xl">
                        <h2 id="citation-heading" class="text-center text-3xl font-bold tracking-tight sm:text-4xl">
                            AI citation tracking & monitoring
                        </h2>
                        <p class="mx-auto mt-4 max-w-2xl text-center text-lg text-muted-foreground">
                            Know when <a href="https://openai.com/index/searchgpt-prototype/" class="text-primary hover:underline" target="_blank" rel="noopener">ChatGPT</a>, <a href="https://www.perplexity.ai/" class="text-primary hover:underline" target="_blank" rel="noopener">Perplexity</a>, or <a href="https://www.anthropic.com/claude" class="text-primary hover:underline" target="_blank" rel="noopener">Claude</a> actually mentions your brand. Track AI citations across every major AI platform.
                        </p>
                        <div class="mt-6 flex flex-wrap justify-center gap-3">
                            <span v-for="platform in [
                                { name: 'ChatGPT', color: 'bg-emerald-500' },
                                { name: 'Perplexity', color: 'bg-teal-500' },
                                { name: 'Claude', color: 'bg-amber-500' },
                                { name: 'Gemini', color: 'bg-blue-500' },
                            ]" :key="platform.name" class="flex items-center gap-2 rounded-full border px-3 py-1 text-sm">
                                <span :class="[platform.color, 'h-2 w-2 rounded-full']" aria-hidden="true" />
                                {{ platform.name }}
                            </span>
                        </div>
                        <Card class="mt-8">
                            <CardContent class="pt-6">
                                <ul class="space-y-3.5">
                                    <li class="flex items-start gap-3">
                                        <Bell class="mt-0.5 h-5 w-5 shrink-0 text-primary" aria-hidden="true" />
                                        <span>Get alerts when citations appear or disappear across AI platforms</span>
                                    </li>
                                    <li class="flex items-start gap-3">
                                        <Eye class="mt-0.5 h-5 w-5 shrink-0 text-primary" aria-hidden="true" />
                                        <span>See the exact AI responses that reference your site</span>
                                    </li>
                                    <li class="flex items-start gap-3">
                                        <TrendingUp class="mt-0.5 h-5 w-5 shrink-0 text-primary" aria-hidden="true" />
                                        <span>Benchmark your AI visibility against competitors</span>
                                    </li>
                                </ul>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </section>

            <!-- ============================================ -->
            <!-- PRICING -->
            <!-- ============================================ -->
            <section class="border-t py-20 sm:py-28" aria-labelledby="plans-heading">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="mx-auto max-w-3xl">
                        <h2 id="plans-heading" class="text-center text-3xl font-bold tracking-tight sm:text-4xl">
                            Start free. Pay only when you need more.
                        </h2>
                        <p class="mx-auto mt-4 max-w-2xl text-center text-lg text-muted-foreground">
                            Run unlimited basic scans on the free tier. Upgrade to tokens for Pro and Full analysis with the complete research-grounded scoring breakdown.
                        </p>

                        <div class="mt-10 grid gap-5 sm:grid-cols-3">
                            <Card v-for="plan in [
                                { name: 'Free', price: '$0', unit: 'forever', desc: 'Unlimited basic scans with the core citation pillars', cta: 'Get Started', highlight: false },
                                { name: 'Tokens', price: '$5', unit: 'starting', desc: 'Pro and Full scans with the complete pillar breakdown', cta: 'Buy Tokens', highlight: true },
                                { name: 'Team', price: '$99', unit: '/month', desc: 'White-label, GA4, teams, 1000 tokens included', cta: 'Contact Us', highlight: false },
                            ]" :key="plan.name" :class="plan.highlight ? 'border-primary ring-1 ring-primary' : ''">
                                <CardContent class="flex flex-col items-center pt-6 text-center">
                                    <Badge v-if="plan.highlight" class="mb-2">Most Popular</Badge>
                                    <h3 class="text-lg font-bold" :class="plan.highlight ? 'text-primary' : ''">{{ plan.name }}</h3>
                                    <div class="mt-2 flex items-baseline gap-1">
                                        <span class="text-3xl font-bold">{{ plan.price }}</span>
                                        <span class="text-sm text-muted-foreground">{{ plan.unit }}</span>
                                    </div>
                                    <p class="mt-3 text-sm text-muted-foreground">{{ plan.desc }}</p>
                                    <Link :href="plan.name === 'Team' ? '/pricing' : register()" class="mt-4 w-full">
                                        <Button :variant="plan.highlight ? 'default' : 'outline'" class="w-full">
                                            {{ plan.cta }}
                                        </Button>
                                    </Link>
                                </CardContent>
                            </Card>
                        </div>

                        <p class="mt-6 text-center text-sm text-muted-foreground">
                            Tokens never expire.
                            <Link href="/pricing" class="font-medium text-primary hover:underline">See full pricing details</Link>
                        </p>
                    </div>
                </div>
            </section>

            <!-- ============================================ -->
            <!-- FAQ -->
            <!-- ============================================ -->
            <section class="border-t bg-muted/30 py-20 sm:py-28" aria-labelledby="faq-heading">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="mx-auto max-w-3xl">
                        <h2 id="faq-heading" class="text-center text-3xl font-bold tracking-tight sm:text-4xl">
                            Frequently asked questions about GEO
                        </h2>
                        <div class="mt-10 space-y-3" role="list" aria-label="Frequently asked questions">
                            <div
                                v-for="(faq, index) in faqItems"
                                :key="index"
                                class="overflow-hidden rounded-xl border bg-card transition-colors"
                                role="listitem"
                            >
                                <button
                                    :id="`faq-button-${index}`"
                                    class="flex w-full items-center justify-between px-6 py-5 text-left"
                                    :aria-expanded="openFaqIndex === index"
                                    :aria-controls="`faq-panel-${index}`"
                                    @click="toggleFaq(index)"
                                >
                                    <span class="pr-4 font-semibold">{{ faq.question }}</span>
                                    <ChevronDown
                                        class="h-5 w-5 shrink-0 text-muted-foreground transition-transform duration-200"
                                        :class="openFaqIndex === index ? 'rotate-180' : ''"
                                        aria-hidden="true"
                                    />
                                </button>
                                <div
                                    v-show="openFaqIndex === index"
                                    :id="`faq-panel-${index}`"
                                    :aria-labelledby="`faq-button-${index}`"
                                    class="px-6 pb-5"
                                    role="region"
                                >
                                    <p class="text-muted-foreground">{{ faq.answer }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ============================================ -->
            <!-- TRUST / E-E-A-T -->
            <!-- ============================================ -->
            <section class="border-t py-12 sm:py-16">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="mx-auto max-w-3xl text-center">
                        <p class="text-sm text-muted-foreground">
                            Backed by an original <a href="/research" class="text-primary hover:underline">four-study research line</a> on what predicts AI citation · Trusted by teams optimizing for <a href="https://openai.com/index/searchgpt-prototype/" class="text-primary hover:underline" target="_blank" rel="noopener">ChatGPT</a>, <a href="https://www.perplexity.ai/" class="text-primary hover:underline" target="_blank" rel="noopener">Perplexity</a>, <a href="https://www.anthropic.com/claude" class="text-primary hover:underline" target="_blank" rel="noopener">Claude</a>, and <a href="https://blog.google/products/search/generative-ai-google-search-may-2024/" class="text-primary hover:underline" target="_blank" rel="noopener">Google AI Overviews</a>
                        </p>
                    </div>
                </div>
            </section>

            <!-- ============================================ -->
            <!-- FINAL CTA -->
            <!-- ============================================ -->
            <section class="relative overflow-hidden border-t py-20 sm:py-28" aria-labelledby="cta-heading">
                <div class="pointer-events-none absolute inset-0" aria-hidden="true">
                    <div class="absolute left-1/2 top-1/2 h-[500px] w-[700px] -translate-x-1/2 -translate-y-1/2 rounded-full bg-[radial-gradient(ellipse,hsl(262_80%_55%/0.08),transparent_70%)] dark:bg-[radial-gradient(ellipse,hsl(262_80%_55%/0.12),transparent_70%)]" />
                </div>
                <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="mx-auto max-w-2xl text-center">
                        <h2 id="cta-heading" class="text-3xl font-bold tracking-tight sm:text-4xl">
                            AI search is already here
                        </h2>
                        <p class="mt-4 text-lg text-muted-foreground">
                            If your content isn't optimized for AI systems, it won't appear in AI-generated answers. GeoSource.ai helps you stay visible as search evolves.
                        </p>
                        <div class="mt-8">
                            <Link :href="register()">
                                <Button size="lg" class="h-12 gap-2 px-8 text-base font-semibold">
                                    Start Your Free GEO Scan
                                    <ArrowRight class="h-4 w-4" aria-hidden="true" />
                                </Button>
                            </Link>
                        </div>
                        <p class="mt-4 text-sm text-muted-foreground">No credit card required. Unlimited basic scans forever.</p>
                    </div>
                </div>
            </section>
        </main>

        <!-- Footer -->
        <footer class="border-t py-12" role="contentinfo">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col items-center gap-6">
                    <div class="flex flex-wrap items-center justify-center gap-4">
                        <div class="flex items-center gap-2 rounded-lg bg-primary/10 px-4 py-2">
                            <Mail class="h-4 w-4 text-primary" aria-hidden="true" />
                            <a href="mailto:support@geosource.ai" class="text-sm font-medium text-primary hover:underline">
                                support@geosource.ai
                            </a>
                        </div>
                        <a href="https://x.com/geosource_ai" target="_blank" rel="noopener noreferrer" class="flex items-center gap-2 rounded-lg bg-primary/10 px-4 py-2 text-sm font-medium text-primary transition-colors hover:bg-primary/20" aria-label="Follow GeoSource.ai on X">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                            </svg>
                            Follow us
                        </a>
                    </div>
                    <div class="flex w-full flex-col items-center justify-between gap-4 sm:flex-row">
                        <div class="flex items-center gap-2">
                            <Globe class="h-5 w-5 text-primary" aria-hidden="true" />
                            <span class="text-sm font-semibold">GeoSource<span class="text-primary">.ai</span></span>
                        </div>
                        <p class="text-xs text-muted-foreground">
                            &copy; {{ new Date().getFullYear() }} GeoSource.ai. All rights reserved.
                        </p>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>
