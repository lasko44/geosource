<?php

use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\MarketingUnsubscribeController;
use App\Http\Controllers\ScanController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('/pricing', function () {
    return Inertia::render('Pricing', [
        'plans' => config('billing.plans.user'),
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

Route::get('dashboard', [ScanController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// User Guide / Help
Route::get('/help', function () {
    return view('help.guide');
})->name('help');

require __DIR__.'/settings.php';
require __DIR__.'/billing.php';
require __DIR__.'/teams.php';
require __DIR__.'/scans.php';
require __DIR__.'/scheduled-scans.php';
require __DIR__.'/resources.php';
require __DIR__.'/citations.php';
require __DIR__.'/blog.php';

// Marketing email unsubscribe
Route::get('/unsubscribe', [MarketingUnsubscribeController::class, 'unsubscribe'])->name('marketing.unsubscribe');
Route::post('/unsubscribe', [MarketingUnsubscribeController::class, 'processUnsubscribe'])->name('marketing.unsubscribe.process');
Route::get('/unsubscribe/success', [MarketingUnsubscribeController::class, 'success'])->name('marketing.unsubscribe.success');
Route::get('/email/track/open', [MarketingUnsubscribeController::class, 'trackOpen'])->name('marketing.track-open');
Route::get('/email/track/click', [MarketingUnsubscribeController::class, 'trackClick'])->name('marketing.track-click');
