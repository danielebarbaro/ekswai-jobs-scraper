<?php

declare(strict_types=1);

use App\Application\DTOs\JobPostingDTO;
use App\Application\Services\JobFilterService;
use App\Domain\Company\Company;
use App\Domain\JobFilter\JobFilter;
use App\Domain\User\User;

beforeEach(function (): void {
    $this->service = app(JobFilterService::class);
});

// --- Filter resolution ---

it('returns company-specific filter when it exists', function (): void {
    $user = User::factory()->create();
    $company = Company::factory()->create();
    JobFilter::factory()->create(['user_id' => $user->id, 'company_id' => null, 'title_exclude' => ['VP']]);
    $companyFilter = JobFilter::factory()->create(['user_id' => $user->id, 'company_id' => $company->id, 'title_exclude' => ['Director']]);

    $result = $this->service->getEffectiveFilter($user, $company);

    expect($result->id)->toBe($companyFilter->id);
    expect($result->title_exclude)->toBe(['Director']);
});

it('falls back to global filter when no company-specific filter exists', function (): void {
    $user = User::factory()->create();
    $company = Company::factory()->create();
    $globalFilter = JobFilter::factory()->create(['user_id' => $user->id, 'company_id' => null, 'title_exclude' => ['VP']]);

    $result = $this->service->getEffectiveFilter($user, $company);

    expect($result->id)->toBe($globalFilter->id);
});

it('returns null when no filters exist', function (): void {
    $user = User::factory()->create();
    $company = Company::factory()->create();

    $result = $this->service->getEffectiveFilter($user, $company);

    expect($result)->toBeNull();
});

// --- Title include filter ---

it('filters jobs by title include keywords', function (): void {
    $filter = JobFilter::factory()->make(['title_include' => ['Engineer', 'Developer']]);

    $jobs = collect([
        new JobPostingDTO('1', 'Senior Engineer', null, 'http://example.com', null, []),
        new JobPostingDTO('2', 'VP of Sales', null, 'http://example.com', null, []),
        new JobPostingDTO('3', 'Frontend Developer', null, 'http://example.com', null, []),
    ]);

    $result = $this->service->apply($jobs, $filter);

    expect($result)->toHaveCount(2);
    expect($result->pluck('externalId')->toArray())->toBe(['1', '3']);
});

// --- Title exclude filter ---

it('filters jobs by title exclude keywords', function (): void {
    $filter = JobFilter::factory()->make(['title_exclude' => ['VP', 'Director']]);

    $jobs = collect([
        new JobPostingDTO('1', 'Senior Engineer', null, 'http://example.com', null, []),
        new JobPostingDTO('2', 'VP of Sales', null, 'http://example.com', null, []),
        new JobPostingDTO('3', 'Director of Engineering', null, 'http://example.com', null, []),
    ]);

    $result = $this->service->apply($jobs, $filter);

    expect($result)->toHaveCount(1);
    expect($result->first()->externalId)->toBe('1');
});

// --- Remote only filter ---

it('filters for remote jobs by location string', function (): void {
    $filter = JobFilter::factory()->make(['remote_only' => true]);

    $jobs = collect([
        new JobPostingDTO('1', 'Engineer', 'Berlin, Germany (Remote)', 'http://example.com', null, []),
        new JobPostingDTO('2', 'Engineer', 'Berlin, Germany', 'http://example.com', null, []),
    ]);

    $result = $this->service->apply($jobs, $filter);

    expect($result)->toHaveCount(1);
    expect($result->first()->externalId)->toBe('1');
});

it('filters for remote jobs by raw_payload isRemote flag', function (): void {
    $filter = JobFilter::factory()->make(['remote_only' => true]);

    $jobs = collect([
        new JobPostingDTO('1', 'Engineer', 'Berlin', 'http://example.com', null, ['isRemote' => true]),
        new JobPostingDTO('2', 'Engineer', 'Berlin', 'http://example.com', null, ['isRemote' => false]),
    ]);

    $result = $this->service->apply($jobs, $filter);

    expect($result)->toHaveCount(1);
    expect($result->first()->externalId)->toBe('1');
});

// --- Department include filter ---

it('filters jobs by department include', function (): void {
    $filter = JobFilter::factory()->make(['department_include' => ['Engineering', 'Product']]);

    $jobs = collect([
        new JobPostingDTO('1', 'Engineer', null, 'http://example.com', 'Engineering', []),
        new JobPostingDTO('2', 'Salesperson', null, 'http://example.com', 'Sales', []),
        new JobPostingDTO('3', 'PM', null, 'http://example.com', 'Product', []),
        new JobPostingDTO('4', 'Designer', null, 'http://example.com', null, []),
    ]);

    $result = $this->service->apply($jobs, $filter);

    expect($result)->toHaveCount(3);
    expect($result->pluck('externalId')->toArray())->toBe(['1', '3', '4']);
});

// --- Null filter passes everything ---

it('passes all jobs when filter is null', function (): void {
    $jobs = collect([
        new JobPostingDTO('1', 'Engineer', null, 'http://example.com', null, []),
        new JobPostingDTO('2', 'VP Sales', null, 'http://example.com', null, []),
    ]);

    $result = $this->service->apply($jobs, null);

    expect($result)->toHaveCount(2);
});

// --- Combined filters ---

it('applies multiple filters in sequence', function (): void {
    $filter = JobFilter::factory()->make([
        'title_include' => ['Engineer'],
        'title_exclude' => ['Junior'],
        'remote_only' => true,
        'department_include' => ['Engineering'],
    ]);

    $jobs = collect([
        new JobPostingDTO('1', 'Senior Engineer', 'Remote', 'http://example.com', 'Engineering', []),
        new JobPostingDTO('2', 'Junior Engineer', 'Remote', 'http://example.com', 'Engineering', []),
        new JobPostingDTO('3', 'Senior Engineer', 'Berlin', 'http://example.com', 'Engineering', []),
        new JobPostingDTO('4', 'Senior Engineer', 'Remote', 'http://example.com', 'Sales', []),
    ]);

    $result = $this->service->apply($jobs, $filter);

    expect($result)->toHaveCount(1);
    expect($result->first()->externalId)->toBe('1');
});
