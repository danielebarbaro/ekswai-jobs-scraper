<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\SmartRecruiters;

use App\Application\DTOs\JobPostingDTO;
use App\Infrastructure\Services\Contracts\JobBoardClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmartRecruitersHttpClient implements JobBoardClient
{
    private const string API_BASE_URL = 'https://api.smartrecruiters.com/v1/companies';

    /**
     * Public job page. The list endpoint does not expose `postingUrl` (only the
     * per-posting detail endpoint does), and fetching detail for every posting
     * would mean 100+ extra requests per company per sync. The public page
     * accepts the bare posting id without the title slug, so we build it from
     * the company identifier and the posting id instead. Verified against live
     * postings (744000147566189, 744000147564650, 744000147556063 on
     * ABOUTYOUGmbH all return 200 and render the correct job; an invalid id
     * returns 404). The identifier is case-insensitive on this host.
     */
    private const string POSTING_URL_TEMPLATE = 'https://jobs.smartrecruiters.com/%s/%s';

    private const int PAGE_SIZE = 100;

    /**
     * Safety net against a totalFound that never gets reached (PAGE_SIZE * MAX_PAGES postings).
     */
    private const int MAX_PAGES = 50;

    private const int TIMEOUT_SECONDS = 30;

    /**
     * @return Collection<int, JobPostingDTO>
     */
    public function fetchJobsForCompany(string $slug): Collection
    {
        try {
            $postings = $this->fetchAllPostings($slug);

            if ($postings === null) {
                return collect();
            }

            return collect($this->dedupeByRefNumber($postings))
                ->map(fn (array $posting): JobPostingDTO => $this->mapToDTO($posting, $slug))
                ->values();
        } catch (ConnectionException $e) {
            Log::error('SmartRecruiters API connection error', [
                'company_slug' => $slug,
                'error' => $e->getMessage(),
            ]);

            return collect();
        } catch (\Throwable $e) {
            Log::error('Unexpected error fetching SmartRecruiters jobs', [
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
            $response = Http::timeout(15)->get($this->postingsUrl($slug), [
                'offset' => 0,
                'limit' => 1,
            ]);

            if (! $response->successful()) {
                return null;
            }

            $content = $response->json('content');

            // The API answers 200 with an empty result set for unknown identifiers,
            // so an empty board is the only signal we have that the slug is wrong.
            if (! is_array($content) || $content === []) {
                Log::warning('SmartRecruiters returned no postings for slug', [
                    'company_slug' => $slug,
                ]);

                return null;
            }

            $first = $content[0];
            $name = is_array($first) ? ($first['company']['name'] ?? null) : null;

            return is_string($name) && trim($name) !== '' ? trim($name) : $slug;
        } catch (\Throwable) {
            return null;
        }
    }

    public function fetchCompanyDescription(string $slug): ?string
    {
        // SmartRecruiters exposes no public company profile endpoint
        // (/v1/companies/{identifier} responds 404 for every identifier).
        return null;
    }

    /**
     * Page through the postings endpoint. Returns null when the API could not be read.
     *
     * @return list<array<string, mixed>>|null
     */
    private function fetchAllPostings(string $slug): ?array
    {
        $postings = [];
        $offset = 0;
        $page = 0;
        $total = 0;

        do {
            $response = Http::timeout(self::TIMEOUT_SECONDS)->get($this->postingsUrl($slug), [
                'offset' => $offset,
                'limit' => self::PAGE_SIZE,
            ]);

            if (! $response->successful()) {
                Log::warning('SmartRecruiters API request failed', [
                    'company_slug' => $slug,
                    'status' => $response->status(),
                    'offset' => $offset,
                ]);

                return null;
            }

            $data = $response->json();

            if (! is_array($data) || ! isset($data['content']) || ! is_array($data['content'])) {
                Log::warning('SmartRecruiters API response missing content array', [
                    'company_slug' => $slug,
                    'offset' => $offset,
                ]);

                return null;
            }

            foreach ($data['content'] as $posting) {
                if (is_array($posting)) {
                    $postings[] = $posting;
                }
            }

            if ($data['content'] === []) {
                break;
            }

            $total = (int) ($data['totalFound'] ?? 0);
            $offset += self::PAGE_SIZE;
            $page++;
        } while ($offset < $total && $page < self::MAX_PAGES);

        if ($page >= self::MAX_PAGES && $offset < $total) {
            Log::warning('SmartRecruiters pagination stopped at the page limit', [
                'company_slug' => $slug,
                'fetched' => count($postings),
                'total_found' => $total,
            ]);
        }

        return $postings;
    }

    /**
     * SmartRecruiters publishes the same job once per language, each copy with its own
     * id and uuid but a shared refNumber. Group by refNumber and keep the English copy
     * when there is one, otherwise the first one seen.
     *
     * @param  list<array<string, mixed>>  $postings
     * @return list<array<string, mixed>>
     */
    private function dedupeByRefNumber(array $postings): array
    {
        $grouped = [];
        $standalone = 0;

        foreach ($postings as $posting) {
            $refNumber = $this->refNumber($posting);

            // Without a refNumber there is nothing to group on, so the posting stands alone.
            $key = $refNumber !== null ? 'ref:'.$refNumber : 'idx:'.$standalone++;

            if (! isset($grouped[$key])) {
                $grouped[$key] = $posting;

                continue;
            }

            if (! $this->isEnglish($grouped[$key]) && $this->isEnglish($posting)) {
                $grouped[$key] = $posting;
            }
        }

        return array_values($grouped);
    }

    /**
     * @param  array<string, mixed>  $posting
     */
    private function isEnglish(array $posting): bool
    {
        $code = $posting['language']['code'] ?? null;

        return is_string($code) && str_starts_with(strtolower($code), 'en');
    }

    /**
     * @param  array<string, mixed>  $posting
     */
    private function refNumber(array $posting): ?string
    {
        $refNumber = $posting['refNumber'] ?? null;

        if (! is_string($refNumber) && ! is_int($refNumber)) {
            return null;
        }

        $refNumber = trim((string) $refNumber);

        return $refNumber !== '' ? $refNumber : null;
    }

    /**
     * @param  array<string, mixed>  $posting
     */
    private function postingId(array $posting): string
    {
        $id = $posting['id'] ?? null;

        return is_string($id) || is_int($id) ? trim((string) $id) : '';
    }

    /**
     * @param  array<string, mixed>  $posting
     */
    private function mapToDTO(array $posting, string $slug): JobPostingDTO
    {
        $id = $this->postingId($posting);
        $title = $posting['name'] ?? null;
        $title = is_string($title) ? trim($title) : '';

        $department = $posting['department']['label'] ?? null;
        $department = is_string($department) ? trim($department) : '';

        return new JobPostingDTO(
            externalId: $this->refNumber($posting) ?? $id,
            title: $title !== '' ? $title : 'Untitled Position',
            location: $this->resolveLocation($posting),
            url: $this->resolveUrl($posting, $slug, $id),
            department: $department !== '' ? $department : null,
            rawPayload: $posting,
        );
    }

    /**
     * @param  array<string, mixed>  $posting
     */
    private function resolveLocation(array $posting): ?string
    {
        $location = $posting['location'] ?? null;

        if (! is_array($location)) {
            return null;
        }

        $full = $location['fullLocation'] ?? null;

        if (is_string($full) && trim($full) !== '') {
            return trim($full);
        }

        $parts = [];

        foreach (['city', 'region', 'country'] as $key) {
            $value = $location[$key] ?? null;

            if (is_string($value) && trim($value) !== '') {
                $parts[] = trim($value);
            }
        }

        return $parts === [] ? null : implode(', ', $parts);
    }

    /**
     * @param  array<string, mixed>  $posting
     */
    private function resolveUrl(array $posting, string $slug, string $id): string
    {
        $postingUrl = $posting['postingUrl'] ?? null;

        if (is_string($postingUrl) && trim($postingUrl) !== '') {
            return trim($postingUrl);
        }

        if ($id === '') {
            return '';
        }

        return sprintf(self::POSTING_URL_TEMPLATE, $slug, $id);
    }

    private function postingsUrl(string $slug): string
    {
        return sprintf('%s/%s/postings', self::API_BASE_URL, $slug);
    }
}
