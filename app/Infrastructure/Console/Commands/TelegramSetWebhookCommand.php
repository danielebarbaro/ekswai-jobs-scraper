<?php

declare(strict_types=1);

namespace App\Infrastructure\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TelegramSetWebhookCommand extends Command
{
    protected $signature = 'telegram:set-webhook {url : The public webhook URL}';

    protected $description = 'Register the Telegram bot webhook URL';

    public function handle(): int
    {
        $url = $this->argument('url');
        $token = config('services.telegram.bot_token');

        $this->info("Setting webhook to: {$url}");

        $response = Http::post("https://api.telegram.org/bot{$token}/setWebhook", [
            'url' => $url,
        ]);

        if (! $response->successful() || ! ($response->json('ok') ?? false)) {
            $this->error('Failed: '.($response->json('description') ?? 'Unknown error'));

            return self::FAILURE;
        }

        $this->info('Webhook registered successfully.');

        return self::SUCCESS;
    }
}
