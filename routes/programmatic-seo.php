<?php

use App\Http\Controllers\ProgrammaticSeo\ComparisonController;
use App\Http\Controllers\ProgrammaticSeo\IndustryGeoController;
use App\Http\Controllers\ProgrammaticSeo\PlatformOptimizeController;
use App\Http\Controllers\ProgrammaticSeo\UseCaseController;
use Illuminate\Support\Facades\Route;

// Redirect /guides to unified /resources hub
Route::get('/guides', fn () => redirect('/resources', 301))->name('guides');

// Industry GEO pages
Route::get('/geo-for-{slug}', IndustryGeoController::class)
    ->where('slug', '[a-z0-9\-]+')
    ->name('geo-for-industry');

// AI platform optimization pages
Route::get('/optimize-for-{slug}', PlatformOptimizeController::class)
    ->where('slug', '[a-z0-9\-]+')
    ->name('optimize-for-platform');

// Comparison pages
Route::get('/compare/{slug}', ComparisonController::class)
    ->where('slug', '[a-z0-9\-]+')
    ->name('compare');

// Use case "how to" pages
Route::get('/how-to/{slug}', UseCaseController::class)
    ->where('slug', '[a-z0-9\-]+')
    ->name('how-to');
