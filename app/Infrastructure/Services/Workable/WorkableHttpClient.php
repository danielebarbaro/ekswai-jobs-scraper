<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Workable;

use App\Application\DTOs\JobPostingDTO;
use App\Infrastructure\Services\Contracts\JobBoardClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WorkableHttpClient implements JobBoardClient
{
    private const string API_BASE_URL = 'https://apply.workable.com/api/v1/widget/accounts';

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
                Log::warning('Workable API request failed', [
                    'company_slug' => $slug,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return collect();
            }

            $data = $response->json();

            if (! isset($data['jobs']) || ! is_array($data['jobs'])) {
                Log::warning('Workable API response missing jobs array', [
                    'company_slug' => $slug,
                    'response' => $data,
                ]);

                return collect();
            }

            return collect($data['jobs'])->map(fn (array $job): JobPostingDTO => $this->mapToDTO($job));
        } catch (ConnectionException $e) {
            Log::error('Workable API connection error', [
                'company_slug' => $slug,
                'error' => $e->getMessage(),
            ]);

            return collect();
        } catch (\Throwable $e) {
            Log::error('Unexpected error fetching Workable jobs', [
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

            if (! $response->successful() || ! isset($response->json()['name'])) {
                return null;
            }

            return $response->json()['name'];
        } catch (\Throwable) {
            return null;
        }
    }

    private function mapToDTO(array $data): JobPostingDTO
    {
        $location = collect([
            $data['city'] ?? null,
            $data['country'] ?? null,
        ])->filter()->implode(', ') ?: null;

        return new JobPostingDTO(
            externalId: $data['shortcode'] ?? (string) ($data['id'] ?? ''),
            title: $data['title'] ?? 'Untitled Position',
            location: $location,
            url: $data['url'] ?? $data['shortlink'] ?? '',
            department: $data['department'] ?? null,
            rawPayload: $data,
        );
    }
}
