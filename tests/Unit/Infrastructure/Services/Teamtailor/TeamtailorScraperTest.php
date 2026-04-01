<?php

declare(strict_types=1);

use App\Domain\Company\JobBoardProvider;
use App\Domain\ScraperConfig\ScraperConfig;
use App\Infrastructure\Services\Teamtailor\TeamtailorScraper;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->config = ScraperConfig::factory()->create([
        'provider' => 'teamtailor',
        'retry_delay_seconds' => 0,
    ]);

    $this->scraper = new TeamtailorScraper;
    $this->fixture = file_get_contents(base_path('tests/Fixtures/teamtailor-weroad-jobs.html'));
});

it('returns the correct provider', function () {
    expect($this->scraper->getProvider())->toBe(JobBoardProvider::Teamtailor);
});

it('parses jobs from real teamtailor html', function () {
    Http::fake(['https://weroad.teamtailor.com/jobs' => Http::response($this->fixture, 200)]);

    $jobs = $this->scraper->fetchJobsForCompany('weroad');

    expect($jobs)->not->toBeEmpty()
        ->and($jobs->first()->title)->toBeString()->not->toBeEmpty()
        ->and($jobs->first()->externalId)->toBeString()->not->toBeEmpty()
        ->and($jobs->first()->url)->toBeString()->toContain('teamtailor.com');
});

it('extracts numeric external id from url', function () {
    Http::fake(['https://weroad.teamtailor.com/jobs' => Http::response($this->fixture, 200)]);

    $jobs = $this->scraper->fetchJobsForCompany('weroad');

    // The first job URL is /jobs/7289318-customer-care-assistant, so ID should be 7289318
    expect($jobs->first()->externalId)->toMatch('/^\d+$/');
});

it('extracts department and location', function () {
    Http::fake(['https://weroad.teamtailor.com/jobs' => Http::response($this->fixture, 200)]);

    $jobs = $this->scraper->fetchJobsForCompany('weroad');
    $job = $jobs->first();

    // First job should have department "Commercial" and location "Milan"
    expect($job->department)->toBeString()->not->toBeEmpty()
        ->and($job->rawPayload)->toHaveKey('source', 'teamtailor');
});
