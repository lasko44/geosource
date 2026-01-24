<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    /**
     * Redirect to Google OAuth.
     */
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle Google OAuth callback.
     */
    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Exception $e) {
            return redirect('/login')->withErrors([
                'email' => 'Unable to authenticate with Google. Please try again.',
            ]);
        }

        // Check if user exists with this Google ID
        $user = User::where('google_id', $googleUser->getId())->first();

        if ($user) {
            // Existing user - log them in
            Auth::login($user, remember: true);

            return redirect()->intended('/dashboard');
        }

        // Check if user exists with this email
        $user = User::where('email', $googleUser->getEmail())->first();

        if ($user) {
            // Link Google account to existing user
            $user->update([
                'google_id' => $googleUser->getId(),
                'avatar' => $googleUser->getAvatar(),
            ]);

            Auth::login($user, remember: true);

            return redirect()->intended('/dashboard');
        }

        // Create new user
        $user = User::create([
            'name' => $googleUser->getName(),
            'email' => $googleUser->getEmail(),
            'google_id' => $googleUser->getId(),
            'avatar' => $googleUser->getAvatar(),
            'password' => Hash::make(Str::random(24)),
            'email_verified_at' => now(),
        ]);

        Auth::login($user, remember: true);

        return redirect('/dashboard');
    }
}
