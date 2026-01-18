<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"  @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Inline script to detect system dark mode preference and apply it immediately --}}
        <script>
            (function() {
                const appearance = '{{ $appearance ?? "system" }}';

                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                }
            })();
        </script>

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: oklch(1 0 0);
            }

            html.dark {
                background-color: oklch(0.145 0 0);
            }
        </style>

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @if($page['component'] === 'Welcome')
        <meta name="description" content="GeoSource.ai analyzes your website's GEO (Generative Engine Optimization) score. Understand how visible and cite-ready your content is for AI systems like ChatGPT, Perplexity, and Claude.">
        <meta name="keywords" content="GEO, Generative Engine Optimization, AI SEO, AI search optimization, ChatGPT visibility, AI citations, content optimization">
        <meta property="og:title" content="GeoSource.ai - The GEO Scoring Platform for the AI Search Era">
        <meta property="og:description" content="Understand how visible, trustworthy, and cite-ready your website is for generative AI systems.">
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ config('app.url') }}">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="GeoSource.ai - GEO Scoring Platform">
        <meta name="twitter:description" content="Measure your website's AI visibility and citation readiness.">
        <link rel="canonical" href="{{ config('app.url') }}">

        <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@graph": [
                {
                    "@type": "Organization",
                    "@id": "{{ config('app.url') }}/#organization",
                    "name": "GeoSource.ai",
                    "url": "{{ config('app.url') }}",
                    "logo": {
                        "@type": "ImageObject",
                        "url": "{{ config('app.url') }}/favicon.svg"
                    },
                    "description": "GeoSource.ai is a GEO (Generative Engine Optimization) scoring platform that helps websites become visible and citable by AI search systems."
                },
                {
                    "@type": "WebSite",
                    "@id": "{{ config('app.url') }}/#website",
                    "url": "{{ config('app.url') }}",
                    "name": "GeoSource.ai",
                    "publisher": {
                        "@id": "{{ config('app.url') }}/#organization"
                    }
                },
                {
                    "@type": "WebPage",
                    "@id": "{{ config('app.url') }}/#webpage",
                    "url": "{{ config('app.url') }}",
                    "name": "GeoSource.ai - The GEO Scoring Platform for the AI Search Era",
                    "description": "GeoSource.ai helps you understand how visible, trustworthy, and cite-ready your website is for generative AI systems like ChatGPT, Perplexity, and Claude.",
                    "isPartOf": {
                        "@id": "{{ config('app.url') }}/#website"
                    },
                    "about": {
                        "@id": "{{ config('app.url') }}/#geo-definition"
                    }
                },
                {
                    "@type": "DefinedTerm",
                    "@id": "{{ config('app.url') }}/#geo-definition",
                    "name": "Generative Engine Optimization (GEO)",
                    "description": "Generative Engine Optimization (GEO) is the practice of structuring website content so it can be accurately retrieved, understood, and cited by AI-powered search engines.",
                    "inDefinedTermSet": {
                        "@type": "DefinedTermSet",
                        "name": "AI and Search Optimization Terms"
                    }
                },
                {
                    "@type": "FAQPage",
                    "@id": "{{ config('app.url') }}/#faq",
                    "mainEntity": [
                        {
                            "@type": "Question",
                            "name": "What is GEO (Generative Engine Optimization)?",
                            "acceptedAnswer": {
                                "@type": "Answer",
                                "text": "Generative Engine Optimization (GEO) is the practice of structuring website content so it can be accurately retrieved, understood, and cited by AI-powered search engines. Unlike traditional SEO, GEO focuses on clear definitions, structured knowledge, topic authority, machine-readable formatting, and high-confidence answerability."
                            }
                        },
                        {
                            "@type": "Question",
                            "name": "Why does GEO matter now?",
                            "acceptedAnswer": {
                                "@type": "Answer",
                                "text": "Search behavior is changing fast. Users ask AI instead of Google, AI summarizes instead of listing links, and only a few trusted sources are referenced. If your site is not optimized for GEO, your content may exist but never be cited, your expertise may be invisible to AI, and your traffic may decline even with strong SEO rankings."
                            }
                        },
                        {
                            "@type": "Question",
                            "name": "What does GeoSource.ai do?",
                            "acceptedAnswer": {
                                "@type": "Answer",
                                "text": "GeoSource.ai analyzes your website the same way AI systems do. We scan your pages, extract structured knowledge, and evaluate how easily AI models can retrieve and trust your content. You receive a clear GEO Score (0-100) with actionable insights."
                            }
                        },
                        {
                            "@type": "Question",
                            "name": "What is a GEO Score?",
                            "acceptedAnswer": {
                                "@type": "Answer",
                                "text": "Your GEO Score represents how likely an AI system is to understand your content, retrieve it accurately, use it as a trusted source, and cite or reference your site in generated answers. A higher GEO score means your site communicates knowledge clearly — not just keywords."
                            }
                        },
                        {
                            "@type": "Question",
                            "name": "How does GEO relate to SEO?",
                            "acceptedAnswer": {
                                "@type": "Answer",
                                "text": "GeoSource.ai does not replace SEO — it complements it. SEO optimizes for rankings, while GEO optimizes for answers. Sites that combine both gain traditional search traffic, AI visibility, long-term discoverability, and authority across platforms."
                            }
                        }
                    ]
                },
                {
                    "@type": "SoftwareApplication",
                    "name": "GeoSource.ai",
                    "applicationCategory": "BusinessApplication",
                    "operatingSystem": "Web",
                    "description": "A GEO (Generative Engine Optimization) scoring platform that analyzes websites for AI search visibility and citation readiness.",
                    "offers": {
                        "@type": "Offer",
                        "price": "0",
                        "priceCurrency": "USD",
                        "description": "Free GEO analysis available"
                    },
                    "featureList": [
                        "GEO Score calculation (0-100)",
                        "Definition clarity analysis",
                        "Topic structure evaluation",
                        "Schema and structured data detection",
                        "AI answerability assessment",
                        "Actionable recommendations"
                    ]
                }
            ]
        }
        </script>
        @endif

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        @vite(['resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
