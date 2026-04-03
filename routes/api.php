<?php

declare(strict_types=1);

use App\Infrastructure\Http\Controllers\TelegramWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('telegram/webhook', TelegramWebhookController::class)
    ->name('telegram.webhook');
