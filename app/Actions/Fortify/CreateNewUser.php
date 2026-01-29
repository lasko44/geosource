<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Mail\AdminNewUserNotification;
use App\Models\TokenTransaction;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    // Registration bonus tokens for new users
    private const REGISTRATION_BONUS_TOKENS = 20;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
        ])->validate();

        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
            'token_balance' => self::REGISTRATION_BONUS_TOKENS,
        ]);

        // Create transaction record for registration bonus
        TokenTransaction::create([
            'user_id' => $user->id,
            'type' => TokenTransaction::TYPE_BONUS,
            'amount' => self::REGISTRATION_BONUS_TOKENS,
            'balance_after' => self::REGISTRATION_BONUS_TOKENS,
            'description' => 'Welcome bonus - free tokens on registration',
            'metadata' => ['reason' => 'registration_bonus'],
        ]);

        // Notify admin of new registration
        Mail::to('matt@geosource.ai')->send(new AdminNewUserNotification($user));

        return $user;
    }
}
