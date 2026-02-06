<?php

namespace Database\Seeders;

use App\Models\CitationAlert;
use App\Models\CitationCheck;
use App\Models\CitationQuery;
use App\Models\Scan;
use App\Models\ScheduledScan;
use App\Models\Team;
use App\Models\TokenCode;
use App\Models\TokenTransaction;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating test users...');
        $users = $this->createUsers();

        $this->command->info('Creating teams...');
        $teams = $this->createTeams($users);

        $this->command->info('Creating scans...');
        $this->createScans($users);

        $this->command->info('Creating citation queries and checks...');
        $this->createCitations($users, $teams);

        $this->command->info('Creating scheduled scans...');
        $this->createScheduledScans($users);

        $this->command->info('Creating token codes...');
        $this->createTokenCodes();

        $this->command->info('Creating token transactions...');
        $this->createTokenTransactions($users);

        $this->command->info('Test data seeding completed!');
    }

    /**
     * Create test users.
     */
    private function createUsers(): array
    {
        $users = [];

        // Create regular test users
        // Mix of users with tokens (existing users) and without (new users needing starter tokens)
        $testUsers = [
            // Users WITH tokens (already received starter tokens or purchased)
            [
                'name' => 'Jane Smith',
                'email' => 'jane@example.com',
                'token_balance' => 100,
            ],
            [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'token_balance' => 50,
            ],
            [
                'name' => 'Sarah Wilson',
                'email' => 'sarah@example.com',
                'token_balance' => 200,
            ],
            // Users WITHOUT tokens (eligible for starter tokens)
            [
                'name' => 'Mike Johnson',
                'email' => 'mike@example.com',
                'token_balance' => 0,
            ],
            [
                'name' => 'Emily Davis',
                'email' => 'emily@example.com',
                'token_balance' => 0,
            ],
            [
                'name' => 'Chris Brown',
                'email' => 'chris@example.com',
                'token_balance' => 0,
            ],
            [
                'name' => 'Alex Turner',
                'email' => 'alex@example.com',
                'token_balance' => 0,
            ],
            [
                'name' => 'Taylor Swift',
                'email' => 'taylor@example.com',
                'token_balance' => 0,
            ],
        ];

        foreach ($testUsers as $userData) {
            $users[] = User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                    'token_balance' => $userData['token_balance'],
                ]
            );
        }

        // Create a few more random users - some with tokens, some without
        $users = array_merge($users, User::factory()->count(3)->create()->all());
        // Create some users with 0 tokens (eligible for starter tokens)
        $users = array_merge($users, User::factory()->count(3)->create(['token_balance' => 0])->all());

        return $users;
    }

    /**
     * Create teams with members.
     */
    private function createTeams(array $users): array
    {
        $teams = [];

        // Create a team for the first test user
        $teams[] = Team::factory()
            ->withWhiteLabel()
            ->withDescription()
            ->create([
                'owner_id' => $users[0]->id,
                'name' => 'Acme Digital Agency',
            ]);

        // Add some members to the team
        $teams[0]->members()->attach($users[1]->id, ['role' => 'admin']);
        $teams[0]->members()->attach($users[2]->id, ['role' => 'member']);

        // Create another team
        $teams[] = Team::factory()
            ->withDescription()
            ->create([
                'owner_id' => $users[2]->id,
                'name' => 'SEO Experts Inc',
            ]);

        // Create a few random teams
        $teams = array_merge($teams, Team::factory()->count(3)->create()->all());

        return $teams;
    }

    /**
     * Create scans with various statuses.
     */
    private function createScans(array $users): void
    {
        $testUrls = [
            ['url' => 'https://example.com', 'title' => 'Example Homepage'],
            ['url' => 'https://example.com/about', 'title' => 'About Us Page'],
            ['url' => 'https://techblog.io/ai-trends', 'title' => 'AI Trends 2026'],
            ['url' => 'https://marketing-hub.com', 'title' => 'Marketing Hub'],
            ['url' => 'https://seo-tools.net/guide', 'title' => 'SEO Best Practices'],
            ['url' => 'https://content-strategy.com/tips', 'title' => 'Content Strategy Tips'],
            ['url' => 'https://analytics-pro.io', 'title' => 'Analytics Pro Dashboard'],
            ['url' => 'https://ecommerce-site.com/products', 'title' => 'Product Catalog'],
        ];

        foreach ($users as $index => $user) {
            // Completed basic scans
            Scan::factory()
                ->tier('basic')
                ->count(3)
                ->create([
                    'user_id' => $user->id,
                    'created_at' => now()->subDays(rand(1, 30)),
                ]);

            // Completed pro scans
            Scan::factory()
                ->tier('pro')
                ->count(2)
                ->create([
                    'user_id' => $user->id,
                    'created_at' => now()->subDays(rand(1, 20)),
                ]);

            // Completed full scans
            Scan::factory()
                ->tier('full')
                ->count(1)
                ->create([
                    'user_id' => $user->id,
                    'created_at' => now()->subDays(rand(1, 10)),
                ]);

            // Add specific URL scans for first few users
            if ($index < count($testUrls)) {
                Scan::factory()
                    ->tier('pro')
                    ->create([
                        'user_id' => $user->id,
                        'url' => $testUrls[$index]['url'],
                        'title' => $testUrls[$index]['title'],
                        'score' => rand(60, 95),
                        'created_at' => now()->subDays(rand(1, 7)),
                    ]);
            }
        }

        // Create some pending scans
        Scan::factory()
            ->pending()
            ->count(2)
            ->create(['user_id' => $users[0]->id]);

        // Create some processing scans
        Scan::factory()
            ->processing()
            ->count(1)
            ->create(['user_id' => $users[1]->id]);

        // Create some failed scans
        Scan::factory()
            ->failed()
            ->count(2)
            ->create(['user_id' => $users[2]->id]);
    }

    /**
     * Create citation queries, checks, and alerts.
     */
    private function createCitations(array $users, array $teams): void
    {
        $queries = [
            ['query' => 'best seo tools 2026', 'domain' => 'example.com', 'brand' => 'Example Co'],
            ['query' => 'ai content optimization', 'domain' => 'techblog.io', 'brand' => 'TechBlog'],
            ['query' => 'local business marketing tips', 'domain' => 'marketing-hub.com', 'brand' => 'Marketing Hub'],
            ['query' => 'how to improve website ranking', 'domain' => 'seo-tools.net', 'brand' => 'SEO Tools'],
            ['query' => 'content strategy guide', 'domain' => 'content-strategy.com', 'brand' => 'Content Strategy Pro'],
            ['query' => 'ecommerce seo best practices', 'domain' => 'ecommerce-site.com', 'brand' => 'E-Commerce Site'],
        ];

        $platforms = ['perplexity', 'openai', 'claude', 'gemini', 'deepseek'];

        foreach ($users as $userIndex => $user) {
            // Create citation queries for each user
            $numQueries = min($userIndex + 1, count($queries));

            for ($i = 0; $i < $numQueries; $i++) {
                $queryData = $queries[$i];

                $citationQuery = CitationQuery::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'query' => $queryData['query'],
                    ],
                    [
                        'domain' => $queryData['domain'],
                        'brand' => $queryData['brand'],
                        'frequency' => ['manual', 'daily', 'weekly'][rand(0, 2)],
                        'is_active' => true,
                        'team_id' => $userIndex < 2 && ! empty($teams) ? $teams[0]->id : null,
                    ]
                );

                // Create citation checks for each platform
                foreach ($platforms as $platform) {
                    $isCited = rand(0, 1) === 1;

                    $check = CitationCheck::factory()
                        ->create([
                            'citation_query_id' => $citationQuery->id,
                            'user_id' => $user->id,
                            'team_id' => $citationQuery->team_id,
                            'platform' => $platform,
                            'is_cited' => $isCited,
                            'citations' => $isCited ? [
                                [
                                    'url' => "https://{$queryData['domain']}/article",
                                    'snippet' => "According to {$queryData['brand']}, this is the recommended approach...",
                                ],
                            ] : [],
                            'created_at' => now()->subDays(rand(1, 14)),
                        ]);

                    // Create some alerts
                    if ($isCited && rand(0, 1) === 1) {
                        CitationAlert::create([
                            'user_id' => $user->id,
                            'team_id' => $citationQuery->team_id,
                            'citation_query_id' => $citationQuery->id,
                            'citation_check_id' => $check->id,
                            'type' => CitationAlert::TYPE_NEW_CITATION,
                            'platform' => $platform,
                            'message' => "Your domain is now being cited on {$platform} for the query: \"{$queryData['query']}\"",
                            'is_read' => rand(0, 1) === 1,
                            'read_at' => rand(0, 1) === 1 ? now()->subHours(rand(1, 48)) : null,
                        ]);
                    }
                }
            }
        }

        // Create some random citation queries with factories
        CitationQuery::factory()
            ->daily()
            ->count(5)
            ->create();

        CitationQuery::factory()
            ->weekly()
            ->count(3)
            ->create();
    }

    /**
     * Create scheduled scans.
     */
    private function createScheduledScans(array $users): void
    {
        foreach (array_slice($users, 0, 3) as $user) {
            // Daily scheduled scans
            ScheduledScan::factory()
                ->count(2)
                ->create([
                    'user_id' => $user->id,
                    'total_runs' => rand(5, 30),
                    'last_run_at' => now()->subDays(1),
                ]);

            // Weekly scheduled scans
            ScheduledScan::factory()
                ->weekly()
                ->count(1)
                ->create([
                    'user_id' => $user->id,
                    'total_runs' => rand(2, 10),
                    'last_run_at' => now()->subWeek(),
                ]);
        }

        // Create some inactive scheduled scans
        ScheduledScan::factory()
            ->inactive()
            ->count(2)
            ->create();
    }

    /**
     * Create token codes.
     */
    private function createTokenCodes(): void
    {
        // Active promo codes
        TokenCode::factory()->create([
            'code' => 'WELCOME25',
            'tokens' => 25,
            'type' => 'promo',
            'max_uses' => 1000,
            'uses_count' => 150,
        ]);

        TokenCode::factory()->create([
            'code' => 'LAUNCH50',
            'tokens' => 50,
            'type' => 'promo',
            'max_uses' => 500,
            'uses_count' => 75,
        ]);

        TokenCode::factory()->create([
            'code' => 'VIP100',
            'tokens' => 100,
            'type' => 'promo',
            'max_uses' => 100,
            'uses_count' => 20,
        ]);

        // Single-use codes
        TokenCode::factory()
            ->single()
            ->count(5)
            ->create(['tokens' => 200]);

        // Expired codes
        TokenCode::factory()
            ->expired()
            ->count(2)
            ->create();

        // Maxed out codes
        TokenCode::factory()
            ->maxedOut()
            ->count(1)
            ->create();
    }

    /**
     * Create token transactions.
     */
    private function createTokenTransactions(array $users): void
    {
        // Valid types: purchase, spend, refund, bonus
        $transactionTypes = [
            ['type' => TokenTransaction::TYPE_PURCHASE, 'description' => 'Purchased token package', 'amount' => 50],
            ['type' => TokenTransaction::TYPE_BONUS, 'description' => 'Welcome bonus', 'amount' => 25],
            ['type' => TokenTransaction::TYPE_SPEND, 'description' => 'Pro scan', 'amount' => -5],
            ['type' => TokenTransaction::TYPE_SPEND, 'description' => 'Full scan', 'amount' => -10],
            ['type' => TokenTransaction::TYPE_SPEND, 'description' => 'Citation check - Perplexity', 'amount' => -3],
            ['type' => TokenTransaction::TYPE_REFUND, 'description' => 'Scan refund - failed scan', 'amount' => 5],
        ];

        foreach ($users as $user) {
            $balance = $user->token_balance ?? 0;

            // Welcome bonus transaction
            TokenTransaction::create([
                'user_id' => $user->id,
                'type' => TokenTransaction::TYPE_BONUS,
                'amount' => 25,
                'balance_after' => $balance,
                'description' => 'Welcome bonus',
                'created_at' => now()->subDays(rand(10, 30)),
            ]);

            // Create random transactions
            for ($i = 0; $i < rand(3, 8); $i++) {
                $type = $transactionTypes[array_rand($transactionTypes)];
                $amount = match ($type['type']) {
                    TokenTransaction::TYPE_PURCHASE => rand(1, 5) * 50,
                    TokenTransaction::TYPE_BONUS => rand(10, 50),
                    TokenTransaction::TYPE_SPEND => -rand(3, 10),
                    TokenTransaction::TYPE_REFUND => rand(5, 10),
                    default => 0,
                };

                // Adjust balance based on transaction
                $balance += $amount;
                if ($balance < 0) {
                    $balance = 0;
                }

                TokenTransaction::create([
                    'user_id' => $user->id,
                    'type' => $type['type'],
                    'amount' => $amount,
                    'balance_after' => $balance,
                    'description' => $type['description'],
                    'created_at' => now()->subDays(rand(1, 20)),
                ]);
            }
        }
    }
}
