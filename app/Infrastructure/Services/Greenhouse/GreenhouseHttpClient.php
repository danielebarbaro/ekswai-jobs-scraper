<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Greenhouse;

use App\Application\DTOs\JobPostingDTO;
use App\Infrastructure\Services\Contracts\JobBoardClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GreenhouseHttpClient implements JobBoardClient
{
    private const string API_BASE_URL = 'https://boards-api.greenhouse.io/v1/boards';

    private const string API_BASE_URL_EU = 'https://boards-api.eu.greenhouse.io/v1/boards';

    private const int TIMEOUT_SECONDS = 30;

    /**
     * @return Collection<int, JobPostingDTO>
     */
    public function fetchJobsForCompany(string $slug): Collection
    {
        try {
            $response = $this->requestWithEuFallback($slug, self::TIMEOUT_SECONDS);

            if (! $response->successful()) {
                Log::warning('Greenhouse API request failed', [
                    'company_slug' => $slug,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return collect();
            }

            $data = $response->json();

            if (! isset($data['jobs']) || ! is_array($data['jobs'])) {
                Log::warning('Greenhouse API response missing jobs array', [
                    'company_slug' => $slug,
                    'response' => $data,
                ]);

                return collect();
            }

            return collect($data['jobs'])->map(fn (array $job): JobPostingDTO => $this->mapToDTO($job));
        } catch (ConnectionException $e) {
            Log::error('Greenhouse API connection error', [
                'company_slug' => $slug,
                'error' => $e->getMessage(),
            ]);

            return collect();
        } catch (\Throwable $e) {
            Log::error('Unexpected error fetching Greenhouse jobs', [
                'company_slug' => $slug,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return collect();
        }
    }

    public function fetchCompanyDescription(string $slug): ?string
    {
        try {
            $url = sprintf('%s/%s', self::API_BASE_URL, $slug);
            $response = Http::timeout(15)->get($url);

            if (! $response->successful()) {
                return null;
            }

            $content = $response->json('content');

            if (! is_string($content) || $content === '') {
                return null;
            }

            return trim(strip_tags($content));
        } catch (\Throwable) {
            return null;
        }
    }

    public function validateSlug(string $slug): ?string
    {
        try {
            $response = $this->requestWithEuFallback($slug, 15);

            if (! $response->successful() || ! isset($response->json()['jobs'])) {
                return null;
            }

            $jobs = $response->json()['jobs'];

            if (is_array($jobs) && $jobs !== [] && isset($jobs[0]['company_name'])) {
                return $jobs[0]['company_name'];
            }

            return $slug;
        } catch (\Throwable) {
            return null;
        }
    }

    private function requestWithEuFallback(string $slug, int $timeout): Response
    {
        $url = sprintf('%s/%s/jobs', self::API_BASE_URL, $slug);
        $response = Http::timeout($timeout)->get($url);

        if ($response->successful()) {
            return $response;
        }

        $euUrl = sprintf('%s/%s/jobs', self::API_BASE_URL_EU, $slug);

        return Http::timeout($timeout)->get($euUrl);
    }

    private function mapToDTO(array $data): JobPostingDTO
    {
        return new JobPostingDTO(
            externalId: (string) ($data['id'] ?? ''),
            title: $data['title'] ?? 'Untitled Position',
            location: $data['location']['name'] ?? null,
            url: $data['absolute_url'] ?? '',
            department: null,
            rawPayload: $data,
        );
    }
}
