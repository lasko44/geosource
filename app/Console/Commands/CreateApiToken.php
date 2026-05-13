<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * Creates a Sanctum API token for a user.
 */
class CreateApiToken extends Command
{
    protected $signature = 'api:create-token {email : The user email address} {--name=mcp-server : Token name for identification}';

    protected $description = 'Create a Sanctum API token for a user';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error("User not found: {$this->argument('email')}");

            return 1;
        }

        $token = $user->createToken($this->option('name'));

        $this->info('API token created successfully.');
        $this->newLine();
        $this->line("Token: {$token->plainTextToken}");
        $this->newLine();
        $this->warn('Store this token securely. It will not be shown again.');

        return 0;
    }
}
