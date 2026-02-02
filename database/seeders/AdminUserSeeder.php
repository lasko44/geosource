<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'matthew.laszkiewicz@gmail.com'],
            [
                'name' => 'Matthew Laszkiewicz',
                'password' => Hash::make('PasswordPassword1!'),
                'is_admin' => true,
                'token_balance' => 1000,
                'email_verified_at' => now(),
            ]
        );

        $this->command->info('Admin user created: matthew.laszkiewicz@gmail.com');
    }
}
