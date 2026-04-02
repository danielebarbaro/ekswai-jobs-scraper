<?php

declare(strict_types=1);

use App\Domain\Company\Company;
use App\Domain\JobFilter\JobFilter;
use App\Domain\User\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('returns current user filters on the index page', function () {
    $globalFilter = JobFilter::factory()->global()->create(['user_id' => $this->user->id]);
    $company = Company::factory()->create();
    $this->user->subscribedCompanies()->attach($company->id);
    $companyFilter = JobFilter::factory()->forCompany($company->id)->create(['user_id' => $this->user->id]);

    $this->get(route('job-filters.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('settings/filters')
            ->where('globalFilter.id', $globalFilter->id)
            ->has('companyFilters', 1)
            ->has('companies', 1)
        );
});

it('creates a global filter', function () {
    $this->post(route('job-filters.store'), [
        'company_id' => null,
        'title_include' => ['engineer'],
        'remote_only' => true,
    ])->assertRedirect();

    expect($this->user->jobFilters()->global()->count())->toBe(1);

    $filter = $this->user->jobFilters()->global()->first();
    expect($filter->title_include)->toBe(['engineer'])
        ->and($filter->remote_only)->toBeTrue();
});

it('creates a company-specific filter', function () {
    $company = Company::factory()->create();

    $this->post(route('job-filters.store'), [
        'company_id' => $company->id,
        'title_exclude' => ['intern'],
        'remote_only' => false,
    ])->assertRedirect();

    expect($this->user->jobFilters()->forCompany($company->id)->count())->toBe(1);
});

it('updates an existing filter', function () {
    $filter = JobFilter::factory()->global()->create([
        'user_id' => $this->user->id,
        'title_include' => ['old'],
    ]);

    $this->put(route('job-filters.update', $filter), [
        'title_include' => ['new'],
        'remote_only' => false,
    ])->assertRedirect();

    expect($filter->fresh()->title_include)->toBe(['new']);
});

it('deletes a filter', function () {
    $filter = JobFilter::factory()->global()->create(['user_id' => $this->user->id]);

    $this->delete(route('job-filters.destroy', $filter))
        ->assertRedirect();

    expect(JobFilter::find($filter->id))->toBeNull();
});

it('prevents creating a duplicate global filter', function () {
    JobFilter::factory()->global()->create(['user_id' => $this->user->id]);

    $this->post(route('job-filters.store'), [
        'company_id' => null,
        'remote_only' => false,
    ])->assertSessionHasErrors('company_id');
});

it('prevents accessing another user filter', function () {
    $otherUser = User::factory()->create();
    $filter = JobFilter::factory()->global()->create(['user_id' => $otherUser->id]);

    $this->put(route('job-filters.update', $filter), [
        'remote_only' => true,
    ])->assertForbidden();

    $this->delete(route('job-filters.destroy', $filter))
        ->assertForbidden();
});
