<?php

use App\Http\Controllers\Scans\BulkScanController;
use App\Http\Controllers\Scans\BulkScanStatusController;
use App\Http\Controllers\Scans\CheckCooldownController;
use App\Http\Controllers\Scans\EmailReportController;
use App\Http\Controllers\Scans\ExportPdfController;
use App\Http\Controllers\Scans\ScanCancelController;
use App\Http\Controllers\Scans\ScanController;
use App\Http\Controllers\Scans\ScanRescanController;
use App\Http\Controllers\Scans\ScanStatusController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    // Scan store with throttle
    Route::post('/scan', [ScanController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('scan');

    // Check cooldown
    Route::post('/scan/check-cooldown', CheckCooldownController::class)->name('scan.check-cooldown');

    // Bulk scan routes (must come before /scans/{scan})
    Route::get('/scans/bulk', [BulkScanController::class, 'index'])->name('scans.bulk');
    Route::post('/scans/bulk', [BulkScanController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('scans.bulk.store');
    Route::post('/scans/bulk/status', BulkScanStatusController::class)->name('scans.bulk.status');

    // Scan resource routes
    Route::get('/scans', [ScanController::class, 'index'])->name('scans.index');
    Route::get('/scans/{scan}', [ScanController::class, 'show'])->name('scans.show');
    Route::delete('/scans/{scan}', [ScanController::class, 'destroy'])->name('scans.destroy');

    // Scan action routes
    Route::get('/scans/{scan}/status', ScanStatusController::class)->name('scans.status');
    Route::post('/scans/{scan}/cancel', ScanCancelController::class)->name('scans.cancel');

    Route::post('/scans/{scan}/rescan', ScanRescanController::class)
        ->middleware('throttle:10,1')
        ->name('scans.rescan');

    Route::get('/scans/{scan}/export/pdf', ExportPdfController::class)
        ->middleware('throttle:20,1')
        ->name('scans.export.pdf');

    Route::post('/scans/{scan}/email', EmailReportController::class)
        ->middleware('throttle:5,1')
        ->name('scans.email');
});
