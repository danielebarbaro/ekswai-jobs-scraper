<?php

use App\Http\Controllers\CompanySubscriptionController;
use App\Http\Controllers\LandingController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [LandingController::class, 'redirect'])->name('home');
Route::get('/{locale}', [LandingController::class, 'show'])
    ->name('landing')
    ->where('locale', 'en|it');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    Route::get('companies', [CompanySubscriptionController::class, 'index'])->name('companies.index');
    Route::post('companies/follow', [CompanySubscriptionController::class, 'follow'])->name('companies.follow');
    Route::delete('companies/{company}/unfollow', [CompanySubscriptionController::class, 'unfollow'])->name('companies.unfollow');
    Route::patch('companies/{company}/notifications', [CompanySubscriptionController::class, 'toggleNotifications'])->name('companies.notifications');
});

require __DIR__.'/settings.php';
