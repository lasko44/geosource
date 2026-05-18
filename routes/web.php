<?php

use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Experiments\GuestScanController;
use App\Http\Controllers\Experiments\GuestScanShowController;
use App\Http\Controllers\Experiments\GuestScanStatusController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\SuggestedContentController;
use App\Http\Controllers\Marketing\ProcessUnsubscribeController;
use App\Http\Controllers\Marketing\ShowUnsubscribeController;
use App\Http\Controllers\Marketing\TrackClickController;
use App\Http\Controllers\Marketing\TrackOpenController;
use App\Http\Controllers\Marketing\UnsubscribeSuccessController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', HomeController::class)->name('home');
Route::post('/experiment/scan', GuestScanController::class)->name('experiment.scan');
Route::get('/experiment/scans/{scan}', GuestScanShowController::class)->name('experiment.scan.show');
Route::get('/experiment/scans/{scan}/status', GuestScanStatusController::class)->name('experiment.scan.status');

Route::get('/pricing', function () {
    return Inertia::render('Pricing', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('pricing');

// Sitemap
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

// LLMs.txt for AI crawlers
Route::get('/llms.txt', function () {
    return response()->file(public_path('llms.txt'), [
        'Content-Type' => 'text/plain',
    ]);
})->name('llms');

// Google OAuth
Route::get('/auth/google', [GoogleController::class, 'redirect'])->name('auth.google');
Route::get('/auth/google/callback', [GoogleController::class, 'callback']);

// MCP Server Authorization
Route::get('/auth/mcp', [\App\Http\Controllers\Auth\McpAuthController::class, 'show'])
    ->middleware(['auth', 'verified'])
    ->name('auth.mcp');
Route::post('/auth/mcp', [\App\Http\Controllers\Auth\McpAuthController::class, 'store'])
    ->middleware(['auth', 'verified', 'throttle:5,1'])
    ->name('auth.mcp.authorize');

Route::get('dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::get('mcp', function () {
    return Inertia::render('McpSetup');
})->middleware(['auth', 'verified'])->name('mcp.setup');

// User Guide / Help
Route::get('/help', function () {
    return view('help.guide');
})->name('help');

require __DIR__.'/settings.php';
require __DIR__.'/billing.php';
require __DIR__.'/tokens.php';
require __DIR__.'/teams.php';
require __DIR__.'/scans.php';
require __DIR__.'/scheduled-scans.php';
require __DIR__.'/resources.php';
require __DIR__.'/citations.php';
require __DIR__.'/blog.php';
require __DIR__.'/programmatic-seo.php';

// Marketing email unsubscribe
Route::get('/unsubscribe', ShowUnsubscribeController::class)->name('marketing.unsubscribe');
Route::post('/unsubscribe', ProcessUnsubscribeController::class)->name('marketing.unsubscribe.process');
Route::get('/unsubscribe/success', UnsubscribeSuccessController::class)->name('marketing.unsubscribe.success');
Route::get('/email/track/open', TrackOpenController::class)->name('marketing.track-open');
Route::get('/email/track/click', TrackClickController::class)->name('marketing.track-click');

// Analytics tracking (for marking page views as engaged)
Route::post('/analytics/engaged', AnalyticsController::class)->name('analytics.engaged');

// RAG-powered suggested content (public, cached)
Route::get('/api/suggested-content', SuggestedContentController::class)
    ->middleware('throttle:30,1')
    ->name('suggested-content');
