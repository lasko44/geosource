<?php

use App\Http\Controllers\Billing\UserBillingController;
use App\Http\Controllers\Billing\WebhookController;
use Illuminate\Support\Facades\Route;

// Stripe webhook (no auth middleware)
Route::post('stripe/webhook', [WebhookController::class, 'handleWebhook'])
    ->name('cashier.webhook');

// User billing routes (authenticated)
Route::middleware(['auth', 'verified'])->prefix('billing')->name('billing.')->group(function () {
    Route::get('/', [UserBillingController::class, 'index'])->name('index');
    Route::get('/plans', [UserBillingController::class, 'plans'])->name('plans');
    Route::get('/checkout/{plan}', [UserBillingController::class, 'checkout'])->name('checkout');
    Route::post('/subscribe', [UserBillingController::class, 'subscribe'])->name('subscribe');
    Route::post('/cancel', [UserBillingController::class, 'cancel'])->name('cancel');
    Route::post('/resume', [UserBillingController::class, 'resume'])->name('resume');
    Route::get('/payment-methods', [UserBillingController::class, 'paymentMethods'])->name('payment-methods');
    Route::post('/payment-methods', [UserBillingController::class, 'addPaymentMethod'])->name('payment-methods.store');
    Route::put('/payment-methods/default', [UserBillingController::class, 'setDefaultPaymentMethod'])->name('payment-methods.default');
    Route::delete('/payment-methods', [UserBillingController::class, 'deletePaymentMethod'])->name('payment-methods.destroy');
    Route::get('/invoices', [UserBillingController::class, 'invoices'])->name('invoices');
    Route::get('/invoices/{invoice}', [UserBillingController::class, 'downloadInvoice'])->name('invoices.download');
    Route::get('/portal', [UserBillingController::class, 'portal'])->name('portal');
});
