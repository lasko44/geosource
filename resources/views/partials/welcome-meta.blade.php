<meta name="description" content="GeoSource.ai helps you measure and improve how visible your content is inside AI search engines like ChatGPT, Perplexity, Claude, and Gemini. Scan any URL. Get a GEO score. Fix what AI systems don't understand.">
<meta name="keywords" content="GEO, Generative Engine Optimization, AI SEO, AI search optimization, ChatGPT visibility, AI citations, content optimization, AI search visibility">

{{-- Open Graph / Facebook / iMessage / SMS --}}
<meta property="og:type" content="website">
<meta property="og:url" content="{{ config('app.url') }}">
<meta property="og:site_name" content="GeoSource.ai">
<meta property="og:title" content="GeoSource.ai - GEO Software for AI Search Visibility">
<meta property="og:description" content="Measure and improve how visible your content is inside AI search engines like ChatGPT, Perplexity, Claude, and Gemini. Get your free GEO Score today.">
<meta property="og:image" content="{{ config('app.url') }}/og-image.png">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="GeoSource.ai - Optimize your website for AI search engines">

{{-- Twitter --}}
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:site" content="@geosource_ai">
<meta name="twitter:creator" content="@geosource_ai">
<meta name="twitter:title" content="GeoSource.ai - GEO Software for AI Search Visibility">
<meta name="twitter:description" content="Measure and improve how visible your content is inside AI search engines. Get your free GEO Score.">
<meta name="twitter:image" content="{{ config('app.url') }}/og-image.png">

<link rel="canonical" href="{{ config('app.url') }}">

