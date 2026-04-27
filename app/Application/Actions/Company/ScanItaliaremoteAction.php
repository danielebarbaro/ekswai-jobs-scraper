<?php

declare(strict_types=1);

namespace App\Application\Actions\Company;

use App\Application\Actions\JobPosting\SyncCompanyJobPostingsAction;
use App\Application\DTOs\ScanItaliaremoteSummary;
use App\Application\Services\JobBoardUrlParser;
use App\Domain\Company\Company;
use App\Infrastructure\Services\JobBoardClientFactory;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ScanItaliaremoteAction
{
    private const string SOURCE_URL = 'https://raw.githubusercontent.com/italiaremote/awesome-italia-remote/refs/heads/main/outputs.json';

    private const int TIMEOUT_SECONDS = 30;

    public function __construct(
        private readonly JobBoardUrlParser $urlParser,
        private readonly JobBoardClientFactory $clientFactory,
        private readonly SyncCompanyJobPostingsAction $syncAction,
    ) {}

    public function execute(): ScanItaliaremoteSummary
    {
        $entries = $this->fetchEntries();

        if ($entries === null) {
            Log::error('italiaremote:scan could not fetch source JSON', ['url' => self::SOURCE_URL]);

            return new ScanItaliaremoteSummary(
                total: 0,
                matched: 0,
                created: 0,
                skipped: 0,
                failed: 0,
            );
        }

        $total = count($entries);
        $matched = 0;
        $created = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($entries as $entry) {
            $careerUrl = $entry['career_page_url'] ?? null;
            if (! is_string($careerUrl)) {
                continue;
            }
            if ($careerUrl === '') {
                continue;
            }

            $parsed = $this->urlParser->parse($careerUrl);

            if ($parsed === null) {
                continue;
            }

            $matched++;
            $provider = $parsed['provider'];
            $slug = $parsed['slug'];

            $existing = Company::query()
                ->where('provider', $provider->value)
                ->where('provider_slug', $slug)
                ->first();

            if ($existing !== null) {
                $skipped++;

                continue;
            }

            try {
                $client = $this->clientFactory->make($provider);
                $detectedName = $client->validateSlug($slug);

                if ($detectedName === null) {
                    Log::warning('italiaremote:scan slug validation failed', [
                        'provider' => $provider->value,
                        'slug' => $slug,
                        'career_page_url' => $careerUrl,
                    ]);
                    $failed++;

                    continue;
                }

                $description = $client->fetchCompanyDescription($slug);

                $company = Company::query()->create([
                    'provider' => $provider->value,
                    'provider_slug' => $slug,
                    'name' => $detectedName,
                    'description' => $description,
                    'is_active' => true,
                ]);

                $created++;

                Log::info('italiaremote:scan created company', [
                    'provider' => $provider->value,
                    'slug' => $slug,
                    'name' => $detectedName,
                ]);

                try {
                    $this->syncAction->execute($company);
                } catch (\Throwable $e) {
                    Log::warning('italiaremote:scan immediate sync failed', [
                        'provider' => $provider->value,
                        'slug' => $slug,
                        'error' => $e->getMessage(),
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning('italiaremote:scan entry failed', [
                    'provider' => $provider->value,
                    'slug' => $slug,
                    'error' => $e->getMessage(),
                ]);
                $failed++;
            }
        }

        $summary = new ScanItaliaremoteSummary(
            total: $total,
            matched: $matched,
            created: $created,
            skipped: $skipped,
            failed: $failed,
        );

        Log::info('italiaremote:scan completed', (array) $summary);

        return $summary;
    }

    /**
     * @return array<int, array<string, mixed>>|null
     */
    private function fetchEntries(): ?array
    {
        try {
            $response = Http::timeout(self::TIMEOUT_SECONDS)->get(self::SOURCE_URL);

            if (! $response->successful()) {
                Log::warning('italiaremote:scan upstream returned non-200', [
                    'status' => $response->status(),
                ]);

                return null;
            }

            $data = $response->json();

            if (! is_array($data)) {
                return null;
            }

            return $data;
        } catch (\Throwable $e) {
            Log::error('italiaremote:scan HTTP error', ['error' => $e->getMessage()]);

            return null;
        }
    }
}
