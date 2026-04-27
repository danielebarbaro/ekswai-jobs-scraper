<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Personio;

use App\Application\DTOs\JobPostingDTO;
use App\Infrastructure\Services\Contracts\JobBoardClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use SimpleXMLElement;

class PersonioHttpClient implements JobBoardClient
{
    private const string BASE_URL_TEMPLATE = 'https://%s.jobs.personio.de/xml';

    private const int TIMEOUT_SECONDS = 30;

    /**
     * @return Collection<int, JobPostingDTO>
     */
    public function fetchJobsForCompany(string $slug): Collection
    {
        $xml = $this->fetchXml($slug, withLanguageParam: true);

        if ($xml === null) {
            return collect();
        }

        $positions = $xml->xpath('//position') ?: [];

        if ($positions === []) {
            $retry = $this->fetchXml($slug, withLanguageParam: false);
            $positions = $retry?->xpath('//position') ?: [];
        }

        return collect($positions)->map(
            fn (SimpleXMLElement $position): JobPostingDTO => $this->mapToDTO($position, $slug)
        )->values();
    }

    public function validateSlug(string $slug): ?string
    {
        $xml = $this->fetchXml($slug, withLanguageParam: true, timeout: 15);

        if ($xml === null) {
            return null;
        }

        $first = $xml->xpath('//position[1]')[0] ?? null;
        $subcompany = $first !== null ? trim((string) $first->subcompany) : '';

        return $subcompany !== '' ? $subcompany : $slug;
    }

    public function fetchCompanyDescription(string $slug): ?string
    {
        return null;
    }

    private function fetchXml(string $slug, bool $withLanguageParam, int $timeout = self::TIMEOUT_SECONDS): ?SimpleXMLElement
    {
        try {
            $url = sprintf(self::BASE_URL_TEMPLATE, $slug);
            $response = Http::timeout($timeout)->get($url, $withLanguageParam ? ['language' => 'en'] : []);

            if (! $response->successful()) {
                Log::warning('Personio XML request failed', [
                    'company_slug' => $slug,
                    'status' => $response->status(),
                ]);

                return null;
            }

            $body = trim($response->body());
            if ($body === '') {
                return null;
            }

            $previousErrors = libxml_use_internal_errors(true);
            $xml = simplexml_load_string($body);
            libxml_clear_errors();
            libxml_use_internal_errors($previousErrors);

            if ($xml === false) {
                Log::warning('Personio XML parse failed', ['company_slug' => $slug]);

                return null;
            }

            return $xml;
        } catch (ConnectionException $e) {
            Log::error('Personio connection error', [
                'company_slug' => $slug,
                'error' => $e->getMessage(),
            ]);

            return null;
        } catch (\Throwable $e) {
            Log::error('Unexpected error fetching Personio XML', [
                'company_slug' => $slug,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function mapToDTO(SimpleXMLElement $position, string $slug): JobPostingDTO
    {
        $id = trim((string) $position->id);
        $title = trim((string) $position->name);
        $office = trim((string) $position->office);
        $department = trim((string) $position->department);

        $url = sprintf('https://%s.jobs.personio.de/job/%s?language=en', $slug, $id);

        /** @var array<string, mixed> $raw */
        $raw = json_decode((string) json_encode($position), true) ?: [];

        return new JobPostingDTO(
            externalId: $id,
            title: $title !== '' ? $title : 'Untitled Position',
            location: $office !== '' ? $office : null,
            url: $url,
            department: $department !== '' ? $department : null,
            rawPayload: $raw,
        );
    }
}
