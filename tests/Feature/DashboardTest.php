<?php

declare(strict_types=1);

use App\Domain\Company\Company;
use App\Domain\JobPosting\JobPosting;
use App\Domain\User\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('shows the dashboard with job postings', function () {
    $company = Company::factory()->create();
    $this->user->subscribedCompanies()->attach($company->id);
    $jp = JobPosting::factory()->create(['company_id' => $company->id]);
    $this->user->jobPostingStatuses()->attach($jp->id, ['status' => 'new']);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard')
            ->has('jobPostings.data', 1)
            ->has('companies', 1)
            ->has('filters')
        );
});

it('filters by status', function () {
    $company = Company::factory()->create();
    $this->user->subscribedCompanies()->attach($company->id);

    $jp1 = JobPosting::factory()->create(['company_id' => $company->id]);
    $jp2 = JobPosting::factory()->create(['company_id' => $company->id]);
    $this->user->jobPostingStatuses()->attach($jp1->id, ['status' => 'bookmarked']);
    $this->user->jobPostingStatuses()->attach($jp2->id, ['status' => 'new']);

    $this->get(route('dashboard', ['status' => 'bookmarked']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('jobPostings.data', 1)
            ->where('jobPostings.data.0.status', 'bookmarked')
        );
});

it('filters by company', function () {
    $company1 = Company::factory()->create();
    $company2 = Company::factory()->create();
    $this->user->subscribedCompanies()->attach([$company1->id, $company2->id]);

    $jp1 = JobPosting::factory()->create(['company_id' => $company1->id]);
    $jp2 = JobPosting::factory()->create(['company_id' => $company2->id]);
    $this->user->jobPostingStatuses()->attach($jp1->id, ['status' => 'new']);
    $this->user->jobPostingStatuses()->attach($jp2->id, ['status' => 'new']);

    $this->get(route('dashboard', ['company' => $company1->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('jobPostings.data', 1)
        );
});

it('hides dismissed by default', function () {
    $company = Company::factory()->create();
    $this->user->subscribedCompanies()->attach($company->id);

    $jp1 = JobPosting::factory()->create(['company_id' => $company->id]);
    $jp2 = JobPosting::factory()->create(['company_id' => $company->id]);
    $this->user->jobPostingStatuses()->attach($jp1->id, ['status' => 'new']);
    $this->user->jobPostingStatuses()->attach($jp2->id, ['status' => 'dismissed']);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('jobPostings.data', 1)
        );
});

it('shows dismissed when filtered explicitly', function () {
    $company = Company::factory()->create();
    $this->user->subscribedCompanies()->attach($company->id);

    $jp = JobPosting::factory()->create(['company_id' => $company->id]);
    $this->user->jobPostingStatuses()->attach($jp->id, ['status' => 'dismissed']);

    $this->get(route('dashboard', ['status' => 'dismissed']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('jobPostings.data', 1)
        );
});

it('shows empty state when no subscriptions', function () {
    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('jobPostings.data', 0)
            ->has('companies', 0)
        );
});
