<?php

/*
|--------------------------------------------------------------------------
| Ecommerce Brand Display-Name Map
|--------------------------------------------------------------------------
|
| Maps each brand domain (used for scanning) to one or more brand display
| names (used for citation detection in AI responses). Some brands have
| short or generic names that need disambiguation to avoid false-positive
| word matches.
|
| Used by the v6-ecommerce study post-processor to re-evaluate is_cited
| via brand-name mentions in addition to domain-string matches.
|
*/

return [
    // running-shoes
    'allbirds.com' => ['Allbirds'],
    'hoka.com' => ['Hoka'],
    'on.com' => ['On Running', 'On Cloud', 'On Cloudmonster', 'On Cloudflyer', 'On Cloudswift'],
    'saucony.com' => ['Saucony'],

    // memory-foam-mattresses
    'casper.com' => ['Casper'],
    'purple.com' => ['Purple Mattress', 'Purple Hybrid'],
    'saatva.com' => ['Saatva'],
    'helixsleep.com' => ['Helix Sleep', 'Helix Midnight', 'Helix Mattress'],
    'nectarsleep.com' => ['Nectar Sleep', 'Nectar Mattress'],

    // eyewear
    'liingoeyewear.com' => ['Liingo Eyewear', 'Liingo'],

    // minimalist-skincare
    'glossier.com' => ['Glossier'],
    'theordinary.com' => ['The Ordinary'],
    'cerave.com' => ['CeraVe'],
    'cetaphil.com' => ['Cetaphil'],

    // sustainable-apparel
    'patagonia.com' => ['Patagonia'],
    'everlane.com' => ['Everlane'],
    'thereformation.com' => ['Reformation'],
    'cuyana.com' => ['Cuyana'],

    // coffee-subscription
    'drinktrade.com' => ['Trade Coffee'],
    'atlascoffeeclub.com' => ['Atlas Coffee Club'],
    'bluebottlecoffee.com' => ['Blue Bottle Coffee', 'Blue Bottle'],

    // meal-kits
    'hellofresh.com' => ['HelloFresh', 'Hello Fresh'],
    'blueapron.com' => ['Blue Apron'],
    'homechef.com' => ['Home Chef'],
    'sunbasket.com' => ['Sunbasket', 'Sun Basket'],

    // pet-supplies
    'chewy.com' => ['Chewy.com', 'Chewy'],
    'bark.co' => ['BarkBox', 'Bark.co'],

    // dtc-furniture (Article needs disambiguation — "Article" alone matches articles in prose)
    'article.com' => ['Article.com', 'Article furniture', 'Article (the furniture'],
    'burrow.com' => ['Burrow'],
    'floydhome.com' => ['Floyd Home', 'Floyd Detroit'],
    'joybird.com' => ['Joybird'],

    // wellness-supplements
    'athleticgreens.com' => ['Athletic Greens', 'AG1'],
    'ritual.com' => ['Ritual Essential', 'Ritual vitamins', 'Ritual multivitamin'],
    'humnutrition.com' => ['HUM Nutrition', 'HUM'],

    // razors
    'harrys.com' => ["Harry's"],
    'dollarshaveclub.com' => ['Dollar Shave Club'],
    'gillette.com' => ['Gillette'],

    // bedsheets
    'brooklinen.com' => ['Brooklinen'],
    'parachutehome.com' => ['Parachute Home', 'Parachute Sheets'],
    'bollandbranch.com' => ['Boll & Branch', 'Boll and Branch'],
];
