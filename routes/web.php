<?php

use App\Infrastructure\Http\Controllers\CompanySubscriptionController;
use App\Infrastructure\Http\Controllers\DashboardController;
use App\Infrastructure\Http\Controllers\JobFilterController;
use App\Infrastructure\Http\Controllers\JobPostingStatusController;
use App\Infrastructure\Http\Controllers\LandingController;
use App\Infrastructure\Http\Middleware\RedirectAdmin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', [LandingController::class, 'redirect'])->name('home');
Route::get('/{locale}', [LandingController::class, 'show'])
    ->name('landing')
    ->where('locale', 'en|it');

Route::middleware(['auth', 'verified', RedirectAdmin::class])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::get('companies', [CompanySubscriptionController::class, 'index'])->name('companies.index');
    Route::post('companies/follow', [CompanySubscriptionController::class, 'follow'])->name('companies.follow');
    Route::post('companies/load-defaults', [CompanySubscriptionController::class, 'loadDefaults'])->name('companies.load-defaults');
    Route::delete('companies/{company}/unfollow', [CompanySubscriptionController::class, 'unfollow'])->name('companies.unfollow');
    Route::patch('companies/{company}/notifications', [CompanySubscriptionController::class, 'toggleNotifications'])->name('companies.notifications');
    Route::post('companies/{company}/sync', [CompanySubscriptionController::class, 'sync'])->name('companies.sync');

    Route::patch('job-postings/{jobPosting}/status', [JobPostingStatusController::class, 'update'])->name('job-postings.status');

    Route::get('filters', [JobFilterController::class, 'index'])->name('job-filters.index');
    Route::post('filters', [JobFilterController::class, 'store'])->name('job-filters.store');
    Route::put('filters/{jobFilter}', [JobFilterController::class, 'update'])->name('job-filters.update');
    Route::delete('filters/{jobFilter}', [JobFilterController::class, 'destroy'])->name('job-filters.destroy');

    Route::patch('user/locale', function (Request $request) {
        $request->validate(['locale' => 'required|in:en,it']);
        $request->user()->update(['locale' => $request->locale]);

        return back();
    })->name('user.locale');
});

require __DIR__.'/settings.php';
