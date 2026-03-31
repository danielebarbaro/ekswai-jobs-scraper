<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Workable;

use App\Application\DTOs\WorkableJobDTO;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WorkableHttpClient
{
    private const API_BASE_URL = 'https://apply.workable.com/api/v1/widget/accounts';

    private const TIMEOUT_SECONDS = 30;

    /**
     * Fetch all job postings for a given Workable company slug.
     *
     * @return Collection<int, WorkableJobDTO>
     */
    public function fetchJobsForCompany(string $companySlug): Collection
    {
        try {
            $url = sprintf('%s/%s', self::API_BASE_URL, $companySlug);

            $response = Http::timeout(self::TIMEOUT_SECONDS)
                ->get($url);

            if (! $response->successful()) {
                Log::warning('Workable API request failed', [
                    'company_slug' => $companySlug,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return collect();
            }

            $data = $response->json();

            if (! isset($data['jobs']) || ! is_array($data['jobs'])) {
                Log::warning('Workable API response missing jobs array', [
                    'company_slug' => $companySlug,
                    'response' => $data,
                ]);

                return collect();
            }

            return collect($data['jobs'])->map(fn (array $job) => WorkableJobDTO::fromApiResponse($job));
        } catch (ConnectionException $e) {
            Log::error('Workable API connection error', [
                'company_slug' => $companySlug,
                'error' => $e->getMessage(),
            ]);

            return collect();
        } catch (\Throwable $e) {
            Log::error('Unexpected error fetching Workable jobs', [
                'company_slug' => $companySlug,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return collect();
        }
    }
}
