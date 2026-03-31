<?php

use App\Infrastructure\Http\Controllers\CompanySubscriptionController;
use App\Infrastructure\Http\Controllers\DashboardController;
use App\Infrastructure\Http\Controllers\JobPostingStatusController;
use App\Infrastructure\Http\Controllers\LandingController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LandingController::class, 'redirect'])->name('home');
Route::get('/{locale}', [LandingController::class, 'show'])
    ->name('landing')
    ->where('locale', 'en|it');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::get('companies', [CompanySubscriptionController::class, 'index'])->name('companies.index');
    Route::post('companies/follow', [CompanySubscriptionController::class, 'follow'])->name('companies.follow');
    Route::delete('companies/{company}/unfollow', [CompanySubscriptionController::class, 'unfollow'])->name('companies.unfollow');
    Route::patch('companies/{company}/notifications', [CompanySubscriptionController::class, 'toggleNotifications'])->name('companies.notifications');

    Route::patch('job-postings/{jobPosting}/status', [JobPostingStatusController::class, 'update'])->name('job-postings.status');
});

require __DIR__.'/settings.php';
