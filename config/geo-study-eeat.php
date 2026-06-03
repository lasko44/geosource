<?php

/*
|--------------------------------------------------------------------------
| E-E-A-T Follow-Up Study Configuration
|--------------------------------------------------------------------------
|
| 2x2 design probing whether the negative E-E-A-T correlation found in
| the v3 homepage study holds when content type is controlled.
|
| Axes:
|   - industry: healthcare (YMYL, credentials *should* matter)
|              vs saas (technical, credentials shouldn't matter as much)
|   - content_type: educational (definitions, guides — high answerability potential)
|                  vs authority (about, team, credentials — high E-E-A-T potential)
|
| Each URL gets a query that matches what the *page* would answer, not the
| brand. Definition pages get "what is X" queries; authority pages get
| "is X trustworthy / who is behind X" queries.
|
*/

return [
    'entries' => [
        // ─────────────────────────────────────────────────────────────
        // HEALTHCARE × EDUCATIONAL (definitions, conditions, treatments)
        // ─────────────────────────────────────────────────────────────
        [
            'url' => 'https://www.mayoclinic.org/diseases-conditions/type-2-diabetes/symptoms-causes/syc-20351193',
            'domain' => 'mayoclinic.org',
            'industry' => 'healthcare',
            'site_size' => 'large',
            'content_type' => 'educational',
            'query' => 'what are the symptoms of type 2 diabetes',
        ],
        [
            'url' => 'https://my.clevelandclinic.org/health/diseases/5005-migraine-headaches',
            'domain' => 'clevelandclinic.org',
            'industry' => 'healthcare',
            'site_size' => 'large',
            'content_type' => 'educational',
            'query' => 'what causes migraines',
        ],
        [
            'url' => 'https://www.webmd.com/heart-disease/heart-disease-symptoms',
            'domain' => 'webmd.com',
            'industry' => 'healthcare',
            'site_size' => 'large',
            'content_type' => 'educational',
            'query' => 'what are the symptoms of heart disease',
        ],
        [
            'url' => 'https://www.healthline.com/health/high-blood-pressure-hypertension',
            'domain' => 'healthline.com',
            'industry' => 'healthcare',
            'site_size' => 'large',
            'content_type' => 'educational',
            'query' => 'what is high blood pressure',
        ],
        [
            'url' => 'https://www.medicalnewstoday.com/articles/9152',
            'domain' => 'medicalnewstoday.com',
            'industry' => 'healthcare',
            'site_size' => 'medium',
            'content_type' => 'educational',
            'query' => 'what is depression and what are its symptoms',
        ],
        [
            'url' => 'https://www.nhs.uk/conditions/asthma/',
            'domain' => 'nhs.uk',
            'industry' => 'healthcare',
            'site_size' => 'large',
            'content_type' => 'educational',
            'query' => 'what is asthma',
        ],

        // ─────────────────────────────────────────────────────────────
        // HEALTHCARE × AUTHORITY (about, leadership, accreditation)
        // ─────────────────────────────────────────────────────────────
        [
            'url' => 'https://www.mayoclinic.org/about-mayo-clinic',
            'domain' => 'mayoclinic.org',
            'industry' => 'healthcare',
            'site_size' => 'large',
            'content_type' => 'authority',
            'query' => 'is mayo clinic a trustworthy source for medical information',
        ],
        [
            'url' => 'https://my.clevelandclinic.org/about',
            'domain' => 'clevelandclinic.org',
            'industry' => 'healthcare',
            'site_size' => 'large',
            'content_type' => 'authority',
            'query' => 'who runs cleveland clinic and what is its reputation',
        ],
        [
            'url' => 'https://www.webmd.com/about-webmd-policies/about-editorial-policy',
            'domain' => 'webmd.com',
            'industry' => 'healthcare',
            'site_size' => 'large',
            'content_type' => 'authority',
            'query' => 'how does webmd review its medical content',
        ],
        [
            'url' => 'https://www.healthline.com/about',
            'domain' => 'healthline.com',
            'industry' => 'healthcare',
            'site_size' => 'large',
            'content_type' => 'authority',
            'query' => 'is healthline a trustworthy health information source',
        ],
        [
            'url' => 'https://www.medicalnewstoday.com/about',
            'domain' => 'medicalnewstoday.com',
            'industry' => 'healthcare',
            'site_size' => 'medium',
            'content_type' => 'authority',
            'query' => 'who writes for medical news today',
        ],
        [
            'url' => 'https://www.nhs.uk/about-us/',
            'domain' => 'nhs.uk',
            'industry' => 'healthcare',
            'site_size' => 'large',
            'content_type' => 'authority',
            'query' => 'who runs the nhs website',
        ],

        // ─────────────────────────────────────────────────────────────
        // SAAS × EDUCATIONAL (definition / guide content from product blogs)
        // ─────────────────────────────────────────────────────────────
        [
            'url' => 'https://www.salesforce.com/crm/what-is-crm/',
            'domain' => 'salesforce.com',
            'industry' => 'saas',
            'site_size' => 'large',
            'content_type' => 'educational',
            'query' => 'what is crm software',
        ],
        [
            'url' => 'https://www.hubspot.com/marketing-statistics',
            'domain' => 'hubspot.com',
            'industry' => 'saas',
            'site_size' => 'large',
            'content_type' => 'educational',
            'query' => 'what are the latest marketing statistics',
        ],
        [
            'url' => 'https://www.atlassian.com/agile/scrum',
            'domain' => 'atlassian.com',
            'industry' => 'saas',
            'site_size' => 'large',
            'content_type' => 'educational',
            'query' => 'what is scrum methodology',
        ],
        [
            'url' => 'https://www.cloudflare.com/learning/ddos/what-is-a-ddos-attack/',
            'domain' => 'cloudflare.com',
            'industry' => 'saas',
            'site_size' => 'large',
            'content_type' => 'educational',
            'query' => 'what is a ddos attack',
        ],
        [
            'url' => 'https://www.notion.com/help/intro-to-databases',
            'domain' => 'notion.so',
            'industry' => 'saas',
            'site_size' => 'large',
            'content_type' => 'educational',
            'query' => 'what is a notion database',
        ],
        [
            'url' => 'https://zapier.com/blog/what-is-an-api/',
            'domain' => 'zapier.com',
            'industry' => 'saas',
            'site_size' => 'medium',
            'content_type' => 'educational',
            'query' => 'what is an api',
        ],

        // ─────────────────────────────────────────────────────────────
        // SAAS × AUTHORITY (about, team, leadership, company pages)
        // ─────────────────────────────────────────────────────────────
        [
            'url' => 'https://stripe.com/about',
            'domain' => 'stripe.com',
            'industry' => 'saas',
            'site_size' => 'large',
            'content_type' => 'authority',
            'query' => 'who founded stripe and what is the company',
        ],
        [
            'url' => 'https://www.hubspot.com/our-story',
            'domain' => 'hubspot.com',
            'industry' => 'saas',
            'site_size' => 'large',
            'content_type' => 'authority',
            'query' => 'who founded hubspot',
        ],
        [
            'url' => 'https://www.atlassian.com/company',
            'domain' => 'atlassian.com',
            'industry' => 'saas',
            'site_size' => 'large',
            'content_type' => 'authority',
            'query' => 'who runs atlassian',
        ],
        [
            'url' => 'https://www.cloudflare.com/about-overview/',
            'domain' => 'cloudflare.com',
            'industry' => 'saas',
            'site_size' => 'large',
            'content_type' => 'authority',
            'query' => 'who is cloudflare and what do they do',
        ],
        [
            'url' => 'https://www.notion.so/about',
            'domain' => 'notion.so',
            'industry' => 'saas',
            'site_size' => 'large',
            'content_type' => 'authority',
            'query' => 'who founded notion',
        ],
        [
            'url' => 'https://zapier.com/about',
            'domain' => 'zapier.com',
            'industry' => 'saas',
            'site_size' => 'medium',
            'content_type' => 'authority',
            'query' => 'who founded zapier',
        ],
    ],
];
