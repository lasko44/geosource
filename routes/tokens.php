<?php

use App\Http\Controllers\Tokens\RedeemTokenCodeController;
use App\Http\Controllers\Tokens\TokenBalanceController;
use App\Http\Controllers\Tokens\TokenCheckoutController;
use App\Http\Controllers\Tokens\TokenCostsController;
use App\Http\Controllers\Tokens\TokenHistoryController;
use App\Http\Controllers\Tokens\TokenPurchaseController;
use App\Http\Controllers\Tokens\TokenSuccessController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    // Token purchase page
    Route::get('/tokens', TokenPurchaseController::class)->name('tokens.index');

    // Checkout flow
    Route::post('/tokens/checkout', TokenCheckoutController::class)->name('tokens.checkout');
    Route::get('/tokens/success', TokenSuccessController::class)->name('tokens.success');

    // Transaction history
    Route::get('/tokens/history', TokenHistoryController::class)->name('tokens.history');

    // Redeem token code
    Route::post('/tokens/redeem', RedeemTokenCodeController::class)->name('tokens.redeem');

    // API endpoints
    Route::get('/api/tokens/balance', TokenBalanceController::class)->name('api.tokens.balance');
    Route::get('/api/tokens/costs', TokenCostsController::class)->name('api.tokens.costs');
});
