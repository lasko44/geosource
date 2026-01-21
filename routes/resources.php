<?php

use App\Http\Controllers\ResourceController;
use Illuminate\Support\Facades\Route;

// Resource hub pages (under /resources)
Route::prefix('resources')->group(function () {
    Route::get('/', [ResourceController::class, 'index'])->name('resources.index');
    Route::get('/what-is-geo', [ResourceController::class, 'whatIsGeo'])->name('resources.what-is-geo');
    Route::get('/geo-vs-seo', [ResourceController::class, 'geoVsSeo'])->name('resources.geo-vs-seo');
    Route::get('/how-ai-search-works', [ResourceController::class, 'howAiSearchWorks'])->name('resources.how-ai-search-works');
    Route::get('/how-llms-cite-sources', [ResourceController::class, 'howLlmsCiteSources'])->name('resources.how-llms-cite-sources');
    Route::get('/what-is-a-geo-score', [ResourceController::class, 'whatIsGeoScore'])->name('resources.what-is-a-geo-score');
    Route::get('/geo-content-framework', [ResourceController::class, 'geoContentFramework'])->name('resources.geo-content-framework');
    Route::get('/why-llms-txt-matters', [ResourceController::class, 'whyLlmsTxtMatters'])->name('resources.why-llms-txt-matters');
    Route::get('/why-ssr-matters-for-geo', [ResourceController::class, 'whySsrMattersForGeo'])->name('resources.why-ssr-matters-for-geo');
    Route::get('/e-e-a-t-and-geo', [ResourceController::class, 'eeatAndGeo'])->name('resources.e-e-a-t-and-geo');
    Route::get('/ai-citations-and-geo', [ResourceController::class, 'aiCitationsAndGeo'])->name('resources.ai-citations-and-geo');
    Route::get('/ai-accessibility-for-geo', [ResourceController::class, 'aiAccessibilityForGeo'])->name('resources.ai-accessibility-for-geo');
    Route::get('/content-freshness-for-geo', [ResourceController::class, 'contentFreshnessForGeo'])->name('resources.content-freshness-for-geo');
    Route::get('/readability-and-geo', [ResourceController::class, 'readabilityAndGeo'])->name('resources.readability-and-geo');
    Route::get('/question-coverage-for-geo', [ResourceController::class, 'questionCoverageForGeo'])->name('resources.question-coverage-for-geo');
    Route::get('/multimedia-and-geo', [ResourceController::class, 'multimediaAndGeo'])->name('resources.multimedia-and-geo');
});

// Programmatic GEO pages (root level for maximum AI visibility)
Route::get('/definitions', [ResourceController::class, 'definitions'])->name('definitions');
Route::get('/geo-score-explained', [ResourceController::class, 'geoScoreExplained'])->name('geo-score-explained');
Route::get('/geo-optimization-checklist', [ResourceController::class, 'geoOptimizationChecklist'])->name('geo-optimization-checklist');
Route::get('/ai-search-visibility-guide', [ResourceController::class, 'aiSearchVisibilityGuide'])->name('ai-search-visibility-guide');
