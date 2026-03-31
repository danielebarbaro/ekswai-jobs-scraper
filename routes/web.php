<?php

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
});

require __DIR__.'/settings.php';
