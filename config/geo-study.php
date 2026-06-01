<?php

/*
|--------------------------------------------------------------------------
| GEO Effectiveness Study Configuration
|--------------------------------------------------------------------------
|
| URLs to study across industries, mixing large brands, mid-size companies,
| and smaller sites. Each entry includes a natural-language query that a
| user might ask an AI assistant about this domain's space.
|
*/

return [
    'entries' => [
        // ── SaaS / Software ─────────────────────────────────────────
        ['url' => 'https://www.notion.so', 'domain' => 'notion.so', 'industry' => 'saas', 'site_size' => 'large', 'query' => 'what is the best note-taking app for teams'],
        ['url' => 'https://www.airtable.com', 'domain' => 'airtable.com', 'industry' => 'saas', 'site_size' => 'large', 'query' => 'what is the best database tool for non-developers'],
        ['url' => 'https://linear.app', 'domain' => 'linear.app', 'industry' => 'saas', 'site_size' => 'medium', 'query' => 'what is the best project management tool for engineering teams'],
        ['url' => 'https://www.clickup.com', 'domain' => 'clickup.com', 'industry' => 'saas', 'site_size' => 'large', 'query' => 'what is the best project management software'],
        ['url' => 'https://www.loom.com', 'domain' => 'loom.com', 'industry' => 'saas', 'site_size' => 'medium', 'query' => 'what is the best screen recording tool for work'],
        ['url' => 'https://cal.com', 'domain' => 'cal.com', 'industry' => 'saas', 'site_size' => 'small', 'query' => 'what is the best open source scheduling tool'],
        ['url' => 'https://www.postman.com', 'domain' => 'postman.com', 'industry' => 'saas', 'site_size' => 'large', 'query' => 'what is the best API testing tool'],
        ['url' => 'https://www.figma.com', 'domain' => 'figma.com', 'industry' => 'saas', 'site_size' => 'large', 'query' => 'what is the best design tool for teams'],

        // ── Ecommerce ───────────────────────────────────────────────
        ['url' => 'https://www.allbirds.com', 'domain' => 'allbirds.com', 'industry' => 'ecommerce', 'site_size' => 'medium', 'query' => 'what are the best sustainable shoes'],
        ['url' => 'https://www.warbyparker.com', 'domain' => 'warbyparker.com', 'industry' => 'ecommerce', 'site_size' => 'medium', 'query' => 'what is the best place to buy glasses online'],
        ['url' => 'https://www.casper.com', 'domain' => 'casper.com', 'industry' => 'ecommerce', 'site_size' => 'medium', 'query' => 'what is the best mattress for back pain'],
        ['url' => 'https://www.glossier.com', 'domain' => 'glossier.com', 'industry' => 'ecommerce', 'site_size' => 'medium', 'query' => 'what are the best skincare brands for minimalists'],
        ['url' => 'https://www.chewy.com', 'domain' => 'chewy.com', 'industry' => 'ecommerce', 'site_size' => 'large', 'query' => 'what is the best online pet store'],

        // ── Healthcare ──────────────────────────────────────────────
        ['url' => 'https://www.mayoclinic.org', 'domain' => 'mayoclinic.org', 'industry' => 'healthcare', 'site_size' => 'large', 'query' => 'what are the symptoms of type 2 diabetes'],
        ['url' => 'https://www.webmd.com', 'domain' => 'webmd.com', 'industry' => 'healthcare', 'site_size' => 'large', 'query' => 'how to treat a migraine at home'],
        ['url' => 'https://www.healthline.com', 'domain' => 'healthline.com', 'industry' => 'healthcare', 'site_size' => 'large', 'query' => 'what is the best diet for heart health'],
        ['url' => 'https://www.zocdoc.com', 'domain' => 'zocdoc.com', 'industry' => 'healthcare', 'site_size' => 'medium', 'query' => 'how to find a doctor near me'],
        ['url' => 'https://www.onemedical.com', 'domain' => 'onemedical.com', 'industry' => 'healthcare', 'site_size' => 'medium', 'query' => 'what is the best primary care membership service'],

        // ── Finance ─────────────────────────────────────────────────
        ['url' => 'https://www.nerdwallet.com', 'domain' => 'nerdwallet.com', 'industry' => 'finance', 'site_size' => 'large', 'query' => 'what is the best credit card for travel rewards'],
        ['url' => 'https://www.investopedia.com', 'domain' => 'investopedia.com', 'industry' => 'finance', 'site_size' => 'large', 'query' => 'how to start investing in stocks'],
        ['url' => 'https://www.bankrate.com', 'domain' => 'bankrate.com', 'industry' => 'finance', 'site_size' => 'large', 'query' => 'what is the best savings account interest rate'],
        ['url' => 'https://www.mercury.com', 'domain' => 'mercury.com', 'industry' => 'finance', 'site_size' => 'medium', 'query' => 'what is the best business bank account for startups'],
        ['url' => 'https://www.brex.com', 'domain' => 'brex.com', 'industry' => 'finance', 'site_size' => 'medium', 'query' => 'what is the best corporate credit card'],

        // ── Legal ───────────────────────────────────────────────────
        ['url' => 'https://www.legalzoom.com', 'domain' => 'legalzoom.com', 'industry' => 'legal', 'site_size' => 'large', 'query' => 'how to form an LLC online'],
        ['url' => 'https://www.nolo.com', 'domain' => 'nolo.com', 'industry' => 'legal', 'site_size' => 'medium', 'query' => 'what is the difference between an LLC and a corporation'],
        ['url' => 'https://www.avvo.com', 'domain' => 'avvo.com', 'industry' => 'legal', 'site_size' => 'medium', 'query' => 'how to find a good lawyer'],

        // ── Education ───────────────────────────────────────────────
        ['url' => 'https://www.khanacademy.org', 'domain' => 'khanacademy.org', 'industry' => 'education', 'site_size' => 'large', 'query' => 'what is the best free online learning platform'],
        ['url' => 'https://www.coursera.org', 'domain' => 'coursera.org', 'industry' => 'education', 'site_size' => 'large', 'query' => 'what are the best online courses for data science'],
        ['url' => 'https://www.duolingo.com', 'domain' => 'duolingo.com', 'industry' => 'education', 'site_size' => 'large', 'query' => 'what is the best app to learn a new language'],
        ['url' => 'https://www.brilliant.org', 'domain' => 'brilliant.org', 'industry' => 'education', 'site_size' => 'medium', 'query' => 'what is the best app to learn math and science'],

        // ── Travel ──────────────────────────────────────────────────
        ['url' => 'https://www.tripadvisor.com', 'domain' => 'tripadvisor.com', 'industry' => 'travel', 'site_size' => 'large', 'query' => 'what is the best hotel in Paris'],
        ['url' => 'https://www.kayak.com', 'domain' => 'kayak.com', 'industry' => 'travel', 'site_size' => 'large', 'query' => 'what is the best flight search engine'],
        ['url' => 'https://www.hostelworld.com', 'domain' => 'hostelworld.com', 'industry' => 'travel', 'site_size' => 'medium', 'query' => 'what is the best hostel booking site'],
        ['url' => 'https://www.airbnb.com', 'domain' => 'airbnb.com', 'industry' => 'travel', 'site_size' => 'large', 'query' => 'what is the best vacation rental platform'],

        // ── Real Estate ─────────────────────────────────────────────
        ['url' => 'https://www.zillow.com', 'domain' => 'zillow.com', 'industry' => 'real-estate', 'site_size' => 'large', 'query' => 'what is the best website to search for homes'],
        ['url' => 'https://www.redfin.com', 'domain' => 'redfin.com', 'industry' => 'real-estate', 'site_size' => 'large', 'query' => 'what is the best real estate website with accurate estimates'],
        ['url' => 'https://www.apartments.com', 'domain' => 'apartments.com', 'industry' => 'real-estate', 'site_size' => 'large', 'query' => 'what is the best apartment search website'],

        // ── Fitness ─────────────────────────────────────────────────
        ['url' => 'https://www.peloton.com', 'domain' => 'peloton.com', 'industry' => 'fitness', 'site_size' => 'large', 'query' => 'what is the best at-home exercise bike'],
        ['url' => 'https://www.myfitnesspal.com', 'domain' => 'myfitnesspal.com', 'industry' => 'fitness', 'site_size' => 'large', 'query' => 'what is the best calorie tracking app'],
        ['url' => 'https://www.whoop.com', 'domain' => 'whoop.com', 'industry' => 'fitness', 'site_size' => 'medium', 'query' => 'what is the best fitness tracker for recovery'],

        // ── Cybersecurity ───────────────────────────────────────────
        ['url' => 'https://www.crowdstrike.com', 'domain' => 'crowdstrike.com', 'industry' => 'cybersecurity', 'site_size' => 'large', 'query' => 'what is the best endpoint protection platform'],
        ['url' => 'https://www.1password.com', 'domain' => '1password.com', 'industry' => 'cybersecurity', 'site_size' => 'medium', 'query' => 'what is the best password manager'],
        ['url' => 'https://www.cloudflare.com', 'domain' => 'cloudflare.com', 'industry' => 'cybersecurity', 'site_size' => 'large', 'query' => 'what is the best DDoS protection service'],

        // ── Marketing / Agencies ────────────────────────────────────
        ['url' => 'https://www.hubspot.com', 'domain' => 'hubspot.com', 'industry' => 'agencies', 'site_size' => 'large', 'query' => 'what is the best CRM for small businesses'],
        ['url' => 'https://www.mailchimp.com', 'domain' => 'mailchimp.com', 'industry' => 'agencies', 'site_size' => 'large', 'query' => 'what is the best email marketing platform'],
        ['url' => 'https://www.buffer.com', 'domain' => 'buffer.com', 'industry' => 'agencies', 'site_size' => 'medium', 'query' => 'what is the best social media scheduling tool'],
        ['url' => 'https://www.canva.com', 'domain' => 'canva.com', 'industry' => 'agencies', 'site_size' => 'large', 'query' => 'what is the best online graphic design tool'],

        // ── Food & Beverage ─────────────────────────────────────────
        ['url' => 'https://www.doordash.com', 'domain' => 'doordash.com', 'industry' => 'food-beverage', 'site_size' => 'large', 'query' => 'what is the best food delivery app'],
        ['url' => 'https://www.hellofresh.com', 'domain' => 'hellofresh.com', 'industry' => 'food-beverage', 'site_size' => 'large', 'query' => 'what is the best meal kit delivery service'],
        ['url' => 'https://www.instacart.com', 'domain' => 'instacart.com', 'industry' => 'food-beverage', 'site_size' => 'large', 'query' => 'what is the best grocery delivery service'],

        // ── B2B ─────────────────────────────────────────────────────
        ['url' => 'https://www.salesforce.com', 'domain' => 'salesforce.com', 'industry' => 'b2b', 'site_size' => 'large', 'query' => 'what is the best enterprise CRM software'],
        ['url' => 'https://www.stripe.com', 'domain' => 'stripe.com', 'industry' => 'b2b', 'site_size' => 'large', 'query' => 'what is the best payment processing platform for developers'],
        ['url' => 'https://www.twilio.com', 'domain' => 'twilio.com', 'industry' => 'b2b', 'site_size' => 'large', 'query' => 'what is the best API for sending SMS messages'],
        ['url' => 'https://www.datadog.com', 'domain' => 'datadog.com', 'industry' => 'b2b', 'site_size' => 'large', 'query' => 'what is the best cloud monitoring platform'],

        // ── News / Media ────────────────────────────────────────────
        ['url' => 'https://www.theverge.com', 'domain' => 'theverge.com', 'industry' => 'news-media', 'site_size' => 'large', 'query' => 'what is the best tech news website'],
        ['url' => 'https://techcrunch.com', 'domain' => 'techcrunch.com', 'industry' => 'news-media', 'site_size' => 'large', 'query' => 'what is the best website for startup news'],
        ['url' => 'https://www.wired.com', 'domain' => 'wired.com', 'industry' => 'news-media', 'site_size' => 'large', 'query' => 'what is the best technology magazine'],

        // ── Automotive ──────────────────────────────────────────────
        ['url' => 'https://www.carvana.com', 'domain' => 'carvana.com', 'industry' => 'automotive', 'site_size' => 'large', 'query' => 'what is the best website to buy a used car online'],
        ['url' => 'https://www.edmunds.com', 'domain' => 'edmunds.com', 'industry' => 'automotive', 'site_size' => 'large', 'query' => 'what is the best car research website'],
        ['url' => 'https://www.kbb.com', 'domain' => 'kbb.com', 'industry' => 'automotive', 'site_size' => 'large', 'query' => 'how to find the value of my car'],

        // ── Gaming ──────────────────────────────────────────────────
        ['url' => 'https://www.ign.com', 'domain' => 'ign.com', 'industry' => 'gaming', 'site_size' => 'large', 'query' => 'what is the best gaming news website'],
        ['url' => 'https://www.pcgamer.com', 'domain' => 'pcgamer.com', 'industry' => 'gaming', 'site_size' => 'medium', 'query' => 'what is the best gaming PC build for 2026'],
        ['url' => 'https://store.steampowered.com', 'domain' => 'steampowered.com', 'industry' => 'gaming', 'site_size' => 'large', 'query' => 'what is the best PC game store'],

        // ── Recruiting ──────────────────────────────────────────────
        ['url' => 'https://www.lever.co', 'domain' => 'lever.co', 'industry' => 'recruitment', 'site_size' => 'medium', 'query' => 'what is the best applicant tracking system'],
        ['url' => 'https://www.greenhouse.com', 'domain' => 'greenhouse.com', 'industry' => 'recruitment', 'site_size' => 'medium', 'query' => 'what is the best recruiting software for growing companies'],

        // ── Small / Niche Sites (control group — likely lower GEO) ─
        ['url' => 'https://www.seriouseats.com', 'domain' => 'seriouseats.com', 'industry' => 'food-beverage', 'site_size' => 'medium', 'query' => 'what is the best recipe website for home cooks'],
        ['url' => 'https://www.wirecutter.com', 'domain' => 'wirecutter.com', 'industry' => 'ecommerce', 'site_size' => 'large', 'query' => 'what is the best product review website'],
        ['url' => 'https://www.zapier.com', 'domain' => 'zapier.com', 'industry' => 'saas', 'site_size' => 'large', 'query' => 'what is the best workflow automation tool'],
        ['url' => 'https://www.vercel.com', 'domain' => 'vercel.com', 'industry' => 'saas', 'site_size' => 'medium', 'query' => 'what is the best hosting platform for Next.js'],
    ],
];
