<?php

use App\Http\Controllers\ScheduledScanController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/scheduled-scans', [ScheduledScanController::class, 'index'])->name('scheduled-scans.index');
    Route::get('/scheduled-scans/create', [ScheduledScanController::class, 'create'])->name('scheduled-scans.create');
    Route::post('/scheduled-scans', [ScheduledScanController::class, 'store'])->name('scheduled-scans.store');
    Route::get('/scheduled-scans/{scheduledScan}/edit', [ScheduledScanController::class, 'edit'])->name('scheduled-scans.edit');
    Route::put('/scheduled-scans/{scheduledScan}', [ScheduledScanController::class, 'update'])->name('scheduled-scans.update');
    Route::post('/scheduled-scans/{scheduledScan}/toggle', [ScheduledScanController::class, 'toggle'])->name('scheduled-scans.toggle');
    Route::post('/scheduled-scans/{scheduledScan}/run', [ScheduledScanController::class, 'runNow'])->name('scheduled-scans.run');
    Route::delete('/scheduled-scans/{scheduledScan}', [ScheduledScanController::class, 'destroy'])->name('scheduled-scans.destroy');
});
