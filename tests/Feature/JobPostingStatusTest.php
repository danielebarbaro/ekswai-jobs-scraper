<?php

declare(strict_types=1);

use App\Domain\Company\Company;
use App\Domain\JobPosting\JobPosting;
use App\Domain\User\User;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    $this->company = Company::factory()->create();
    $this->user->subscribedCompanies()->attach($this->company->id);
    $this->jobPosting = JobPosting::factory()->create(['company_id' => $this->company->id]);
    $this->user->jobPostingStatuses()->attach($this->jobPosting->id, ['status' => 'new']);
});

it('can change job posting status to bookmarked', function (): void {
    $this->patch(route('job-postings.status', $this->jobPosting), ['status' => 'bookmarked'])
        ->assertRedirect();

    $pivot = $this->user->jobPostingStatuses()->where('job_posting_id', $this->jobPosting->id)->first()->pivot;
    expect($pivot->status)->toBe('bookmarked');
});

it('can change job posting status to submitted', function (): void {
    $this->patch(route('job-postings.status', $this->jobPosting), ['status' => 'submitted'])
        ->assertRedirect();

    $pivot = $this->user->jobPostingStatuses()->where('job_posting_id', $this->jobPosting->id)->first()->pivot;
    expect($pivot->status)->toBe('submitted');
});

it('can change job posting status to interview', function (): void {
    $this->patch(route('job-postings.status', $this->jobPosting), ['status' => 'interview'])
        ->assertRedirect();

    $pivot = $this->user->jobPostingStatuses()->where('job_posting_id', $this->jobPosting->id)->first()->pivot;
    expect($pivot->status)->toBe('interview');
});

it('can dismiss a job posting', function (): void {
    $this->patch(route('job-postings.status', $this->jobPosting), ['status' => 'dismissed'])
        ->assertRedirect();

    $pivot = $this->user->jobPostingStatuses()->where('job_posting_id', $this->jobPosting->id)->first()->pivot;
    expect($pivot->status)->toBe('dismissed');
});

it('rejects invalid status', function (): void {
    $this->patch(route('job-postings.status', $this->jobPosting), ['status' => 'invalid'])
        ->assertSessionHasErrors('status');
});

it('requires authentication', function (): void {
    auth()->logout();

    $this->patch(route('job-postings.status', $this->jobPosting), ['status' => 'bookmarked'])
        ->assertRedirect('/login');
});
