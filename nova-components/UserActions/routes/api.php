<?php

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/verify-email/{userId}', function (Request $request, int $userId) {
    $user = User::findOrFail($userId);

    if ($user->hasVerifiedEmail()) {
        return response()->json(['message' => 'Email is already verified.']);
    }

    $user->markEmailAsVerified();

    return response()->json(['message' => 'Email marked as verified.']);
});

Route::post('/resend-verification/{userId}', function (Request $request, int $userId) {
    $user = User::findOrFail($userId);

    if ($user->hasVerifiedEmail()) {
        return response()->json(['message' => 'Email is already verified.']);
    }

    try {
        $user->sendEmailVerificationNotification();
    } catch (\Throwable $e) {
        report($e);

        return response()->json(['message' => 'Failed to send: '.$e->getMessage()], 500);
    }

    return response()->json(['message' => 'Verification email sent.']);
});
