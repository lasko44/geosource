<?php

use App\Http\Controllers\TokenController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    // Token purchase page
    Route::get('/tokens', [TokenController::class, 'index'])->name('tokens.index');

    // Checkout flow
    Route::post('/tokens/checkout', [TokenController::class, 'checkout'])->name('tokens.checkout');
    Route::get('/tokens/success', [TokenController::class, 'success'])->name('tokens.success');

    // Transaction history
    Route::get('/tokens/history', [TokenController::class, 'history'])->name('tokens.history');

    // API endpoints
    Route::get('/api/tokens/balance', [TokenController::class, 'balance'])->name('api.tokens.balance');
    Route::get('/api/tokens/costs', [TokenController::class, 'costs'])->name('api.tokens.costs');
});
