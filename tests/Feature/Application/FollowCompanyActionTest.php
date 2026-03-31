<?php

declare(strict_types=1);

use App\Application\Actions\Company\FollowCompanyAction;
use App\Domain\Company\Company;
use App\Domain\Company\JobBoardProvider;
use App\Domain\JobPosting\JobPosting;
use App\Domain\User\User;
use App\Infrastructure\Services\Contracts\JobBoardClient;
use App\Infrastructure\Services\JobBoardClientFactory;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->user = User::factory()->create();

    $this->jobBoardClient = Mockery::mock(JobBoardClient::class);
    $this->factory = Mockery::mock(JobBoardClientFactory::class);
    $this->factory->shouldReceive('make')->with(JobBoardProvider::Workable)->andReturn($this->jobBoardClient);

    $this->syncAction = new \App\Application\Actions\JobPosting\SyncCompanyJobPostingsAction($this->factory);
    $this->action = new FollowCompanyAction($this->factory, $this->syncAction);
});

it('creates a new company and subscribes the user', function () {
    $this->jobBoardClient->shouldReceive('validateSlug')->with('test-company')->andReturn('Test Company');
    $this->jobBoardClient->shouldReceive('fetchJobsForCompany')->andReturn(collect());

    $company = $this->action->execute($this->user, 'test-company');

    expect($company->name)->toBe('Test Company')
        ->and($company->provider_slug)->toBe('test-company')
        ->and($company->provider)->toBe(JobBoardProvider::Workable)
        ->and($this->user->subscribedCompanies)->toHaveCount(1);
});

it('subscribes to an existing company without creating a duplicate', function () {
    $existing = Company::factory()->create([
        'provider_slug' => 'existing-co',
        'name' => 'Existing Co',
    ]);

    $this->jobBoardClient->shouldReceive('validateSlug')->with('existing-co')->andReturn('Existing Co');
    $this->jobBoardClient->shouldReceive('fetchJobsForCompany')->andReturn(collect());

    $company = $this->action->execute($this->user, 'existing-co');

    expect($company->id)->toBe($existing->id)
        ->and(Company::where('provider_slug', 'existing-co')->count())->toBe(1)
        ->and($this->user->subscribedCompanies)->toHaveCount(1);
});

it('creates job_posting_user records for existing job postings', function () {
    $company = Company::factory()->create(['provider_slug' => 'has-jobs']);
    JobPosting::factory()->count(3)->create(['company_id' => $company->id]);

    $this->jobBoardClient->shouldReceive('validateSlug')->with('has-jobs')->andReturn('Has Jobs Co');

    $this->action->execute($this->user, 'has-jobs');

    expect($this->user->jobPostingStatuses)->toHaveCount(3);
    expect($this->user->jobPostingStatuses->pluck('pivot.status')->unique()->toArray())->toBe(['new']);
});

it('throws validation error for invalid slug', function () {
    $this->jobBoardClient->shouldReceive('validateSlug')->with('nonexistent')->andReturn(null);

    $this->action->execute($this->user, 'nonexistent');
})->throws(ValidationException::class);

it('throws validation error when already following', function () {
    $company = Company::factory()->create(['provider_slug' => 'already-followed']);
    $this->user->subscribedCompanies()->attach($company->id);

    $this->action->execute($this->user, 'already-followed');
})->throws(ValidationException::class);

it('normalizes slug to lowercase', function () {
    $this->jobBoardClient->shouldReceive('validateSlug')->with('my-company')->andReturn('My Company');
    $this->jobBoardClient->shouldReceive('fetchJobsForCompany')->andReturn(collect());

    $company = $this->action->execute($this->user, '  My-Company  ');

    expect($company->provider_slug)->toBe('my-company');
});
