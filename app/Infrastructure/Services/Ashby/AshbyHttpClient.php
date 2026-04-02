<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Ashby;

use App\Application\DTOs\JobPostingDTO;
use App\Infrastructure\Services\Contracts\JobBoardClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AshbyHttpClient implements JobBoardClient
{
    private const string API_BASE_URL = 'https://api.ashbyhq.com/posting-api/job-board';

    private const int TIMEOUT_SECONDS = 30;

    /**
     * @return Collection<int, JobPostingDTO>
     */
    public function fetchJobsForCompany(string $slug): Collection
    {
        try {
            $url = sprintf('%s/%s', self::API_BASE_URL, $slug);

            $response = Http::timeout(self::TIMEOUT_SECONDS)
                ->get($url);

            if (! $response->successful()) {
                Log::warning('Ashby API request failed', [
                    'company_slug' => $slug,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return collect();
            }

            $data = $response->json();

            if (! isset($data['jobs']) || ! is_array($data['jobs'])) {
                Log::warning('Ashby API response missing jobs array', [
                    'company_slug' => $slug,
                    'response' => $data,
                ]);

                return collect();
            }

            return collect($data['jobs'])->map(fn (array $job): JobPostingDTO => $this->mapToDTO($job));
        } catch (ConnectionException $e) {
            Log::error('Ashby API connection error', [
                'company_slug' => $slug,
                'error' => $e->getMessage(),
            ]);

            return collect();
        } catch (\Throwable $e) {
            Log::error('Unexpected error fetching Ashby jobs', [
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
            $url = sprintf('%s/%s', self::API_BASE_URL, $slug);

            $response = Http::timeout(15)->get($url);

            if (! $response->successful() || ! isset($response->json()['jobBoard']['title'])) {
                return null;
            }

            return $response->json()['jobBoard']['title'];
        } catch (\Throwable) {
            return null;
        }
    }

    private function mapToDTO(array $data): JobPostingDTO
    {
        $location = $data['location'] ?? null;

        if ($location && ($data['isRemote'] ?? false)) {
            $location .= ' (Remote)';
        } elseif (! $location && ($data['isRemote'] ?? false)) {
            $location = 'Remote';
        }

        return new JobPostingDTO(
            externalId: $data['id'] ?? '',
            title: $data['title'] ?? 'Untitled Position',
            location: $location,
            url: $data['jobUrl'] ?? '',
            department: $data['department'] ?? null,
            rawPayload: $data,
        );
    }
}
