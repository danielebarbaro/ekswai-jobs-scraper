<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers;

use App\Application\Services\JobBoardUrlParser;
use App\Domain\Company\Company;
use App\Infrastructure\Services\JobBoardClientFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TelegramWebhookController extends Controller
{
    public function __construct(
        private readonly JobBoardUrlParser $urlParser,
        private readonly JobBoardClientFactory $clientFactory,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $message = $request->input('message');

        if ($message === null) {
            return response()->json(['ok' => true]);
        }

        $chatId = $message['chat']['id'] ?? null;
        $text = trim($message['text'] ?? '');

        if ((string) $chatId !== config('services.telegram.admin_chat_id')) {
            return response()->json(['ok' => true]);
        }

        if ($text === '') {
            return response()->json(['ok' => true]);
        }

        $reply = $this->processUrl($text);
        $this->sendReply($chatId, $reply);

        return response()->json(['ok' => true]);
    }

    private function processUrl(string $text): string
    {
        $parsed = $this->urlParser->parse($text);

        if ($parsed === null) {
            return 'Could not parse URL. Supported providers: Workable, Lever, Ashby, Greenhouse, Teamtailor, Factorial, Personio.';
        }

        $provider = $parsed['provider'];
        $slug = $parsed['slug'];

        $existing = Company::query()
            ->where('provider', $provider)
            ->where('provider_slug', $slug)
            ->first();

        if ($existing) {
            return "Already exists: {$existing->name} ({$provider->value})";
        }

        $client = $this->clientFactory->make($provider);
        $companyName = $client->validateSlug($slug);

        if ($companyName === null) {
            return "Could not find company '{$slug}' on {$provider->value}.";
        }

        Company::query()->create([
            'name' => $companyName,
            'provider' => $provider,
            'provider_slug' => $slug,
            'is_active' => true,
        ]);

        return "Added: {$companyName} ({$provider->value})";
    }

    private function sendReply(int $chatId, string $text): void
    {
        $token = config('services.telegram.bot_token');

        Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $text,
        ]);
    }
}
