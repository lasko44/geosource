<?php

/*
|--------------------------------------------------------------------------
| Category Fit Taxonomy
|--------------------------------------------------------------------------
|
| Maps ecommerce / DTC product categories to seed terms that strongly
| signal a page is competing in that category. Used by CategoryFitService
| to classify a scanned page and surface category-fit info in scan results.
|
| Term weight conventions:
|   - core terms: 3.0 (strongly category-specific)
|   - supporting terms: 1.5
|   - generic terms: 0.5 (only useful in combination)
|
| Keep terms lowercase. Matching is case-insensitive against page text.
|
*/

return [
    'running-shoes' => [
        'label' => 'Running Shoes',
        'core' => ['running shoe', 'running shoes', 'trail running', 'marathon shoe', 'road running'],
        'supporting' => ['cushioning', 'midsole', 'pronation', 'arch support', 'forefoot strike'],
        'generic' => ['running', 'shoe', 'shoes', 'footwear', 'training'],
    ],
    'memory-foam-mattresses' => [
        'label' => 'Memory Foam Mattresses',
        'core' => ['memory foam mattress', 'memory foam', 'mattress in a box', 'cooling foam'],
        'supporting' => ['mattress', 'pillow top', 'firmness', 'box spring', 'queen mattress', 'king mattress'],
        'generic' => ['sleep', 'bed', 'foam'],
    ],
    'eyewear' => [
        'label' => 'Online Eyewear',
        'core' => ['prescription glasses online', 'home try-on', 'virtual try-on', 'eyewear online'],
        'supporting' => ['eyeglasses', 'sunglasses', 'frames', 'lenses', 'optometrist'],
        'generic' => ['glasses', 'frames', 'eyewear'],
    ],
    'skincare' => [
        'label' => 'Skincare',
        'core' => ['niacinamide', 'hyaluronic acid', 'salicylic acid', 'retinol serum', 'vitamin c serum'],
        'supporting' => ['skincare routine', 'cleanser', 'moisturizer', 'serum', 'sensitive skin'],
        'generic' => ['skincare', 'beauty', 'cosmetics'],
    ],
    'sustainable-apparel' => [
        'label' => 'Sustainable Apparel',
        'core' => ['organic cotton', 'fair trade clothing', 'sustainable fashion', 'recycled fabric', 'ethical clothing'],
        'supporting' => ['capsule wardrobe', 'merino wool', 'workwear', 'everyday wear'],
        'generic' => ['clothing', 'apparel', 'fashion'],
    ],
    'coffee-subscription' => [
        'label' => 'Coffee Subscription',
        'core' => ['coffee subscription', 'single origin coffee', 'specialty coffee', 'roaster of the month'],
        'supporting' => ['whole bean', 'light roast', 'medium roast', 'pour over', 'espresso roast'],
        'generic' => ['coffee', 'beans', 'roast'],
    ],
    'meal-kits' => [
        'label' => 'Meal Kit Delivery',
        'core' => ['meal kit', 'meal delivery', 'ready to cook', 'pre-portioned ingredients'],
        'supporting' => ['weekly box', 'family meals', 'dietitian approved', 'recipe card'],
        'generic' => ['meals', 'recipes', 'delivery'],
    ],
    'pet-supplies' => [
        'label' => 'Pet Supplies',
        'core' => ['pet food', 'dog food', 'cat food', 'pet supply', 'kibble', 'auto-ship pet'],
        'supporting' => ['dog toys', 'cat litter', 'pet pharmacy', 'pet treats'],
        'generic' => ['pet', 'pets', 'dog', 'cat'],
    ],
    'dtc-furniture' => [
        'label' => 'DTC Furniture',
        'core' => ['modular sofa', 'mid-century modern sofa', 'flat pack furniture', 'sustainable furniture'],
        'supporting' => ['sectional', 'sofa', 'sleeper sofa', 'apartment furniture'],
        'generic' => ['furniture', 'home decor', 'living room'],
    ],
    'wellness-supplements' => [
        'label' => 'Wellness Supplements',
        'core' => ['daily multivitamin', 'vitamin subscription', 'greens powder', 'adaptogen blend'],
        'supporting' => ['third-party tested', 'gummy vitamin', 'prenatal vitamin', 'energy supplement'],
        'generic' => ['vitamins', 'supplements', 'wellness', 'nutrition'],
    ],
    'razors' => [
        'label' => 'Razors / Shaving',
        'core' => ['razor subscription', 'shave club', 'cartridge razor', 'safety razor', 'razor blades'],
        'supporting' => ['shaving cream', 'pre-shave oil', 'after-shave', 'beard care'],
        'generic' => ['shave', 'shaving', 'razor', 'razors'],
    ],
    'bedsheets' => [
        'label' => 'Bedsheets / Bedding',
        'core' => ['percale sheets', 'sateen sheets', 'long-staple cotton', 'bamboo sheets', 'linen sheets'],
        'supporting' => ['thread count', 'oeko-tex', 'duvet cover', 'pillowcase set'],
        'generic' => ['sheets', 'bedding', 'linens'],
    ],
];
