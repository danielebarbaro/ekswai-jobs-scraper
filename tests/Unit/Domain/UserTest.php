<?php

declare(strict_types=1);

use App\Domain\Company\Company;
use App\Domain\JobPosting\JobPosting;
use App\Domain\User\User;

it('has subscribed companies relationship', function () {
    $user = User::factory()->create();
    $company = Company::factory()->create();

    $user->subscribedCompanies()->attach($company->id, ['email_notifications' => true]);

    expect($user->subscribedCompanies)->toHaveCount(1)
        ->and($user->subscribedCompanies->first()->id)->toBe($company->id);
});

it('has job posting statuses relationship', function () {
    $user = User::factory()->create();
    $company = Company::factory()->create();
    $jobPosting = JobPosting::factory()->create(['company_id' => $company->id]);

    $user->jobPostingStatuses()->attach($jobPosting->id, ['status' => 'bookmarked']);

    expect($user->jobPostingStatuses)->toHaveCount(1)
        ->and($user->jobPostingStatuses->first()->pivot->status)->toBe('bookmarked');
});

it('can check email notification preference for a company', function () {
    $user = User::factory()->create();
    $company = Company::factory()->create();

    $user->subscribedCompanies()->attach($company->id, ['email_notifications' => false]);

    $pivot = $user->subscribedCompanies()->where('company_id', $company->id)->first()->pivot;

    expect($pivot->email_notifications)->toBeFalse();
});
