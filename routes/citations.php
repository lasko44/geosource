<?php

use App\Http\Controllers\Citations\CitationAlertController;
use App\Http\Controllers\Citations\CitationCheckController;
use App\Http\Controllers\Citations\CitationQueryController;
use App\Http\Controllers\Citations\CitationTrendController;
use App\Http\Controllers\GA4Controller;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    /*
    |--------------------------------------------------------------------------
    | Citation Query Routes (Resource)
    |--------------------------------------------------------------------------
    */

    Route::resource('citations/queries', CitationQueryController::class)
        ->names('citations.queries')
        ->parameters(['queries' => 'query'])
        ->except(['edit'])
        ->middleware([
            'store' => 'throttle:10,1',
            'update' => 'throttle:30,1',
        ]);

    // Dashboard alias
    Route::get('citations', [CitationQueryController::class, 'index'])
        ->name('citations.index');

    /*
    |--------------------------------------------------------------------------
    | Citation Check Routes
    |--------------------------------------------------------------------------
    */

    Route::post('citations/queries/{query}/check', [CitationCheckController::class, 'store'])
        ->middleware('throttle:20,1')
        ->name('citations.checks.store');

    Route::post('citations/queries/{query}/check-all', [CitationCheckController::class, 'storeBulk'])
        ->middleware('throttle:10,1')
        ->name('citations.checks.store-bulk');

    Route::get('citations/checks/{check}/status', [CitationCheckController::class, 'show'])
        ->name('citations.checks.show');

    /*
    |--------------------------------------------------------------------------
    | Citation Trend Routes
    |--------------------------------------------------------------------------
    */

    Route::get('citations/trends', [CitationTrendController::class, 'index'])
        ->name('citations.trends.index');

    /*
    |--------------------------------------------------------------------------
    | Citation Alert Routes
    |--------------------------------------------------------------------------
    */

    Route::get('citations/alerts', [CitationAlertController::class, 'index'])
        ->name('citations.alerts.index');

    Route::post('citations/alerts/mark-read', [CitationAlertController::class, 'markRead'])
        ->middleware('throttle:30,1')
        ->name('citations.alerts.mark-read');

    /*
    |--------------------------------------------------------------------------
    | GA4 Analytics Routes
    |--------------------------------------------------------------------------
    */

    Route::prefix('analytics/ga4')->name('citations.ga4.')->group(function () {
        Route::get('/', [GA4Controller::class, 'index'])->name('index');
        Route::get('connect', [GA4Controller::class, 'connect'])
            ->middleware('throttle:5,1')
            ->name('connect');
        Route::get('callback', [GA4Controller::class, 'callback'])->name('callback');
        Route::post('select-property', [GA4Controller::class, 'selectProperty'])
            ->middleware('throttle:10,1')
            ->name('select-property');

        Route::prefix('{connection}')->group(function () {
            Route::get('referrals', [GA4Controller::class, 'referrals'])->name('referrals');
            Route::get('ai-traffic', [GA4Controller::class, 'aiTraffic'])->name('ai-traffic');
            Route::post('sync', [GA4Controller::class, 'sync'])
                ->middleware('throttle:5,1')
                ->name('sync');
            Route::get('sync-status', [GA4Controller::class, 'syncStatus'])->name('sync-status');
            Route::delete('/', [GA4Controller::class, 'disconnect'])->name('disconnect');
        });
    });
});
