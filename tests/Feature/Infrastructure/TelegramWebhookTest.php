<?php

declare(strict_types=1);

use App\Domain\Company\Company;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    Config::set('services.telegram.bot_token', 'test-bot-token');
    Config::set('services.telegram.admin_chat_id', '123456');

    Http::fake([
        'api.telegram.org/*' => Http::response(['ok' => true]),
        'apply.workable.com/api/v1/widget/accounts/patchstack' => Http::response([
            'name' => 'Patchstack',
            'jobs' => [],
        ]),
        'apply.workable.com/api/v1/widget/accounts/nonexistent' => Http::response([], 404),
    ]);
});

it('creates a company from a valid workable URL', function (): void {
    $this->postJson('/api/telegram/webhook', [
        'message' => [
            'chat' => ['id' => 123456],
            'text' => 'https://apply.workable.com/patchstack',
        ],
    ])->assertOk();

    expect(Company::query()->where('provider_slug', 'patchstack')->exists())->toBeTrue();

    Http::assertSent(fn ($request): bool => str_contains((string) $request->url(), 'api.telegram.org')
        && str_contains((string) $request['text'], 'Patchstack')
    );
});

it('replies with error for already existing company', function (): void {
    Company::factory()->create([
        'provider' => 'workable',
        'provider_slug' => 'patchstack',
        'name' => 'Patchstack',
    ]);

    $this->postJson('/api/telegram/webhook', [
        'message' => [
            'chat' => ['id' => 123456],
            'text' => 'https://apply.workable.com/patchstack',
        ],
    ])->assertOk();

    Http::assertSent(fn ($request): bool => str_contains((string) $request->url(), 'api.telegram.org')
        && str_contains((string) $request['text'], 'Already exists')
    );
});

it('replies with error for unparseable URL', function (): void {
    $this->postJson('/api/telegram/webhook', [
        'message' => [
            'chat' => ['id' => 123456],
            'text' => 'not a valid url',
        ],
    ])->assertOk();

    Http::assertSent(fn ($request): bool => str_contains((string) $request->url(), 'api.telegram.org')
        && str_contains((string) $request['text'], 'Could not parse')
    );
});

it('replies with error for invalid slug', function (): void {
    $this->postJson('/api/telegram/webhook', [
        'message' => [
            'chat' => ['id' => 123456],
            'text' => 'https://apply.workable.com/nonexistent',
        ],
    ])->assertOk();

    Http::assertSent(fn ($request): bool => str_contains((string) $request->url(), 'api.telegram.org')
        && str_contains((string) $request['text'], 'Could not find')
    );
});

it('ignores messages from unknown chat ids', function (): void {
    $this->postJson('/api/telegram/webhook', [
        'message' => [
            'chat' => ['id' => 999999],
            'text' => 'https://apply.workable.com/patchstack',
        ],
    ])->assertOk();

    expect(Company::query()->count())->toBe(0);
    Http::assertNotSent(fn ($request): bool => str_contains((string) $request->url(), 'api.telegram.org'));
});

it('ignores webhook payloads without a message', function (): void {
    $this->postJson('/api/telegram/webhook', [
        'update_id' => 12345,
    ])->assertOk();
});
