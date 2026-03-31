<?php

declare(strict_types=1);

use App\Application\Actions\JobPosting\SyncCompanyJobPostingsAction;
use App\Application\DTOs\WorkableJobDTO;
use App\Domain\Company\Company;
use App\Domain\JobPosting\JobPosting;
use App\Domain\User\User;
use App\Infrastructure\Services\Workable\WorkableHttpClient;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->company = Company::factory()->create([
        'user_id' => $this->user->id,
        'workable_account_slug' => 'test-company',
    ]);

    $this->workableClient = Mockery::mock(WorkableHttpClient::class);
    $this->action = new SyncCompanyJobPostingsAction($this->workableClient);
});

it('creates new job postings from workable api', function () {
    $workableJobs = collect([
        new WorkableJobDTO(
            externalId: 'job-1',
            title: 'Software Engineer',
            location: 'Remote',
            url: 'https://apply.workable.com/test-company/j/job-1',
            department: 'Engineering',
            rawPayload: ['id' => 'job-1', 'title' => 'Software Engineer']
        ),
        new WorkableJobDTO(
            externalId: 'job-2',
            title: 'Product Manager',
            location: 'New York',
            url: 'https://apply.workable.com/test-company/j/job-2',
            department: 'Product',
            rawPayload: ['id' => 'job-2', 'title' => 'Product Manager']
        ),
    ]);

    $this->workableClient
        ->shouldReceive('fetchJobsForCompany')
        ->with('test-company')
        ->once()
        ->andReturn($workableJobs);

    $newJobs = $this->action->execute($this->company);

    expect($newJobs)->toHaveCount(2);
    expect(JobPosting::count())->toBe(2);

    $firstJob = JobPosting::where('external_id', 'job-1')->first();
    expect($firstJob)
        ->title->toBe('Software Engineer')
        ->location->toBe('Remote')
        ->department->toBe('Engineering');
});

it('does not create duplicate jobs', function () {
    // Create existing job
    JobPosting::factory()->create([
        'company_id' => $this->company->id,
        'external_id' => 'job-1',
        'first_seen_at' => now()->subDays(5),
    ]);

    $workableJobs = collect([
        new WorkableJobDTO(
            externalId: 'job-1',
            title: 'Software Engineer',
            location: 'Remote',
            url: 'https://apply.workable.com/test-company/j/job-1',
            department: 'Engineering',
            rawPayload: ['id' => 'job-1']
        ),
        new WorkableJobDTO(
            externalId: 'job-2',
            title: 'Product Manager',
            location: 'New York',
            url: 'https://apply.workable.com/test-company/j/job-2',
            department: 'Product',
            rawPayload: ['id' => 'job-2']
        ),
    ]);

    $this->workableClient
        ->shouldReceive('fetchJobsForCompany')
        ->with('test-company')
        ->once()
        ->andReturn($workableJobs);

    $newJobs = $this->action->execute($this->company);

    expect($newJobs)->toHaveCount(1);
    expect(JobPosting::count())->toBe(2);

    $existingJob = JobPosting::where('external_id', 'job-1')->first();
    expect($existingJob->last_seen_at)->not->toBeNull();
});

it('returns empty collection when api returns no jobs', function () {
    $this->workableClient
        ->shouldReceive('fetchJobsForCompany')
        ->with('test-company')
        ->once()
        ->andReturn(collect());

    $newJobs = $this->action->execute($this->company);

    expect($newJobs)->toBeEmpty();
    expect(JobPosting::count())->toBe(0);
});
