<?php

/*
|--------------------------------------------------------------------------
| Multi-Turn Conversation Validation Study (study_version=v7-conversation)
|--------------------------------------------------------------------------
|
| Validates the multi-turn infrastructure by re-running a subset of the v6
| ecommerce study with actual conversation state passed between turns.
|
| Subset: 4 brands × 4 stages × 3 platforms = 48 API calls (~7-10 min).
| Each brand's 4 stages share a conversation_id per platform, with each turn
| seeing the prior turns' user prompts and assistant responses as context.
|
| Compare per-stage results to v6's independent-stage results to see whether
| conversation state materially changes recommendation survival behaviour.
|
*/

return [
    'entries' => array_merge(
        // Hoka — running shoes (high survival in v6)
        array_map(fn ($q) => array_merge($q, [
            'url' => 'https://www.hoka.com',
            'domain' => 'hoka.com',
            'industry' => 'ecommerce',
            'site_size' => 'medium',
            'category' => 'running-shoes',
        ]), [
            ['content_type' => 'discovery', 'query' => 'what are the best running shoes for daily training'],
            ['content_type' => 'filter', 'query' => 'what are the best running shoes for flat feet and overpronation'],
            ['content_type' => 'compare', 'query' => 'compare the top running shoes for stability and cushioning'],
            ['content_type' => 'purchase', 'query' => 'i want to buy running shoes for flat feet, recommend the top 3 with links'],
        ]),

        // Allbirds — running shoes (zero survival in v6 — category-fit drift)
        array_map(fn ($q) => array_merge($q, [
            'url' => 'https://www.allbirds.com',
            'domain' => 'allbirds.com',
            'industry' => 'ecommerce',
            'site_size' => 'medium',
            'category' => 'running-shoes',
        ]), [
            ['content_type' => 'discovery', 'query' => 'what are the best running shoes for daily training'],
            ['content_type' => 'filter', 'query' => 'what are the best running shoes for flat feet and overpronation'],
            ['content_type' => 'compare', 'query' => 'compare the top running shoes for stability and cushioning'],
            ['content_type' => 'purchase', 'query' => 'i want to buy running shoes for flat feet, recommend the top 3 with links'],
        ]),

        // Saatva — mattresses (moderate survival in v6)
        array_map(fn ($q) => array_merge($q, [
            'url' => 'https://www.saatva.com',
            'domain' => 'saatva.com',
            'industry' => 'ecommerce',
            'site_size' => 'medium',
            'category' => 'memory-foam-mattresses',
        ]), [
            ['content_type' => 'discovery', 'query' => 'what are the best mattresses for sleep quality'],
            ['content_type' => 'filter', 'query' => 'what are the best memory foam mattresses for back pain'],
            ['content_type' => 'compare', 'query' => 'compare the top memory foam mattresses for side sleepers with back pain'],
            ['content_type' => 'purchase', 'query' => 'recommend the best memory foam mattress for back pain under 1500 dollars'],
        ]),

        // Casper — mattresses (zero survival in v6 — over-optimized landing)
        array_map(fn ($q) => array_merge($q, [
            'url' => 'https://www.casper.com',
            'domain' => 'casper.com',
            'industry' => 'ecommerce',
            'site_size' => 'medium',
            'category' => 'memory-foam-mattresses',
        ]), [
            ['content_type' => 'discovery', 'query' => 'what are the best mattresses for sleep quality'],
            ['content_type' => 'filter', 'query' => 'what are the best memory foam mattresses for back pain'],
            ['content_type' => 'compare', 'query' => 'compare the top memory foam mattresses for side sleepers with back pain'],
            ['content_type' => 'purchase', 'query' => 'recommend the best memory foam mattress for back pain under 1500 dollars'],
        ]),
    ),
];
