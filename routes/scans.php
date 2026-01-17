<?php

use App\Http\Controllers\ScanController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/scan', [ScanController::class, 'scan'])->name('scan');
    Route::get('/scans', [ScanController::class, 'list'])->name('scans.index');
    Route::get('/scans/{scan}', [ScanController::class, 'show'])->name('scans.show');
    Route::delete('/scans/{scan}', [ScanController::class, 'destroy'])->name('scans.destroy');
    Route::post('/scans/{scan}/rescan', [ScanController::class, 'rescan'])->name('scans.rescan');
});
