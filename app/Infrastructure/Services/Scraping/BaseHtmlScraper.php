<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Scraping;

use App\Application\DTOs\JobPostingDTO;
use App\Domain\Company\JobBoardProvider;
use App\Domain\ScraperConfig\ScraperConfig;
use App\Infrastructure\Services\Contracts\JobBoardClient;
use App\Infrastructure\Services\Scraping\Exceptions\DomStructureChangedException;
use App\Infrastructure\Services\Scraping\Exceptions\ScrapingFailedException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;

abstract class BaseHtmlScraper implements JobBoardClient
{
    private const TIMEOUT_SECONDS = 30;

    abstract public function getProvider(): JobBoardProvider;

    abstract protected function mapJobElement(Crawler $node): JobPostingDTO;

    public function fetchJobsForCompany(string $slug): Collection
    {
        $config = $this->getConfig();
        $url = $this->buildUrl($config->base_url_pattern, $slug);
        $maxAttempts = $config->retry_attempts + 1;
        $lastException = null;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $html = $this->fetchHtml($url);
                $crawler = new Crawler($html);

                $this->validateDomStructure($crawler, $config, $slug, $attempt);

                $jobListSelector = $config->selectors['job_list'];

                return collect($crawler->filter($jobListSelector)->each(
                    fn (Crawler $node) => $this->mapJobElement($node)
                ));
            } catch (DomStructureChangedException $e) {
                $lastException = $e;
            } catch (ScrapingFailedException $e) {
                $lastException = $e;
            } catch (\Throwable $e) {
                $lastException = new ScrapingFailedException(
                    provider: $this->getProvider(),
                    slug: $slug,
                    attemptsMade: $attempt,
                    previous: $e,
                );
            }

            if ($attempt < $maxAttempts) {
                $delay = $config->retry_delay_seconds * (2 ** ($attempt - 1));
                Log::warning('Scraping attempt failed, retrying', [
                    'provider' => $this->getProvider()->value,
                    'slug' => $slug,
                    'attempt' => $attempt,
                    'delay_seconds' => $delay,
                ]);
                sleep($delay);
            }
        }

        throw $lastException;
    }

    public function validateSlug(string $slug): ?string
    {
        try {
            $config = $this->getConfig();
            $url = $this->buildUrl($config->base_url_pattern, $slug);
            $html = $this->fetchHtml($url);
            $crawler = new Crawler($html);

            if ($crawler->filter($config->health_check_selector)->count() === 0) {
                return null;
            }

            return $slug;
        } catch (\Throwable) {
            return null;
        }
    }

    protected function getConfigSelectors(): array
    {
        return $this->getConfig()->selectors;
    }

    protected function getConfig(): ScraperConfig
    {
        return ScraperConfig::query()
            ->where('provider', $this->getProvider()->value)
            ->where('is_active', true)
            ->firstOrFail();
    }

    private function buildUrl(string $pattern, string $slug): string
    {
        return str_replace('{slug}', $slug, $pattern);
    }

    private function fetchHtml(string $url): string
    {
        $response = Http::timeout(self::TIMEOUT_SECONDS)->get($url);

        if (! $response->successful()) {
            throw new \RuntimeException(
                sprintf('HTTP %d from %s', $response->status(), $url)
            );
        }

        return $response->body();
    }

    private function validateDomStructure(
        Crawler $crawler,
        ScraperConfig $config,
        string $slug,
        int $attempt,
    ): void {
        if ($crawler->filter($config->health_check_selector)->count() === 0) {
            throw new DomStructureChangedException(
                provider: $this->getProvider(),
                slug: $slug,
                attemptsMade: $attempt,
                expectedSelector: $config->health_check_selector,
                actualHtmlSnippet: mb_substr($crawler->html(), 0, 500),
            );
        }
    }
}
