<?php

declare(strict_types=1);

use App\Domain\Company\JobBoardProvider;
use App\Domain\ScraperConfig\ScraperConfig;
use App\Infrastructure\Services\Factorial\FactorialScraper;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    $this->config = ScraperConfig::factory()->factorial()->create([
        'retry_delay_seconds' => 0,
    ]);

    $this->scraper = new FactorialScraper;
    $this->fixture = file_get_contents(base_path('tests/Fixtures/factorial-shippypro-jobs.html'));
});

it('returns the correct provider', function (): void {
    expect($this->scraper->getProvider())->toBe(JobBoardProvider::Factorial);
});

it('parses jobs from real factorial html', function (): void {
    Http::fake(['https://shippypro.factorialhr.com/' => Http::response($this->fixture, 200)]);

    $jobs = $this->scraper->fetchJobsForCompany('shippypro');

    expect($jobs)->not->toBeEmpty()
        ->and($jobs->first()->title)->toBeString()->not->toBeEmpty()
        ->and($jobs->first()->externalId)->toBeString()->not->toBeEmpty()
        ->and($jobs->first()->url)->toBeString()->toContain('factorialhr.com');
});

it('extracts numeric external id from url', function (): void {
    Http::fake(['https://shippypro.factorialhr.com/' => Http::response($this->fixture, 200)]);

    $jobs = $this->scraper->fetchJobsForCompany('shippypro');

    // URL ends with -290373, so ID should be numeric
    expect($jobs->first()->externalId)->toMatch('/^\d+$/');
});

it('extracts data attributes into raw payload', function (): void {
    Http::fake(['https://shippypro.factorialhr.com/' => Http::response($this->fixture, 200)]);

    $jobs = $this->scraper->fetchJobsForCompany('shippypro');

    expect($jobs->first()->rawPayload)
        ->toBeArray()
        ->toHaveKey('source', 'factorial')
        ->toHaveKey('contract_type')
        ->toHaveKey('is_remote')
        ->toHaveKey('location_id')
        ->toHaveKey('team_id');
});

it('extracts department', function (): void {
    Http::fake(['https://shippypro.factorialhr.com/' => Http::response($this->fixture, 200)]);

    $jobs = $this->scraper->fetchJobsForCompany('shippypro');

    $withDept = $jobs->filter(fn ($job): bool => $job->department !== null);
    expect($withDept)->not->toBeEmpty();
});
