<?php

use App\Http\Controllers\BlogController;
use Illuminate\Support\Facades\Route;

Route::prefix('blog')->group(function () {
    // /blog as a destination is replaced by /research in the new IA.
    // Individual posts still resolve at /blog/{post} so externally-shared
    // links keep working (the research-study pages live under /blog/*).
    Route::get('/', fn () => redirect('/research', 301))->name('blog.index');
    Route::get('/{post}', [BlogController::class, 'show'])->name('blog.show');
    Route::post('/{post}/share', [BlogController::class, 'trackShare'])->name('blog.share');
});
