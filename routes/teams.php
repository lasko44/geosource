<?php

use App\Http\Controllers\Billing\TeamBillingController;
use App\Http\Controllers\Teams\TeamController;
use App\Http\Controllers\Teams\TeamMemberController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('teams')->name('teams.')->group(function () {
    // Team CRUD routes
    Route::get('/', [TeamController::class, 'index'])->name('index');
    Route::get('/create', [TeamController::class, 'create'])->name('create');
    Route::post('/', [TeamController::class, 'store'])->name('store');
    Route::get('/{team}', [TeamController::class, 'show'])->name('show');
    Route::get('/{team}/edit', [TeamController::class, 'edit'])->name('edit');
    Route::put('/{team}', [TeamController::class, 'update'])->name('update');
    Route::delete('/{team}', [TeamController::class, 'destroy'])->name('destroy');

    // Team member routes
    Route::get('/{team}/members', [TeamMemberController::class, 'index'])->name('members');
    Route::post('/{team}/members', [TeamMemberController::class, 'store'])->name('members.store');
    Route::put('/{team}/members/{user}', [TeamMemberController::class, 'update'])->name('members.update');
    Route::delete('/{team}/members/{user}', [TeamMemberController::class, 'destroy'])->name('members.destroy');
    Route::post('/{team}/leave', [TeamMemberController::class, 'leave'])->name('leave');

    // Team billing routes
    Route::get('/{team}/billing', [TeamBillingController::class, 'index'])->name('billing');
    Route::get('/{team}/billing/plans', [TeamBillingController::class, 'plans'])->name('billing.plans');
    Route::get('/{team}/billing/checkout/{plan}', [TeamBillingController::class, 'checkout'])->name('billing.checkout');
    Route::post('/{team}/billing/subscribe', [TeamBillingController::class, 'subscribe'])->name('billing.subscribe');
    Route::post('/{team}/billing/cancel', [TeamBillingController::class, 'cancel'])->name('billing.cancel');
    Route::post('/{team}/billing/resume', [TeamBillingController::class, 'resume'])->name('billing.resume');
    Route::get('/{team}/billing/portal', [TeamBillingController::class, 'portal'])->name('billing.portal');
});
