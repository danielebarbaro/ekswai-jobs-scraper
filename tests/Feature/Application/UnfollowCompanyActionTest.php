<?php

declare(strict_types=1);

use App\Application\Actions\Company\UnfollowCompanyAction;
use App\Domain\Company\Company;
use App\Domain\JobPosting\JobPosting;
use App\Domain\User\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->company = Company::factory()->create();
    $this->user->subscribedCompanies()->attach($this->company->id);

    $this->action = new UnfollowCompanyAction;
});

it('removes the subscription', function () {
    $this->action->execute($this->user, $this->company);

    expect($this->user->fresh()->subscribedCompanies)->toHaveCount(0);
});

it('removes job_posting_user records for the company', function () {
    $jobPostings = JobPosting::factory()->count(2)->create(['company_id' => $this->company->id]);

    foreach ($jobPostings as $jp) {
        $this->user->jobPostingStatuses()->attach($jp->id, ['status' => 'new']);
    }

    expect($this->user->jobPostingStatuses)->toHaveCount(2);

    $this->action->execute($this->user, $this->company);

    expect($this->user->fresh()->jobPostingStatuses)->toHaveCount(0);
});

it('does not affect other users subscriptions', function () {
    $otherUser = User::factory()->create();
    $otherUser->subscribedCompanies()->attach($this->company->id);

    $this->action->execute($this->user, $this->company);

    expect($otherUser->fresh()->subscribedCompanies)->toHaveCount(1);
});

it('does not affect job postings from other companies', function () {
    $otherCompany = Company::factory()->create();
    $otherJobPosting = JobPosting::factory()->create(['company_id' => $otherCompany->id]);
    $this->user->jobPostingStatuses()->attach($otherJobPosting->id, ['status' => 'bookmarked']);

    $thisJobPosting = JobPosting::factory()->create(['company_id' => $this->company->id]);
    $this->user->jobPostingStatuses()->attach($thisJobPosting->id, ['status' => 'new']);

    $this->action->execute($this->user, $this->company);

    $remaining = $this->user->fresh()->jobPostingStatuses;
    expect($remaining)->toHaveCount(1)
        ->and($remaining->first()->id)->toBe($otherJobPosting->id);
});
