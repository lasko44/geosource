<?php

use App\Http\Controllers\Api\V1\Citations\CitationQueryApiController;
use App\Http\Controllers\Api\V1\Citations\CitationTrendsApiController;
use App\Http\Controllers\Api\V1\Citations\RunCitationCheckApiController;
use App\Http\Controllers\Api\V1\Dashboard\DashboardApiController;
use App\Http\Controllers\Api\V1\Scans\ScanApiController;
use App\Http\Controllers\Api\V1\Scans\ScanCancelApiController;
use App\Http\Controllers\Api\V1\Scans\ScanStatusApiController;
use App\Http\Controllers\Api\V1\Tokens\TokenBalanceApiController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'throttle:60,1'])->prefix('v1')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Scan Routes
    |--------------------------------------------------------------------------
    */

    Route::get('/scans', [ScanApiController::class, 'index'])->name('api.scans.index');
    Route::post('/scans', [ScanApiController::class, 'store'])->name('api.scans.store')->middleware('throttle:10,1');
    Route::get('/scans/{scan}', [ScanApiController::class, 'show'])->name('api.scans.show');
    Route::get('/scans/{scan}/status', ScanStatusApiController::class)->name('api.scans.status');
    Route::post('/scans/{scan}/cancel', ScanCancelApiController::class)->name('api.scans.cancel');

    /*
    |--------------------------------------------------------------------------
    | Citation Routes
    |--------------------------------------------------------------------------
    */

    Route::get('/citations/queries', [CitationQueryApiController::class, 'index'])->name('api.citations.queries.index');
    Route::post('/citations/queries', [CitationQueryApiController::class, 'store'])->name('api.citations.queries.store')->middleware('throttle:10,1');
    Route::post('/citations/queries/{query}/check', RunCitationCheckApiController::class)->name('api.citations.check');
    Route::get('/citations/trends', CitationTrendsApiController::class)->name('api.citations.trends');

    /*
    |--------------------------------------------------------------------------
    | Token Routes
    |--------------------------------------------------------------------------
    */

    Route::get('/tokens/balance', TokenBalanceApiController::class)->name('api.tokens.balance');

    /*
    |--------------------------------------------------------------------------
    | Dashboard Routes
    |--------------------------------------------------------------------------
    */

    Route::get('/dashboard', DashboardApiController::class)->name('api.dashboard');
});
