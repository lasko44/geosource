<?php

namespace App\Console\Commands;

use App\Models\TokenTransaction;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CreditExistingUsersTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tokens:credit-existing-users
                            {amount=40 : Number of tokens to credit}
                            {--dry-run : Show what would happen without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Credit existing users with tokens (one-time migration)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $amount = (int) $this->argument('amount');
        $dryRun = $this->option('dry-run');

        if ($amount <= 0) {
            $this->error('Amount must be a positive number.');

            return 1;
        }

        // Find users who haven't received this migration bonus yet
        // We track this by checking for a specific metadata in their transactions
        $migrationKey = 'existing_user_migration_bonus_2026';

        $usersWithBonus = TokenTransaction::where('type', TokenTransaction::TYPE_BONUS)
            ->whereJsonContains('metadata->reason', $migrationKey)
            ->pluck('user_id');

        $usersToCredit = User::whereNotIn('id', $usersWithBonus)
            ->where('created_at', '<', now()) // All existing users
            ->get();

        $count = $usersToCredit->count();

        if ($count === 0) {
            $this->info('No users need to be credited. All existing users have already received the bonus.');

            return 0;
        }

        $this->info("Found {$count} users to credit with {$amount} tokens each.");

        if ($dryRun) {
            $this->warn('DRY RUN - No changes will be made.');
            $this->table(
                ['ID', 'Name', 'Email', 'Current Balance', 'New Balance'],
                $usersToCredit->take(20)->map(fn ($user) => [
                    $user->id,
                    $user->name,
                    $user->email,
                    $user->token_balance ?? 0,
                    ($user->token_balance ?? 0) + $amount,
                ])
            );
            if ($count > 20) {
                $this->info('... and '.($count - 20).' more users.');
            }

            return 0;
        }

        if (! $this->confirm("This will credit {$amount} tokens to {$count} users. Continue?")) {
            $this->info('Operation cancelled.');

            return 0;
        }

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        $credited = 0;
        $errors = 0;

        foreach ($usersToCredit as $user) {
            try {
                DB::transaction(function () use ($user, $amount, $migrationKey) {
                    $lockedUser = User::lockForUpdate()->find($user->id);

                    $newBalance = ($lockedUser->token_balance ?? 0) + $amount;
                    $lockedUser->update(['token_balance' => $newBalance]);

                    TokenTransaction::create([
                        'user_id' => $lockedUser->id,
                        'type' => TokenTransaction::TYPE_BONUS,
                        'amount' => $amount,
                        'balance_after' => $newBalance,
                        'description' => 'Welcome to the new token system! Enjoy these free tokens.',
                        'metadata' => ['reason' => $migrationKey],
                    ]);
                });
                $credited++;
            } catch (\Exception $e) {
                $errors++;
                $this->newLine();
                $this->error("Failed to credit user {$user->id}: {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Successfully credited {$credited} users with {$amount} tokens each.");
        if ($errors > 0) {
            $this->warn("Failed to credit {$errors} users.");
        }

        return $errors > 0 ? 1 : 0;
    }
}
