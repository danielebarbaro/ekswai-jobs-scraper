<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Scraping;

use App\Domain\Company\Company;
use App\Domain\ScraperConfig\ScraperConfig;
use App\Domain\User\User;
use App\Infrastructure\Mail\ScraperHealthAlertMail;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\DomCrawler\Crawler;

class ScraperHealthChecker
{
    public function checkAll(): Collection
    {
        $configs = ScraperConfig::query()
            ->where('is_active', true)
            ->get();

        $results = collect();
        $failures = collect();

        foreach ($configs as $config) {
            $company = Company::query()
                ->where('provider', $config->provider->value)
                ->where('is_active', true)
                ->first();

            if ($company === null) {
                continue;
            }

            $result = $this->checkProvider($config, $company->provider_slug);
            $results->push($result);

            if (! $result['passed']) {
                $failures->push($result);
            }
        }

        if ($failures->isNotEmpty()) {
            $this->sendAdminAlerts($failures);
        }

        return $results;
    }

    private function checkProvider(ScraperConfig $config, string $slug): array
    {
        $url = str_replace('{slug}', $slug, $config->base_url_pattern);

        try {
            $response = Http::timeout(30)->get($url);

            if (! $response->successful()) {
                $this->markFailed($config);

                return [
                    'provider' => $config->provider->value,
                    'passed' => false,
                    'url' => $url,
                    'selector' => $config->health_check_selector,
                    'error' => sprintf('HTTP %d', $response->status()),
                ];
            }

            $crawler = new Crawler($response->body());
            $found = $crawler->filter($config->health_check_selector)->count() > 0;

            if ($found) {
                $this->markPassed($config);

                return ['provider' => $config->provider->value, 'passed' => true];
            }

            $this->markFailed($config);

            return [
                'provider' => $config->provider->value,
                'passed' => false,
                'url' => $url,
                'selector' => $config->health_check_selector,
                'error' => 'Health check selector not found in DOM',
            ];
        } catch (\Throwable $e) {
            $this->markFailed($config);

            return [
                'provider' => $config->provider->value,
                'passed' => false,
                'url' => $url,
                'selector' => $config->health_check_selector,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function markPassed(ScraperConfig $config): void
    {
        $config->update([
            'last_health_check_at' => now(),
            'last_health_check_passed' => true,
        ]);
    }

    private function markFailed(ScraperConfig $config): void
    {
        $config->update([
            'last_health_check_at' => now(),
            'last_health_check_passed' => false,
        ]);

        Log::error('Scraper health check failed', [
            'provider' => $config->provider->value,
            'selector' => $config->health_check_selector,
        ]);
    }

    private function sendAdminAlerts(Collection $failures): void
    {
        $admins = User::query()->where('is_admin', true)->get();

        foreach ($admins as $admin) {
            Mail::to($admin->email)
                ->queue(
                    new ScraperHealthAlertMail($failures)->onQueue('emails')
                );
        }
    }
}
