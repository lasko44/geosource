<?php

namespace Database\Seeders;

use App\Models\TokenPackage;
use Illuminate\Database\Seeder;

class TokenPackageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $packages = [
            [
                'name' => '50 Tokens',
                'tokens' => 50,
                'price_cents' => 500, // $5
                'stripe_price_id' => null, // Set after creating in Stripe
                'savings_percent' => 0,
                'is_popular' => false,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => '200 Tokens',
                'tokens' => 200,
                'price_cents' => 1500, // $15
                'stripe_price_id' => null, // Set after creating in Stripe
                'savings_percent' => 25,
                'is_popular' => true,
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => '500 Tokens',
                'tokens' => 500,
                'price_cents' => 3000, // $30
                'stripe_price_id' => null, // Set after creating in Stripe
                'savings_percent' => 40,
                'is_popular' => false,
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name' => '1000 Tokens',
                'tokens' => 1000,
                'price_cents' => 5000, // $50
                'stripe_price_id' => null, // Set after creating in Stripe
                'savings_percent' => 50,
                'is_popular' => false,
                'is_active' => true,
                'sort_order' => 4,
            ],
        ];

        foreach ($packages as $package) {
            TokenPackage::updateOrCreate(
                ['tokens' => $package['tokens']],
                $package
            );
        }

        $this->command->info('Token packages seeded successfully!');
        $this->command->warn('Remember to create Stripe products and update stripe_price_id for each package.');
    }
}
