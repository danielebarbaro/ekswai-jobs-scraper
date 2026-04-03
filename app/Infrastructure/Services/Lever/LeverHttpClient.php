<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Lever;

use App\Application\DTOs\JobPostingDTO;
use App\Infrastructure\Services\Contracts\JobBoardClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;

class LeverHttpClient implements JobBoardClient
{
    private const string API_BASE_URL = 'https://api.lever.co/v0/postings';

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
                Log::warning('Lever API request failed', [
                    'company_slug' => $slug,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return collect();
            }

            $data = $response->json();

            if (! is_array($data) || $data === [] || ! array_is_list($data)) {
                return collect();
            }

            return collect($data)->map(fn (array $job): JobPostingDTO => $this->mapToDTO($job));
        } catch (ConnectionException $e) {
            Log::error('Lever API connection error', [
                'company_slug' => $slug,
                'error' => $e->getMessage(),
            ]);

            return collect();
        } catch (\Throwable $e) {
            Log::error('Unexpected error fetching Lever jobs', [
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
            $response = Http::timeout(15)->get("https://jobs.lever.co/{$slug}");

            if (! $response->successful()) {
                return null;
            }

            $crawler = new Crawler($response->body());
            $meta = $crawler->filter('meta[name="description"]');

            if ($meta->count() === 0) {
                return null;
            }

            $content = trim($meta->first()->attr('content') ?? '');

            return $content !== '' ? $content : null;
        } catch (\Throwable) {
            return null;
        }
    }

    public function validateSlug(string $slug): ?string
    {
        try {
            $url = sprintf('%s/%s', self::API_BASE_URL, $slug);

            $response = Http::timeout(15)->get($url);

            if (! $response->successful()) {
                return null;
            }

            $data = $response->json();

            if (! is_array($data) || $data === [] || ! array_is_list($data)) {
                return null;
            }

            return str($slug)->replace('-', ' ')->title()->toString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function mapToDTO(array $data): JobPostingDTO
    {
        return new JobPostingDTO(
            externalId: $data['id'] ?? '',
            title: $data['text'] ?? 'Untitled Position',
            location: $data['categories']['location'] ?? null,
            url: $data['hostedUrl'] ?? '',
            department: $data['categories']['department'] ?? null,
            rawPayload: $data,
        );
    }
}
