<?php

use App\Http\Controllers\Billing\AddPaymentMethodController;
use App\Http\Controllers\Billing\BillingDashboardController;
use App\Http\Controllers\Billing\BillingPortalController;
use App\Http\Controllers\Billing\CancelSubscriptionController;
use App\Http\Controllers\Billing\CheckoutController;
use App\Http\Controllers\Billing\DeletePaymentMethodController;
use App\Http\Controllers\Billing\DownloadInvoiceController;
use App\Http\Controllers\Billing\InvoicesController;
use App\Http\Controllers\Billing\PaymentMethodsController;
use App\Http\Controllers\Billing\PlansController;
use App\Http\Controllers\Billing\ResumeSubscriptionController;
use App\Http\Controllers\Billing\SetDefaultPaymentMethodController;
use App\Http\Controllers\Billing\SubscribeController;
use App\Http\Controllers\Billing\ThankYouController;
use App\Http\Controllers\Billing\WebhookController;
use Illuminate\Support\Facades\Route;

// Stripe webhook (no auth middleware)
Route::post('stripe/webhook', [WebhookController::class, 'handleWebhook'])
    ->name('cashier.webhook');

// User billing routes (authenticated)
Route::middleware(['auth', 'verified'])->prefix('billing')->name('billing.')->group(function () {
    Route::get('/', BillingDashboardController::class)->name('index');
    Route::get('/plans', PlansController::class)->name('plans');
    Route::get('/checkout/{plan}', CheckoutController::class)->name('checkout');
    Route::get('/thank-you', ThankYouController::class)->name('thank-you');
    Route::post('/subscribe', SubscribeController::class)->name('subscribe');
    Route::post('/cancel', CancelSubscriptionController::class)->name('cancel');
    Route::post('/resume', ResumeSubscriptionController::class)->name('resume');
    Route::get('/payment-methods', PaymentMethodsController::class)->name('payment-methods');
    Route::post('/payment-methods', AddPaymentMethodController::class)->name('payment-methods.store');
    Route::put('/payment-methods/default', SetDefaultPaymentMethodController::class)->name('payment-methods.default');
    Route::delete('/payment-methods', DeletePaymentMethodController::class)->name('payment-methods.destroy');
    Route::get('/invoices', InvoicesController::class)->name('invoices');
    Route::get('/invoices/{invoice}', DownloadInvoiceController::class)->name('invoices.download');
    Route::get('/portal', BillingPortalController::class)->name('portal');
});
