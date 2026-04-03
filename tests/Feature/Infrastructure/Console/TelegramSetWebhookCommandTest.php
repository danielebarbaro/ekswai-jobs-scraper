<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    Config::set('services.telegram.bot_token', 'test-bot-token');
});

it('registers the webhook with telegram api', function (): void {
    Http::fake([
        'api.telegram.org/*' => Http::response(['ok' => true, 'description' => 'Webhook was set']),
    ]);

    $this->artisan('telegram:set-webhook', ['url' => 'https://example.com/api/telegram/webhook'])
        ->assertExitCode(0);

    Http::assertSent(fn ($request): bool => str_contains((string) $request->url(), 'setWebhook')
        && $request['url'] === 'https://example.com/api/telegram/webhook'
    );
});

it('fails when telegram api returns error', function (): void {
    Http::fake([
        'api.telegram.org/*' => Http::response(['ok' => false, 'description' => 'Bad request'], 400),
    ]);

    $this->artisan('telegram:set-webhook', ['url' => 'https://example.com/api/telegram/webhook'])
        ->assertExitCode(1);
});
