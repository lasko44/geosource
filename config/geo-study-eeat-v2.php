<?php

/*
|--------------------------------------------------------------------------
| E-E-A-T Follow-Up Study v2 (study_version=v5-eeat)
|--------------------------------------------------------------------------
|
| Redesign of v4-eeat to fix the brand-name confound. In v4, authority
| queries named the brand whose page we were checking ("is mayo clinic
| trustworthy") while educational queries did not ("what causes migraines").
| That gave authority pages an unfair citation advantage.
|
| In v2, ALL queries are concept-based and brand-free. AI platforms are
| free to cite any source they consider relevant. Whether a brand's own
| page (educational or authority) gets cited is now a fair test.
|
| Expanded to n=10 per cell (40 URLs) for better statistical power on
| within-cell correlations.
|
*/

return [
    'entries' => [
        // ─────────────────────────────────────────────────────────────
        // HEALTHCARE × EDUCATIONAL (10) — concept queries about conditions
        // ─────────────────────────────────────────────────────────────
        ['url' => 'https://www.mayoclinic.org/diseases-conditions/type-2-diabetes/symptoms-causes/syc-20351193', 'domain' => 'mayoclinic.org', 'industry' => 'healthcare', 'site_size' => 'large', 'content_type' => 'educational', 'query' => 'what are the symptoms of type 2 diabetes'],
        ['url' => 'https://my.clevelandclinic.org/health/diseases/5005-migraine-headaches', 'domain' => 'clevelandclinic.org', 'industry' => 'healthcare', 'site_size' => 'large', 'content_type' => 'educational', 'query' => 'what causes migraines'],
        ['url' => 'https://www.webmd.com/heart-disease/heart-disease-symptoms', 'domain' => 'webmd.com', 'industry' => 'healthcare', 'site_size' => 'large', 'content_type' => 'educational', 'query' => 'what are the symptoms of heart disease'],
        ['url' => 'https://www.healthline.com/health/high-blood-pressure-hypertension', 'domain' => 'healthline.com', 'industry' => 'healthcare', 'site_size' => 'large', 'content_type' => 'educational', 'query' => 'what is high blood pressure'],
        ['url' => 'https://www.medicalnewstoday.com/articles/9152', 'domain' => 'medicalnewstoday.com', 'industry' => 'healthcare', 'site_size' => 'medium', 'content_type' => 'educational', 'query' => 'what is depression and what are its symptoms'],
        ['url' => 'https://www.nhs.uk/conditions/asthma/', 'domain' => 'nhs.uk', 'industry' => 'healthcare', 'site_size' => 'large', 'content_type' => 'educational', 'query' => 'what is asthma'],
        ['url' => 'https://medlineplus.gov/lungcancer.html', 'domain' => 'medlineplus.gov', 'industry' => 'healthcare', 'site_size' => 'large', 'content_type' => 'educational', 'query' => 'what are the symptoms of lung cancer'],
        ['url' => 'https://www.nhlbi.nih.gov/health/heart-attack', 'domain' => 'nhlbi.nih.gov', 'industry' => 'healthcare', 'site_size' => 'large', 'content_type' => 'educational', 'query' => 'what are the warning signs of a heart attack'],
        ['url' => 'https://www.nimh.nih.gov/health/topics/anxiety-disorders', 'domain' => 'nimh.nih.gov', 'industry' => 'healthcare', 'site_size' => 'large', 'content_type' => 'educational', 'query' => 'what are anxiety disorders'],
        ['url' => 'https://www.niams.nih.gov/health-topics/arthritis', 'domain' => 'niams.nih.gov', 'industry' => 'healthcare', 'site_size' => 'large', 'content_type' => 'educational', 'query' => 'what is arthritis'],

        // ─────────────────────────────────────────────────────────────
        // HEALTHCARE × AUTHORITY (10) — concept queries about credibility
        // ─────────────────────────────────────────────────────────────
        ['url' => 'https://www.mayoclinic.org/about-mayo-clinic', 'domain' => 'mayoclinic.org', 'industry' => 'healthcare', 'site_size' => 'large', 'content_type' => 'authority', 'query' => 'how should you choose a hospital for serious illness'],
        ['url' => 'https://my.clevelandclinic.org/about', 'domain' => 'clevelandclinic.org', 'industry' => 'healthcare', 'site_size' => 'large', 'content_type' => 'authority', 'query' => 'what defines a top-tier american hospital system'],
        ['url' => 'https://www.webmd.com/about-webmd-policies/about-editorial-policy', 'domain' => 'webmd.com', 'industry' => 'healthcare', 'site_size' => 'large', 'content_type' => 'authority', 'query' => 'what is the editorial standard for online health content'],
        ['url' => 'https://www.healthline.com/about', 'domain' => 'healthline.com', 'industry' => 'healthcare', 'site_size' => 'large', 'content_type' => 'authority', 'query' => 'what should you look for in a credible health and wellness website'],
        ['url' => 'https://www.medicalnewstoday.com/about', 'domain' => 'medicalnewstoday.com', 'industry' => 'healthcare', 'site_size' => 'medium', 'content_type' => 'authority', 'query' => 'what makes a medical news source reliable'],
        ['url' => 'https://www.nhs.uk/about-us/', 'domain' => 'nhs.uk', 'industry' => 'healthcare', 'site_size' => 'large', 'content_type' => 'authority', 'query' => 'how is public health information governed in the united kingdom'],
        ['url' => 'https://medlineplus.gov/about/', 'domain' => 'medlineplus.gov', 'industry' => 'healthcare', 'site_size' => 'large', 'content_type' => 'authority', 'query' => 'who publishes free reliable consumer health information online'],
        ['url' => 'https://www.cdc.gov/about/index.html', 'domain' => 'cdc.gov', 'industry' => 'healthcare', 'site_size' => 'large', 'content_type' => 'authority', 'query' => 'what is the role of national public health agencies'],
        ['url' => 'https://www.nih.gov/about-nih', 'domain' => 'nih.gov', 'industry' => 'healthcare', 'site_size' => 'large', 'content_type' => 'authority', 'query' => 'what is the structure of biomedical research funding in the united states'],
        ['url' => 'https://www.health.harvard.edu/about-us', 'domain' => 'harvard.edu', 'industry' => 'healthcare', 'site_size' => 'large', 'content_type' => 'authority', 'query' => 'what universities publish consumer health information'],

        // ─────────────────────────────────────────────────────────────
        // SAAS × EDUCATIONAL (10) — concept queries about software topics
        // ─────────────────────────────────────────────────────────────
        ['url' => 'https://www.salesforce.com/crm/what-is-crm/', 'domain' => 'salesforce.com', 'industry' => 'saas', 'site_size' => 'large', 'content_type' => 'educational', 'query' => 'what is crm software'],
        ['url' => 'https://www.hubspot.com/marketing-statistics', 'domain' => 'hubspot.com', 'industry' => 'saas', 'site_size' => 'large', 'content_type' => 'educational', 'query' => 'what are the latest marketing statistics'],
        ['url' => 'https://www.atlassian.com/agile/scrum', 'domain' => 'atlassian.com', 'industry' => 'saas', 'site_size' => 'large', 'content_type' => 'educational', 'query' => 'what is scrum methodology'],
        ['url' => 'https://www.cloudflare.com/learning/ddos/what-is-a-ddos-attack/', 'domain' => 'cloudflare.com', 'industry' => 'saas', 'site_size' => 'large', 'content_type' => 'educational', 'query' => 'what is a ddos attack'],
        ['url' => 'https://www.notion.com/help/intro-to-databases', 'domain' => 'notion.so', 'industry' => 'saas', 'site_size' => 'large', 'content_type' => 'educational', 'query' => 'what is a database in productivity software'],
        ['url' => 'https://zapier.com/blog/what-is-an-api/', 'domain' => 'zapier.com', 'industry' => 'saas', 'site_size' => 'medium', 'content_type' => 'educational', 'query' => 'what is an api'],
        ['url' => 'https://aws.amazon.com/what-is/saas/', 'domain' => 'amazon.com', 'industry' => 'saas', 'site_size' => 'large', 'content_type' => 'educational', 'query' => 'what is software as a service'],
        ['url' => 'https://www.shopify.com/blog/what-is-ecommerce', 'domain' => 'shopify.com', 'industry' => 'saas', 'site_size' => 'large', 'content_type' => 'educational', 'query' => 'what is ecommerce'],
        ['url' => 'https://mailchimp.com/marketing-glossary/email-marketing/', 'domain' => 'mailchimp.com', 'industry' => 'saas', 'site_size' => 'large', 'content_type' => 'educational', 'query' => 'what is email marketing'],
        ['url' => 'https://asana.com/resources/agile-methodology', 'domain' => 'asana.com', 'industry' => 'saas', 'site_size' => 'large', 'content_type' => 'educational', 'query' => 'what is agile project management methodology'],

        // ─────────────────────────────────────────────────────────────
        // SAAS × AUTHORITY (10) — concept queries about company categories
        // ─────────────────────────────────────────────────────────────
        ['url' => 'https://stripe.com/about', 'domain' => 'stripe.com', 'industry' => 'saas', 'site_size' => 'large', 'content_type' => 'authority', 'query' => 'what are the largest payment processing companies'],
        ['url' => 'https://www.hubspot.com/our-story', 'domain' => 'hubspot.com', 'industry' => 'saas', 'site_size' => 'large', 'content_type' => 'authority', 'query' => 'what are the leading marketing software platforms'],
        ['url' => 'https://www.atlassian.com/company', 'domain' => 'atlassian.com', 'industry' => 'saas', 'site_size' => 'large', 'content_type' => 'authority', 'query' => 'what companies make developer collaboration tools'],
        ['url' => 'https://www.cloudflare.com/about-overview/', 'domain' => 'cloudflare.com', 'industry' => 'saas', 'site_size' => 'large', 'content_type' => 'authority', 'query' => 'what companies provide internet infrastructure and ddos protection'],
        ['url' => 'https://www.notion.com/about', 'domain' => 'notion.so', 'industry' => 'saas', 'site_size' => 'large', 'content_type' => 'authority', 'query' => 'what are the most popular productivity and note-taking software companies'],
        ['url' => 'https://zapier.com/about', 'domain' => 'zapier.com', 'industry' => 'saas', 'site_size' => 'medium', 'content_type' => 'authority', 'query' => 'what are the leading workflow automation platforms'],
        ['url' => 'https://www.shopify.com/about', 'domain' => 'shopify.com', 'industry' => 'saas', 'site_size' => 'large', 'content_type' => 'authority', 'query' => 'what are the major ecommerce platform companies'],
        ['url' => 'https://asana.com/company', 'domain' => 'asana.com', 'industry' => 'saas', 'site_size' => 'large', 'content_type' => 'authority', 'query' => 'what are the leading project management software providers'],
        ['url' => 'https://mailchimp.com/about/', 'domain' => 'mailchimp.com', 'industry' => 'saas', 'site_size' => 'large', 'content_type' => 'authority', 'query' => 'what are the largest email marketing software companies'],
        ['url' => 'https://segment.com/company/', 'domain' => 'segment.com', 'industry' => 'saas', 'site_size' => 'medium', 'content_type' => 'authority', 'query' => 'what companies provide customer data platforms'],
    ],
];
