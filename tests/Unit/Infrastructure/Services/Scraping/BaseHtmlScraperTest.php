<?php

declare(strict_types=1);

use App\Application\DTOs\JobPostingDTO;
use App\Domain\Company\JobBoardProvider;
use App\Domain\ScraperConfig\ScraperConfig;
use App\Infrastructure\Services\Scraping\BaseHtmlScraper;
use App\Infrastructure\Services\Scraping\Exceptions\DomStructureChangedException;
use App\Infrastructure\Services\Scraping\Exceptions\ScrapingFailedException;
use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

class FakeHtmlScraper extends BaseHtmlScraper
{
    public function getProvider(): JobBoardProvider
    {
        return JobBoardProvider::Teamtailor;
    }

    protected function mapJobElement(Crawler $node): JobPostingDTO
    {
        return new JobPostingDTO(
            externalId: $node->attr('data-id') ?? 'unknown',
            title: $node->filter('.title')->count() ? $node->filter('.title')->text() : 'Untitled',
            location: $node->filter('.location')->count() ? $node->filter('.location')->text() : null,
            url: $node->filter('a')->count() ? $node->filter('a')->attr('href') ?? '' : '',
            department: null,
            rawPayload: [],
        );
    }
}

beforeEach(function () {
    ScraperConfig::factory()->create([
        'provider' => 'teamtailor',
        'retry_delay_seconds' => 0,
        'retry_attempts' => 2,
        'base_url_pattern' => 'https://{slug}.example.com/jobs',
        'health_check_selector' => 'div.job-item',
        'selectors' => ['job_list' => 'div.job-item'],
        'is_active' => true,
    ]);
});

it('fetches and parses jobs from valid html', function () {
    $html = <<<'HTML'
    <html><body>
        <div class="job-item" data-id="job-1">
            <span class="title">Engineer</span>
            <span class="location">Remote</span>
            <a href="https://example.com/jobs/1">Apply</a>
        </div>
        <div class="job-item" data-id="job-2">
            <span class="title">Designer</span>
            <span class="location">Berlin</span>
            <a href="https://example.com/jobs/2">Apply</a>
        </div>
    </body></html>
    HTML;

    Http::fake([
        'https://acme.example.com/jobs' => Http::response($html, 200),
    ]);

    $scraper = new FakeHtmlScraper;
    $jobs = $scraper->fetchJobsForCompany('acme');

    expect($jobs)->toHaveCount(2);
    expect($jobs[0])->toBeInstanceOf(JobPostingDTO::class);
    expect($jobs[0]->externalId)->toBe('job-1');
    expect($jobs[0]->title)->toBe('Engineer');
    expect($jobs[0]->location)->toBe('Remote');
    expect($jobs[1]->externalId)->toBe('job-2');
    expect($jobs[1]->title)->toBe('Designer');
    expect($jobs[1]->location)->toBe('Berlin');
});

it('throws DomStructureChangedException when health check selector not found', function () {
    $html = '<html><body><p>No jobs here</p></body></html>';

    Http::fake([
        'https://acme.example.com/jobs' => Http::response($html, 200),
    ]);

    $scraper = new FakeHtmlScraper;
    $scraper->fetchJobsForCompany('acme');
})->throws(DomStructureChangedException::class);

it('throws ScrapingFailedException after retries on http error', function () {
    Http::fake([
        'https://acme.example.com/jobs' => Http::response('Server Error', 500),
    ]);

    $scraper = new FakeHtmlScraper;
    $scraper->fetchJobsForCompany('acme');
})->throws(ScrapingFailedException::class);

it('retries the configured number of times before failing', function () {
    Http::fake([
        'https://acme.example.com/jobs' => Http::response('Server Error', 500),
    ]);

    $scraper = new FakeHtmlScraper;

    try {
        $scraper->fetchJobsForCompany('acme');
    } catch (ScrapingFailedException) {
        // Expected
    }

    Http::assertSentCount(3); // 1 initial + 2 retries
});

it('returns collection when job elements found but page is valid', function () {
    $html = <<<'HTML'
    <html><body>
        <div class="job-item" data-id="job-1">
            <span class="title">Solo Role</span>
            <a href="https://example.com/jobs/1">Apply</a>
        </div>
    </body></html>
    HTML;

    Http::fake([
        'https://acme.example.com/jobs' => Http::response($html, 200),
    ]);

    $scraper = new FakeHtmlScraper;
    $jobs = $scraper->fetchJobsForCompany('acme');

    expect($jobs)->toHaveCount(1);
    expect($jobs[0]->title)->toBe('Solo Role');
});

it('validates slug by checking page loads with health check selector', function () {
    $html = '<html><body><div class="job-item">Job</div></body></html>';

    Http::fake([
        'https://valid-co.example.com/jobs' => Http::response($html, 200),
    ]);

    $scraper = new FakeHtmlScraper;
    $result = $scraper->validateSlug('valid-co');

    expect($result)->toBe('valid-co');
});

it('returns null for invalid slug', function () {
    Http::fake([
        'https://invalid-co.example.com/jobs' => Http::response('Not Found', 404),
    ]);

    $scraper = new FakeHtmlScraper;
    $result = $scraper->validateSlug('invalid-co');

    expect($result)->toBeNull();
});

it('builds url from base pattern and slug', function () {
    $html = '<html><body><div class="job-item" data-id="1"><span class="title">Job</span><a href="/j">Link</a></div></body></html>';

    Http::fake([
        'https://my-company.example.com/jobs' => Http::response($html, 200),
    ]);

    $scraper = new FakeHtmlScraper;
    $scraper->fetchJobsForCompany('my-company');

    Http::assertSent(function ($request) {
        return $request->url() === 'https://my-company.example.com/jobs';
    });
});
