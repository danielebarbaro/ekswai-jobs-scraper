<?php

declare(strict_types=1);

use App\Domain\Company\Company;
use App\Domain\JobFilter\JobFilter;
use App\Domain\JobPosting\JobPosting;
use App\Domain\User\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    $this->company = Company::factory()->create();
    $this->user->subscribedCompanies()->attach($this->company->id);
});

it('hides jobs matching title_exclude filter', function () {
    JobFilter::factory()
        ->global()
        ->withTitleExclude(['VP', 'Director'])
        ->create(['user_id' => $this->user->id]);

    $visible = JobPosting::factory()->create([
        'company_id' => $this->company->id,
        'title' => 'Software Engineer',
    ]);
    $hidden = JobPosting::factory()->create([
        'company_id' => $this->company->id,
        'title' => 'VP of Engineering',
    ]);

    $this->user->jobPostingStatuses()->attach($visible->id, ['status' => 'new']);
    $this->user->jobPostingStatuses()->attach($hidden->id, ['status' => 'new']);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('jobPostings.data', 1)
            ->where('jobPostings.data.0.title', 'Software Engineer')
        );
});

it('shows only jobs matching title_include filter', function () {
    JobFilter::factory()
        ->global()
        ->withTitleInclude(['Engineer', 'Developer'])
        ->create(['user_id' => $this->user->id]);

    $visible = JobPosting::factory()->create([
        'company_id' => $this->company->id,
        'title' => 'Software Engineer',
    ]);
    $hidden = JobPosting::factory()->create([
        'company_id' => $this->company->id,
        'title' => 'Product Manager',
    ]);

    $this->user->jobPostingStatuses()->attach($visible->id, ['status' => 'new']);
    $this->user->jobPostingStatuses()->attach($hidden->id, ['status' => 'new']);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('jobPostings.data', 1)
            ->where('jobPostings.data.0.title', 'Software Engineer')
        );
});

it('shows only remote jobs when remote_only is true', function () {
    JobFilter::factory()
        ->global()
        ->remoteOnly()
        ->create(['user_id' => $this->user->id]);

    $remote = JobPosting::factory()->create([
        'company_id' => $this->company->id,
        'title' => 'Remote Engineer',
        'location' => 'Remote - Worldwide',
    ]);
    $onsite = JobPosting::factory()->create([
        'company_id' => $this->company->id,
        'title' => 'Onsite Engineer',
        'location' => 'New York, NY',
    ]);

    $this->user->jobPostingStatuses()->attach($remote->id, ['status' => 'new']);
    $this->user->jobPostingStatuses()->attach($onsite->id, ['status' => 'new']);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('jobPostings.data', 1)
            ->where('jobPostings.data.0.title', 'Remote Engineer')
        );
});

it('uses company-specific filter over global', function () {
    // Global filter excludes "Engineer"
    JobFilter::factory()
        ->global()
        ->withTitleExclude(['Engineer'])
        ->create(['user_id' => $this->user->id]);

    // Company filter excludes "Manager" (not "Engineer")
    JobFilter::factory()
        ->forCompany($this->company->id)
        ->withTitleExclude(['Manager'])
        ->create(['user_id' => $this->user->id]);

    $engineer = JobPosting::factory()->create([
        'company_id' => $this->company->id,
        'title' => 'Software Engineer',
    ]);
    $manager = JobPosting::factory()->create([
        'company_id' => $this->company->id,
        'title' => 'Product Manager',
    ]);

    $this->user->jobPostingStatuses()->attach($engineer->id, ['status' => 'new']);
    $this->user->jobPostingStatuses()->attach($manager->id, ['status' => 'new']);

    // When filtering by company, company-specific filter should be used
    // So "Engineer" is visible (not excluded) and "Manager" is hidden
    $this->get(route('dashboard', ['company' => $this->company->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('jobPostings.data', 1)
            ->where('jobPostings.data.0.title', 'Software Engineer')
        );
});

it('shows all jobs when no filter exists', function () {
    $jp1 = JobPosting::factory()->create([
        'company_id' => $this->company->id,
        'title' => 'VP of Engineering',
    ]);
    $jp2 = JobPosting::factory()->create([
        'company_id' => $this->company->id,
        'title' => 'Software Engineer',
    ]);

    $this->user->jobPostingStatuses()->attach($jp1->id, ['status' => 'new']);
    $this->user->jobPostingStatuses()->attach($jp2->id, ['status' => 'new']);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('jobPostings.data', 2)
        );
});
