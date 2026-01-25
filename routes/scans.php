<?php

use App\Http\Controllers\ScanController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    // Rate limit scan creation to prevent abuse (10 per minute)
    Route::post('/scan', [ScanController::class, 'scan'])
        ->middleware('throttle:10,1')
        ->name('scan');

    // Check cooldown status for a URL
    Route::post('/scan/check-cooldown', [ScanController::class, 'checkCooldown'])
        ->name('scan.check-cooldown');

    // Bulk URL scanning
    Route::get('/scans/bulk', [ScanController::class, 'bulkIndex'])->name('scans.bulk');
    Route::post('/scans/bulk', [ScanController::class, 'bulkScan'])
        ->middleware('throttle:5,1')
        ->name('scans.bulk.store');
    Route::post('/scans/bulk/status', [ScanController::class, 'bulkStatus'])
        ->name('scans.bulk.status');

    Route::get('/scans', [ScanController::class, 'list'])->name('scans.index');
    Route::get('/scans/{scan}', [ScanController::class, 'show'])->name('scans.show');
    Route::get('/scans/{scan}/status', [ScanController::class, 'status'])->name('scans.status');
    Route::post('/scans/{scan}/cancel', [ScanController::class, 'cancel'])->name('scans.cancel');
    Route::delete('/scans/{scan}', [ScanController::class, 'destroy'])->name('scans.destroy');

    // Rate limit rescan to prevent abuse (10 per minute)
    Route::post('/scans/{scan}/rescan', [ScanController::class, 'rescan'])
        ->middleware('throttle:10,1')
        ->name('scans.rescan');

    // Rate limit exports to prevent resource exhaustion (20 per minute)
    Route::get('/scans/{scan}/export/pdf', [ScanController::class, 'exportPdf'])
        ->middleware('throttle:20,1')
        ->name('scans.export.pdf');

    // Rate limit email reports to prevent spam (5 per minute)
    Route::post('/scans/{scan}/email', [ScanController::class, 'emailReport'])
        ->middleware('throttle:5,1')
        ->name('scans.email');
});
