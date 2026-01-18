<meta name="description" content="GeoSource.ai analyzes your website's GEO (Generative Engine Optimization) score. Understand how visible and cite-ready your content is for AI systems like ChatGPT, Perplexity, and Claude.">
<meta name="keywords" content="GEO, Generative Engine Optimization, AI SEO, AI search optimization, ChatGPT visibility, AI citations, content optimization">

{{-- Open Graph / Facebook / iMessage / SMS --}}
<meta property="og:type" content="website">
<meta property="og:url" content="{{ config('app.url') }}">
<meta property="og:site_name" content="GeoSource.ai">
<meta property="og:title" content="GeoSource.ai - The GEO Scoring Platform for the AI Search Era">
<meta property="og:description" content="Measure how visible your website is to AI systems like ChatGPT, Perplexity, and Claude. Get your free GEO Score today.">
<meta property="og:image" content="{{ config('app.url') }}/og-image.png">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="GeoSource.ai - Optimize your website for AI search engines">

{{-- Twitter --}}
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:site" content="@geosourceai">
<meta name="twitter:title" content="GeoSource.ai - GEO Scoring Platform">
<meta name="twitter:description" content="Measure how visible your website is to AI systems. Get your free GEO Score.">
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
            'description' => 'GeoSource.ai is a GEO (Generative Engine Optimization) scoring platform that helps websites become visible and citable by AI search systems.'
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
            'name' => 'GeoSource.ai - The GEO Scoring Platform for the AI Search Era',
            'description' => 'GeoSource.ai helps you understand how visible, trustworthy, and cite-ready your website is for generative AI systems like ChatGPT, Perplexity, and Claude.',
            'isPartOf' => [
                '@id' => $appUrl . '/#website'
            ],
            'about' => [
                '@id' => $appUrl . '/#geo-definition'
            ]
        ],
        [
            '@type' => 'DefinedTerm',
            '@id' => $appUrl . '/#geo-definition',
            'name' => 'Generative Engine Optimization (GEO)',
            'description' => 'Generative Engine Optimization (GEO) is the practice of structuring website content so it can be accurately retrieved, understood, and cited by AI-powered search engines.',
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
                    'name' => 'What is GEO (Generative Engine Optimization)?',
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => 'Generative Engine Optimization (GEO) is the practice of structuring website content so it can be accurately retrieved, understood, and cited by AI-powered search engines. Unlike traditional SEO, GEO focuses on clear definitions, structured knowledge, topic authority, machine-readable formatting, and high-confidence answerability.'
                    ]
                ],
                [
                    '@type' => 'Question',
                    'name' => 'Why does GEO matter now?',
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => 'Search behavior is changing fast. Users ask AI instead of Google, AI summarizes instead of listing links, and only a few trusted sources are referenced. If your site is not optimized for GEO, your content may exist but never be cited, your expertise may be invisible to AI, and your traffic may decline even with strong SEO rankings.'
                    ]
                ],
                [
                    '@type' => 'Question',
                    'name' => 'What does GeoSource.ai do?',
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => 'GeoSource.ai analyzes your website the same way AI systems do. We scan your pages, extract structured knowledge, and evaluate how easily AI models can retrieve and trust your content. You receive a clear GEO Score (0-100) with actionable insights.'
                    ]
                ],
                [
                    '@type' => 'Question',
                    'name' => 'What is a GEO Score?',
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => 'Your GEO Score represents how likely an AI system is to understand your content, retrieve it accurately, use it as a trusted source, and cite or reference your site in generated answers. A higher GEO score means your site communicates knowledge clearly - not just keywords.'
                    ]
                ],
                [
                    '@type' => 'Question',
                    'name' => 'How does GEO relate to SEO?',
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => 'GeoSource.ai does not replace SEO - it complements it. SEO optimizes for rankings, while GEO optimizes for answers. Sites that combine both gain traditional search traffic, AI visibility, long-term discoverability, and authority across platforms.'
                    ]
                ]
            ]
        ],
        [
            '@type' => 'SoftwareApplication',
            'name' => 'GeoSource.ai',
            'applicationCategory' => 'BusinessApplication',
            'operatingSystem' => 'Web',
            'description' => 'A GEO (Generative Engine Optimization) scoring platform that analyzes websites for AI search visibility and citation readiness.',
            'offers' => [
                '@type' => 'Offer',
                'price' => '0',
                'priceCurrency' => 'USD',
                'description' => 'Free GEO analysis available'
            ],
            'featureList' => [
                'GEO Score calculation (0-100)',
                'Definition clarity analysis',
                'Topic structure evaluation',
                'Schema and structured data detection',
                'AI answerability assessment',
                'Actionable recommendations'
            ]
        ]
    ]
];
@endphp
{!! json_encode($schemaData, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
