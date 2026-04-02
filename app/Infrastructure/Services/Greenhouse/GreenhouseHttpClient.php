<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Greenhouse;

use App\Application\DTOs\JobPostingDTO;
use App\Infrastructure\Services\Contracts\JobBoardClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GreenhouseHttpClient implements JobBoardClient
{
    private const string API_BASE_URL = 'https://boards-api.greenhouse.io/v1/boards';

    private const int TIMEOUT_SECONDS = 30;

    /**
     * @return Collection<int, JobPostingDTO>
     */
    public function fetchJobsForCompany(string $slug): Collection
    {
        try {
            $url = sprintf('%s/%s/jobs', self::API_BASE_URL, $slug);

            $response = Http::timeout(self::TIMEOUT_SECONDS)
                ->get($url);

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

            return collect($data['jobs'])->map(fn (array $job) => $this->mapToDTO($job));
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

    public function validateSlug(string $slug): ?string
    {
        try {
            $url = sprintf('%s/%s/jobs', self::API_BASE_URL, $slug);

            $response = Http::timeout(15)->get($url);

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
