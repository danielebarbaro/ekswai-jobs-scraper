<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Lever;

use App\Application\DTOs\JobPostingDTO;
use App\Infrastructure\Services\Contracts\JobBoardClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;

class LeverHttpClient implements JobBoardClient
{
    /** @var string[] */
    private const array API_BASE_URLS = [
        'https://api.lever.co/v0/postings',
        'https://api.eu.lever.co/v0/postings',
    ];

    private const int TIMEOUT_SECONDS = 30;

    /**
     * @return Collection<int, JobPostingDTO>
     */
    public function fetchJobsForCompany(string $slug): Collection
    {
        try {
            $response = $this->requestFromAnyRegion($slug, self::TIMEOUT_SECONDS);

            if ($response === null) {
                Log::warning('Lever API request failed on all regions', [
                    'company_slug' => $slug,
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
            $response = $this->requestFromAnyRegion($slug, 15);

            if ($response === null) {
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

    private function requestFromAnyRegion(string $slug, int $timeout): ?Response
    {
        foreach (self::API_BASE_URLS as $baseUrl) {
            $response = Http::timeout($timeout)->get(sprintf('%s/%s', $baseUrl, $slug));

            if ($response->successful()) {
                return $response;
            }
        }

        return null;
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
