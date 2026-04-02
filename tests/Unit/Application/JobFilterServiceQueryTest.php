<?php

declare(strict_types=1);

use App\Application\Services\JobFilterService;
use App\Domain\Company\Company;
use App\Domain\JobFilter\JobFilter;
use App\Domain\JobPosting\JobPosting;

beforeEach(function (): void {
    $this->service = app(JobFilterService::class);
});

it('applies title include filter to query', function (): void {
    $company = Company::factory()->create();
    JobPosting::factory()->create(['company_id' => $company->id, 'title' => 'Senior Engineer']);
    JobPosting::factory()->create(['company_id' => $company->id, 'title' => 'VP of Sales']);

    $filter = JobFilter::factory()->make(['title_include' => ['Engineer']]);
    $query = JobPosting::query()->where('company_id', $company->id);
    $result = $this->service->applyToQuery($query, $filter)->get();

    expect($result)->toHaveCount(1)
        ->and($result->first()->title)->toBe('Senior Engineer');
});

it('applies title exclude filter to query', function (): void {
    $company = Company::factory()->create();
    JobPosting::factory()->create(['company_id' => $company->id, 'title' => 'Senior Engineer']);
    JobPosting::factory()->create(['company_id' => $company->id, 'title' => 'VP of Sales']);

    $filter = JobFilter::factory()->make(['title_exclude' => ['VP']]);
    $query = JobPosting::query()->where('company_id', $company->id);
    $result = $this->service->applyToQuery($query, $filter)->get();

    expect($result)->toHaveCount(1)
        ->and($result->first()->title)->toBe('Senior Engineer');
});

it('applies remote only filter to query', function (): void {
    $company = Company::factory()->create();
    JobPosting::factory()->create(['company_id' => $company->id, 'title' => 'Remote Dev', 'location' => 'Remote']);
    JobPosting::factory()->create(['company_id' => $company->id, 'title' => 'Office Dev', 'location' => 'Berlin']);

    $filter = JobFilter::factory()->make(['remote_only' => true]);
    $query = JobPosting::query()->where('company_id', $company->id);
    $result = $this->service->applyToQuery($query, $filter)->get();

    expect($result)->toHaveCount(1)
        ->and($result->first()->title)->toBe('Remote Dev');
});

it('applies department include filter to query', function (): void {
    $company = Company::factory()->create();
    JobPosting::factory()->create(['company_id' => $company->id, 'title' => 'Dev', 'department' => 'Engineering']);
    JobPosting::factory()->create(['company_id' => $company->id, 'title' => 'Rep', 'department' => 'Sales']);
    JobPosting::factory()->create(['company_id' => $company->id, 'title' => 'Designer', 'department' => null]);

    $filter = JobFilter::factory()->make(['department_include' => ['Engineering']]);
    $query = JobPosting::query()->where('company_id', $company->id);
    $result = $this->service->applyToQuery($query, $filter)->get();

    expect($result)->toHaveCount(2);
    expect($result->pluck('department')->toArray())->toContain('Engineering');
});

it('returns unmodified query when filter is null', function (): void {
    $company = Company::factory()->create();
    JobPosting::factory()->count(3)->create(['company_id' => $company->id]);

    $query = JobPosting::query()->where('company_id', $company->id);
    $result = $this->service->applyToQuery($query, null)->get();

    expect($result)->toHaveCount(3);
});
