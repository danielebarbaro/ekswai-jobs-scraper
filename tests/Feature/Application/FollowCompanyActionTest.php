<?php

declare(strict_types=1);

use App\Application\Actions\Company\FollowCompanyAction;
use App\Domain\Company\Company;
use App\Domain\JobPosting\JobPosting;
use App\Domain\User\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->action = app(FollowCompanyAction::class);
});

it('creates a new company and subscribes the user', function () {
    Http::fake([
        'apply.workable.com/api/v1/widget/accounts/test-company' => Http::response([
            'name' => 'Test Company',
            'jobs' => [],
        ]),
    ]);

    $company = $this->action->execute($this->user, 'test-company');

    expect($company->name)->toBe('Test Company')
        ->and($company->workable_account_slug)->toBe('test-company')
        ->and($this->user->subscribedCompanies)->toHaveCount(1);
});

it('subscribes to an existing company without creating a duplicate', function () {
    $existing = Company::factory()->create([
        'workable_account_slug' => 'existing-co',
        'name' => 'Existing Co',
    ]);

    Http::fake([
        'apply.workable.com/api/v1/widget/accounts/existing-co' => Http::response([
            'name' => 'Existing Co',
            'jobs' => [],
        ]),
    ]);

    $company = $this->action->execute($this->user, 'existing-co');

    expect($company->id)->toBe($existing->id)
        ->and(Company::where('workable_account_slug', 'existing-co')->count())->toBe(1)
        ->and($this->user->subscribedCompanies)->toHaveCount(1);
});

it('creates job_posting_user records for existing job postings', function () {
    $company = Company::factory()->create(['workable_account_slug' => 'has-jobs']);
    JobPosting::factory()->count(3)->create(['company_id' => $company->id]);

    Http::fake([
        'apply.workable.com/api/v1/widget/accounts/has-jobs' => Http::response([
            'name' => 'Has Jobs Co',
            'jobs' => [],
        ]),
    ]);

    $this->action->execute($this->user, 'has-jobs');

    expect($this->user->jobPostingStatuses)->toHaveCount(3);
    expect($this->user->jobPostingStatuses->pluck('pivot.status')->unique()->toArray())->toBe(['new']);
});

it('throws validation error for invalid slug', function () {
    Http::fake([
        'apply.workable.com/api/v1/widget/accounts/nonexistent' => Http::response([], 404),
    ]);

    $this->action->execute($this->user, 'nonexistent');
})->throws(ValidationException::class);

it('throws validation error when already following', function () {
    $company = Company::factory()->create(['workable_account_slug' => 'already-followed']);
    $this->user->subscribedCompanies()->attach($company->id);

    $this->action->execute($this->user, 'already-followed');
})->throws(ValidationException::class);

it('normalizes slug to lowercase', function () {
    Http::fake([
        'apply.workable.com/api/v1/widget/accounts/my-company' => Http::response([
            'name' => 'My Company',
            'jobs' => [],
        ]),
    ]);

    $company = $this->action->execute($this->user, '  My-Company  ');

    expect($company->workable_account_slug)->toBe('my-company');
});