<script type="application/ld+json">
@php
$appUrl = config('app.url');
$schemaData = [
    '@context' => 'https://schema.org',
    '@graph' => [
        [
            '@type' => 'Organization',
            '@id' => $appUrl . '/#organization',
            'name' => 'GeoSource.ai',
            'url' => $appUrl,
            'logo' => [
                '@type' => 'ImageObject',
                'url' => $appUrl . '/favicon.svg'
            ],
            'description' => 'GeoSource.ai is a Generative Engine Optimization (GEO) platform that helps websites become visible and citable by AI search systems like ChatGPT, Perplexity, Claude, and Gemini.',
            'sameAs' => [
                'https://x.com/geosource_ai'
            ],
            'contactPoint' => [
                '@type' => 'ContactPoint',
                'email' => 'support@geosource.ai',
                'contactType' => 'customer support'
            ]
        ],
        [
            '@type' => 'WebSite',
            '@id' => $appUrl . '/#website',
            'url' => $appUrl,
            'name' => 'GeoSource.ai',
            'publisher' => [
                '@id' => $appUrl . '/#organization'
            ]
        ],
        [
            '@type' => 'WebPage',
            '@id' => $appUrl . '/#webpage',
            'url' => $appUrl,
            'name' => 'Generative Engine Optimization (GEO) Software for AI Search Visibility | GeoSource.ai',
            'description' => 'GeoSource.ai helps you measure and improve how visible your content is inside AI search engines like ChatGPT, Perplexity, Claude, and Gemini.',
            'isPartOf' => [
                '@id' => $appUrl . '/#website'
            ],
            'about' => [
                '@id' => $appUrl . '/#geo-definition'
            ],
            'mainEntity' => [
                '@id' => $appUrl . '/#software'
            ]
        ],
        [
            '@type' => 'DefinedTerm',
            '@id' => $appUrl . '/#geo-definition',
            'name' => 'Generative Engine Optimization (GEO)',
            'description' => 'Generative Engine Optimization (GEO) is the process of optimizing website content so AI systems like ChatGPT, Perplexity, Claude, and Gemini can clearly understand, trust, and reference it when generating answers. Unlike SEO, GEO focuses on structure, definitions, answerability, authority, and machine readability.',
            'inDefinedTermSet' => [
                '@type' => 'DefinedTermSet',
                'name' => 'AI and Search Optimization Terms'
            ]
        ],
        [
            '@type' => 'FAQPage',
            '@id' => $appUrl . '/#faq',
            'mainEntity' => [
                [
                    '@type' => 'Question',
                    'name' => 'What is Generative Engine Optimization (GEO)?',
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => 'Generative Engine Optimization (GEO) is the process of optimizing website content so AI systems like ChatGPT, Perplexity, Claude, and Gemini can clearly understand, trust, and reference it when generating answers. Unlike SEO, GEO focuses on structure, definitions, answerability, authority, and machine readability.'
                    ]
                ],
                [
                    '@type' => 'Question',
                    'name' => 'How is GEO different from SEO?',
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => 'SEO focuses on ranking pages in search engine results. GEO focuses on whether AI models can confidently summarize, cite, or reference your content inside generated responses. A page can rank well in Google but still be invisible to AI systems.'
                    ]
                ],
                [
                    '@type' => 'Question',
                    'name' => 'What is a GEO score?',
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => 'A GEO score is a numerical rating from 0 to 100 that measures how optimized a webpage is for AI search engines. GeoSource.ai calculates this score using 12 AI evaluation pillars, including definition clarity, topic authority, citation quality, and answerability.'
                    ]
                ],
                [
                    '@type' => 'Question',
                    'name' => 'Which AI platforms does GeoSource.ai support?',
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => 'GeoSource.ai analyzes content for visibility in major AI platforms, including ChatGPT (OpenAI), Perplexity, Claude, and Gemini. The analysis simulates how large language models evaluate content.'
                    ]
                ],
                [
                    '@type' => 'Question',
                    'name' => 'Can GeoSource.ai help my site appear in AI answers?',
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => 'Yes. GeoSource.ai identifies the specific issues that prevent AI systems from using your content and provides actionable recommendations to improve AI readability and trust. While no tool can guarantee citations, GEO optimization significantly increases your likelihood of being referenced.'
                    ]
                ],
                [
                    '@type' => 'Question',
                    'name' => 'Does GEO replace SEO?',
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => 'No. GEO complements SEO. SEO helps users find your page. GEO helps AI systems use your page. Modern search strategies require both.'
                    ]
                ],
                [
                    '@type' => 'Question',
                    'name' => 'Who is GeoSource.ai for?',
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => 'GeoSource.ai is designed for SaaS founders, content creators, SEO professionals, digital agencies, publishers preparing for AI search, and anyone who wants visibility inside AI-generated results.'
                    ]
                ]
            ]
        ],
        [
            '@type' => 'SoftwareApplication',
            '@id' => $appUrl . '/#software',
            'name' => 'GeoSource.ai',
            'applicationCategory' => 'BusinessApplication',
            'operatingSystem' => 'Web',
            'description' => 'A Generative Engine Optimization (GEO) platform that analyzes websites for AI search visibility and citation readiness. Scan any URL, get a GEO score, and fix what AI systems don\'t understand.',
            'offers' => [
                [
                    '@type' => 'Offer',
                    'name' => 'Free Plan',
                    'price' => '0',
                    'priceCurrency' => 'USD',
                    'description' => '3 scans per month, basic GEO score, top 3 recommendations'
                ],
                [
                    '@type' => 'Offer',
                    'name' => 'Pro Plan',
                    'price' => '39',
                    'priceCurrency' => 'USD',
                    'priceValidUntil' => date('Y-12-31'),
                    'description' => '50 scans per month, full GEO score breakdown, all recommendations, PDF export'
                ],
                [
                    '@type' => 'Offer',
                    'name' => 'Agency Plan',
                    'price' => '99',
                    'priceCurrency' => 'USD',
                    'priceValidUntil' => date('Y-12-31'),
                    'description' => 'Unlimited scans, white-label reports, citation tracking, GA4 AI traffic analytics'
                ]
            ],
            'featureList' => [
                'GEO Score calculation (0-100)',
                'Letter grades (A+ to F)',
                'AI-simulated content analysis',
                '12 GEO evaluation pillars',
                'Definition clarity analysis',
                'Topic authority evaluation',
                'Machine readability assessment',
                'E-E-A-T signal detection',
                'Answerability scoring',
                'Citation quality analysis',
                'Actionable improvement recommendations',
                'Re-scan to track progress',
                'AI citation tracking (Agency)',
                'GA4 AI traffic analytics (Agency)'
            ],
            'screenshot' => $appUrl . '/og-image.png',
            'softwareVersion' => '1.0',
            'creator' => [
                '@id' => $appUrl . '/#organization'
            ]
        ],
        [
            '@type' => 'Service',
            'name' => 'GEO Scanning',
            'description' => 'AI-powered content analysis that evaluates how well your website content performs inside AI search systems like ChatGPT, Perplexity, Claude, and Gemini.',
            'provider' => [
                '@id' => $appUrl . '/#organization'
            ],
            'serviceType' => 'Content Optimization Analysis',
            'areaServed' => 'Worldwide'
        ],
        [
            '@type' => 'Service',
            'name' => 'AI Citation Tracking',
            'description' => 'Track whether AI platforms like ChatGPT, Perplexity, and Claude are citing your website in their generated responses.',
            'provider' => [
                '@id' => $appUrl . '/#organization'
            ],
            'serviceType' => 'AI Visibility Monitoring',
            'areaServed' => 'Worldwide'
        ]
    ]
];
@endphp
{!! json_encode($schemaData, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
