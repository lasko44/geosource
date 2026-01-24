<?php

use App\Http\Controllers\CitationController;
use App\Http\Controllers\GA4Controller;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    /*
    |--------------------------------------------------------------------------
    | Citation Tracking Routes
    |--------------------------------------------------------------------------
    */

    // Dashboard
    Route::get('/citations', [CitationController::class, 'index'])->name('citations.index');

    // Citation Queries
    Route::get('/citations/queries/create', [CitationController::class, 'create'])->name('citations.queries.create');

    Route::post('/citations/queries', [CitationController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('citations.queries.store');

    Route::get('/citations/queries/{query}', [CitationController::class, 'show'])->name('citations.queries.show');

    Route::put('/citations/queries/{query}', [CitationController::class, 'update'])
        ->middleware('throttle:30,1')
        ->name('citations.queries.update');

    Route::delete('/citations/queries/{query}', [CitationController::class, 'destroy'])
        ->name('citations.queries.destroy');

    // Manual Citation Checks - limit to 5/minute to prevent API cost abuse
    Route::post('/citations/queries/{query}/check', [CitationController::class, 'check'])
        ->middleware('throttle:5,1')
        ->name('citations.queries.check');

    // Check Status (for polling)
    Route::get('/citations/checks/{check}/status', [CitationController::class, 'checkStatus'])
        ->name('citations.checks.status');

    // Trends API
    Route::get('/citations/trends', [CitationController::class, 'trends'])
        ->name('citations.trends');

    // Alerts
    Route::get('/citations/alerts', [CitationController::class, 'alerts'])
        ->name('citations.alerts');

    Route::post('/citations/alerts/mark-read', [CitationController::class, 'markAlertsRead'])
        ->middleware('throttle:30,1')
        ->name('citations.alerts.mark-read');

    /*
    |--------------------------------------------------------------------------
    | GA4 Analytics Routes
    |--------------------------------------------------------------------------
    */

    // Dashboard
    Route::get('/analytics/ga4', [GA4Controller::class, 'index'])->name('citations.analytics');

    // OAuth Flow
    Route::get('/analytics/ga4/connect', [GA4Controller::class, 'connect'])
        ->middleware('throttle:5,1')
        ->name('citations.ga4.connect');

    Route::get('/analytics/ga4/callback', [GA4Controller::class, 'callback'])
        ->name('citations.ga4.callback');

    Route::post('/analytics/ga4/select-property', [GA4Controller::class, 'selectProperty'])
        ->middleware('throttle:10,1')
        ->name('citations.ga4.select-property');

    // Connection Management
    Route::get('/analytics/ga4/{connection}/referrals', [GA4Controller::class, 'referrals'])
        ->name('citations.ga4.referrals');

    Route::get('/analytics/ga4/{connection}/ai-traffic', [GA4Controller::class, 'aiTraffic'])
        ->name('citations.ga4.ai-traffic');

    Route::post('/analytics/ga4/{connection}/sync', [GA4Controller::class, 'sync'])
        ->middleware('throttle:5,1')
        ->name('citations.ga4.sync');

    Route::delete('/analytics/ga4/{connection}', [GA4Controller::class, 'disconnect'])
        ->name('citations.ga4.disconnect');
});
