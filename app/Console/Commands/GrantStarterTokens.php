<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\TokenService;
use Illuminate\Console\Command;

class GrantStarterTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tokens:grant-starter
                            {--amount=25 : Number of tokens to grant}
                            {--reason=Welcome bonus : Reason for the bonus}
                            {--dry-run : Show what would happen without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Grant starter tokens to existing non-admin users who have zero tokens';

    /**
     * Execute the console command.
     */
    public function handle(TokenService $tokenService): int
    {
        $amount = (int) $this->option('amount');
        $reason = $this->option('reason');
        $dryRun = $this->option('dry-run');

        // Find non-admin users with zero tokens
        $users = User::where('is_admin', false)
            ->where('token_balance', 0)
            ->get();

        if ($users->isEmpty()) {
            $this->info('No eligible users found (non-admin users with zero tokens).');

            return Command::SUCCESS;
        }

        $this->info("Found {$users->count()} eligible users.");

        if ($dryRun) {
            $this->warn('DRY RUN - No changes will be made.');
            $this->table(
                ['ID', 'Name', 'Email', 'Current Balance', 'Would Receive'],
                $users->map(fn ($u) => [$u->id, $u->name, $u->email, $u->token_balance, $amount])
            );

            return Command::SUCCESS;
        }

        if (! $this->confirm("Grant {$amount} tokens to {$users->count()} users?")) {
            $this->info('Cancelled.');

            return Command::SUCCESS;
        }

        $bar = $this->output->createProgressBar($users->count());
        $bar->start();

        $granted = 0;
        foreach ($users as $user) {
            try {
                $tokenService->creditBonus($user, $amount, $reason);
                $granted++;
            } catch (\Exception $e) {
                $this->newLine();
                $this->error("Failed to grant tokens to {$user->email}: {$e->getMessage()}");
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Successfully granted {$amount} tokens to {$granted} users.");

        return Command::SUCCESS;
    }
}
