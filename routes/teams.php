<?php

use App\Http\Controllers\Teams\TeamController;
use App\Http\Controllers\Teams\TeamInvitationController;
use App\Http\Controllers\Teams\TeamMemberController;
use Illuminate\Support\Facades\Route;

// Public invitation acceptance route (no auth required to view)
Route::get('/invitations/{token}', [TeamInvitationController::class, 'show'])->name('teams.invitations.show');

// Authenticated invitation acceptance
Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/invitations/{token}/accept', [TeamInvitationController::class, 'accept'])->name('teams.invitations.accept');
});

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
    Route::put('/{team}/members/{user}', [TeamMemberController::class, 'update'])->name('members.update');
    Route::delete('/{team}/members/{user}', [TeamMemberController::class, 'destroy'])->name('members.destroy');
    Route::post('/{team}/leave', [TeamMemberController::class, 'leave'])->name('leave');
    Route::post('/{team}/transfer', [TeamController::class, 'transferOwnership'])->name('transfer');

    // Team invitation routes
    Route::post('/{team}/invitations', [TeamInvitationController::class, 'store'])->name('invitations.store');
    Route::delete('/{team}/invitations/{invitation}', [TeamInvitationController::class, 'destroy'])->name('invitations.destroy');
    Route::post('/{team}/invitations/{invitation}/resend', [TeamInvitationController::class, 'resend'])->name('invitations.resend');
});
