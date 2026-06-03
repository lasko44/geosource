<?php

/*
|--------------------------------------------------------------------------
| Ecommerce Recommendation Survival Study (study_version=v6-ecommerce)
|--------------------------------------------------------------------------
|
| Tests how often DTC and ecommerce brands survive a multi-stage shopping
| journey across ChatGPT, Perplexity, and Claude. Each brand is checked at
| four stages: discovery → filter → comparison → purchase intent.
|
| The "recommendation survival rate" per brand = fraction of stages where
| the brand was cited by at least one platform. We then correlate this
| against the 12 GeoSource pillar scores to determine which content
| signals best predict commercial outcomes — not just citations.
|
| 12 categories × 3-5 brands × 4 stages ≈ 172 entries × 3 platforms ≈ 516 checks.
|
| content_type field encodes the stage: 'discovery', 'filter', 'compare',
| or 'purchase'. category field encodes the product vertical.
|
*/

$categories = [
    // ─────────────────────────────────────────────────────────────
    'running-shoes' => [
        'brands' => [
            ['url' => 'https://www.allbirds.com', 'domain' => 'allbirds.com'],
            ['url' => 'https://www.brooksrunning.com', 'domain' => 'brooksrunning.com'],
            ['url' => 'https://www.hoka.com', 'domain' => 'hoka.com'],
            ['url' => 'https://www.on.com', 'domain' => 'on.com'],
            ['url' => 'https://www.saucony.com', 'domain' => 'saucony.com'],
        ],
        'queries' => [
            'discovery' => 'what are the best running shoes for daily training',
            'filter' => 'what are the best running shoes for flat feet and overpronation',
            'compare' => 'compare the top running shoes for stability and cushioning',
            'purchase' => 'i want to buy running shoes for flat feet, recommend the top 3 with links',
        ],
    ],
    'memory-foam-mattresses' => [
        'brands' => [
            ['url' => 'https://www.casper.com', 'domain' => 'casper.com'],
            ['url' => 'https://purple.com', 'domain' => 'purple.com'],
            ['url' => 'https://www.saatva.com', 'domain' => 'saatva.com'],
            ['url' => 'https://helixsleep.com', 'domain' => 'helixsleep.com'],
            ['url' => 'https://www.nectarsleep.com', 'domain' => 'nectarsleep.com'],
        ],
        'queries' => [
            'discovery' => 'what are the best mattresses for sleep quality',
            'filter' => 'what are the best memory foam mattresses for back pain',
            'compare' => 'compare the top memory foam mattresses for side sleepers with back pain',
            'purchase' => 'recommend the best memory foam mattress for back pain under 1500 dollars',
        ],
    ],
    'eyewear' => [
        'brands' => [
            ['url' => 'https://www.warbyparker.com', 'domain' => 'warbyparker.com'],
            ['url' => 'https://www.liingoeyewear.com', 'domain' => 'liingoeyewear.com'],
            ['url' => 'https://www.eyebuydirect.com', 'domain' => 'eyebuydirect.com'],
        ],
        'queries' => [
            'discovery' => 'where can i buy prescription glasses online',
            'filter' => 'what are the best online glasses retailers for budget shoppers',
            'compare' => 'compare top online eyewear retailers for affordability and home try-on',
            'purchase' => 'recommend the best place to buy prescription glasses online with a home try-on program',
        ],
    ],
    'minimalist-skincare' => [
        'brands' => [
            ['url' => 'https://www.glossier.com', 'domain' => 'glossier.com'],
            ['url' => 'https://theordinary.com', 'domain' => 'theordinary.com'],
            ['url' => 'https://www.cerave.com', 'domain' => 'cerave.com'],
            ['url' => 'https://www.cetaphil.com', 'domain' => 'cetaphil.com'],
        ],
        'queries' => [
            'discovery' => 'what are the best skincare brands for sensitive skin',
            'filter' => 'what are the best minimalist skincare brands for beginners',
            'compare' => 'compare affordable skincare brands for a daily routine',
            'purchase' => 'recommend a starter skincare routine with affordable products for sensitive skin',
        ],
    ],
    'sustainable-apparel' => [
        'brands' => [
            ['url' => 'https://www.patagonia.com', 'domain' => 'patagonia.com'],
            ['url' => 'https://www.everlane.com', 'domain' => 'everlane.com'],
            ['url' => 'https://www.thereformation.com', 'domain' => 'thereformation.com'],
            ['url' => 'https://cuyana.com', 'domain' => 'cuyana.com'],
        ],
        'queries' => [
            'discovery' => 'what are the best sustainable clothing brands',
            'filter' => 'what are the best sustainable womens clothing brands for workwear',
            'compare' => 'compare ethical fashion brands for everyday wear and capsule wardrobes',
            'purchase' => 'recommend sustainable clothing brands for building a capsule wardrobe',
        ],
    ],
    'coffee-subscription' => [
        'brands' => [
            ['url' => 'https://www.drinktrade.com', 'domain' => 'drinktrade.com'],
            ['url' => 'https://www.atlascoffeeclub.com', 'domain' => 'atlascoffeeclub.com'],
            ['url' => 'https://bluebottlecoffee.com', 'domain' => 'bluebottlecoffee.com'],
        ],
        'queries' => [
            'discovery' => 'what are the best coffee subscriptions for home brewing',
            'filter' => 'what are the best coffee subscriptions for single origin specialty beans',
            'compare' => 'compare top coffee subscription services for variety and quality',
            'purchase' => 'recommend a coffee subscription for someone who likes light roast single origin coffee',
        ],
    ],
    'meal-kits' => [
        'brands' => [
            ['url' => 'https://www.hellofresh.com', 'domain' => 'hellofresh.com'],
            ['url' => 'https://www.blueapron.com', 'domain' => 'blueapron.com'],
            ['url' => 'https://www.homechef.com', 'domain' => 'homechef.com'],
            ['url' => 'https://sunbasket.com', 'domain' => 'sunbasket.com'],
        ],
        'queries' => [
            'discovery' => 'what are the best meal kit delivery services',
            'filter' => 'what are the best meal kits for families with picky kids',
            'compare' => 'compare meal kit services for price variety and family-friendly meals',
            'purchase' => 'recommend a meal kit subscription for a family of four with picky kids',
        ],
    ],
    'pet-supplies' => [
        'brands' => [
            ['url' => 'https://www.chewy.com', 'domain' => 'chewy.com'],
            ['url' => 'https://www.petco.com', 'domain' => 'petco.com'],
            ['url' => 'https://www.bark.co', 'domain' => 'bark.co'],
        ],
        'queries' => [
            'discovery' => 'where to buy pet supplies online',
            'filter' => 'what are the best online pet stores with subscription delivery',
            'compare' => 'compare top pet supply retailers for dogs by price and variety',
            'purchase' => 'recommend the best place to buy dog food and toys online on a monthly subscription',
        ],
    ],
    'dtc-furniture' => [
        'brands' => [
            ['url' => 'https://www.article.com', 'domain' => 'article.com'],
            ['url' => 'https://burrow.com', 'domain' => 'burrow.com'],
            ['url' => 'https://floydhome.com', 'domain' => 'floydhome.com'],
            ['url' => 'https://joybird.com', 'domain' => 'joybird.com'],
        ],
        'queries' => [
            'discovery' => 'what are the best direct-to-consumer furniture brands',
            'filter' => 'what are the best modern sofa brands for small apartments',
            'compare' => 'compare direct-to-consumer sofa brands for value and quality',
            'purchase' => 'recommend a modular sofa from a direct-to-consumer brand for a small apartment',
        ],
    ],
    'wellness-supplements' => [
        'brands' => [
            ['url' => 'https://athleticgreens.com', 'domain' => 'athleticgreens.com'],
            ['url' => 'https://ritual.com', 'domain' => 'ritual.com'],
            ['url' => 'https://www.humnutrition.com', 'domain' => 'humnutrition.com'],
        ],
        'queries' => [
            'discovery' => 'what are the best daily vitamins and supplements',
            'filter' => 'what are the best multivitamin subscriptions for adults',
            'compare' => 'compare premium multivitamin brands for daily use',
            'purchase' => 'recommend a daily multivitamin subscription for an adult',
        ],
    ],
    'razors' => [
        'brands' => [
            ['url' => 'https://www.harrys.com', 'domain' => 'harrys.com'],
            ['url' => 'https://www.dollarshaveclub.com', 'domain' => 'dollarshaveclub.com'],
            ['url' => 'https://gillette.com', 'domain' => 'gillette.com'],
        ],
        'queries' => [
            'discovery' => 'what are the best razor subscription services for men',
            'filter' => 'what are the best razor subscriptions for sensitive skin',
            'compare' => 'compare razor subscription services for value and shave quality',
            'purchase' => 'recommend the best razor subscription for sensitive skin',
        ],
    ],
    'bedsheets' => [
        'brands' => [
            ['url' => 'https://www.brooklinen.com', 'domain' => 'brooklinen.com'],
            ['url' => 'https://www.parachutehome.com', 'domain' => 'parachutehome.com'],
            ['url' => 'https://www.bollandbranch.com', 'domain' => 'bollandbranch.com'],
        ],
        'queries' => [
            'discovery' => 'what are the best luxury bedsheet brands',
            'filter' => 'what are the best percale and sateen bedsheets for hot sleepers',
            'compare' => 'compare premium bedsheet brands for softness and durability',
            'purchase' => 'recommend luxury bedsheets for hot sleepers',
        ],
    ],
];

// Flatten into the entries shape RunGeoStudy expects.
$entries = [];
foreach ($categories as $category => $cat) {
    foreach ($cat['brands'] as $brand) {
        foreach ($cat['queries'] as $stage => $query) {
            $entries[] = [
                'url' => $brand['url'],
                'domain' => $brand['domain'],
                'industry' => 'ecommerce',
                'site_size' => 'medium',
                'content_type' => $stage,
                'category' => $category,
                'query' => $query,
            ];
        }
    }
}

return ['entries' => $entries];
