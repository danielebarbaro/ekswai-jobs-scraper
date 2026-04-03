<?php

declare(strict_types=1);

use App\Domain\Company\Company;
use App\Domain\JobPosting\JobPosting;
use App\Domain\User\User;

beforeEach(function (): void {
    $this->withoutVite();
    $this->user = User::factory()->create(['has_completed_onboarding' => false]);
    $this->actingAs($this->user);
});

it('returns companies matching filter keywords', function (): void {
    $company = Company::factory()->create(['is_active' => true]);
    JobPosting::factory()->create([
        'company_id' => $company->id,
        'title' => 'Staff PHP Engineer',
    ]);

    $otherCompany = Company::factory()->create(['is_active' => true]);
    JobPosting::factory()->create([
        'company_id' => $otherCompany->id,
        'title' => 'Marketing Manager',
    ]);

    $this->getJson('/explore/companies?'.http_build_query([
        'title_include' => ['php'],
    ]))->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $company->id);
});

it('returns companies ordered by match count', function (): void {
    $manyMatches = Company::factory()->create(['is_active' => true, 'name' => 'Many Matches']);
    JobPosting::factory()->count(5)->create([
        'company_id' => $manyMatches->id,
        'title' => 'PHP Developer',
    ]);

    $fewMatches = Company::factory()->create(['is_active' => true, 'name' => 'Few Matches']);
    JobPosting::factory()->create([
        'company_id' => $fewMatches->id,
        'title' => 'PHP Developer',
    ]);

    $response = $this->getJson('/explore/companies?'.http_build_query([
        'title_include' => ['php'],
    ]))->assertOk();

    $ids = collect($response->json('data'))->pluck('id')->toArray();
    expect($ids[0])->toBe($manyMatches->id);
});

it('marks already followed companies', function (): void {
    $followed = Company::factory()->create(['is_active' => true]);
    JobPosting::factory()->create(['company_id' => $followed->id, 'title' => 'PHP Dev']);
    $this->user->subscribedCompanies()->attach($followed->id);

    $response = $this->getJson('/explore/companies?'.http_build_query([
        'title_include' => ['php'],
    ]))->assertOk();

    expect($response->json('data.0.is_already_followed'))->toBeTrue();
});

it('excludes inactive companies', function (): void {
    $inactive = Company::factory()->create(['is_active' => false]);
    JobPosting::factory()->create(['company_id' => $inactive->id, 'title' => 'PHP Dev']);

    $this->getJson('/explore/companies?'.http_build_query([
        'title_include' => ['php'],
    ]))->assertOk()
        ->assertJsonCount(0, 'data');
});

it('can follow multiple companies at once', function (): void {
    $companies = Company::factory()->count(3)->create(['is_active' => true]);
    $companies->each(fn ($c) => JobPosting::factory()->create(['company_id' => $c->id]));

    $this->postJson('/explore/follow-many', [
        'company_ids' => $companies->pluck('id')->toArray(),
        'filters' => [
            'title_include' => ['engineer'],
            'title_exclude' => [],
            'country_ids' => [],
            'remote_only' => true,
            'department_include' => [],
        ],
    ])->assertOk();

    expect($this->user->fresh()->subscribedCompanies)->toHaveCount(3);
    expect($this->user->fresh()->has_completed_onboarding)->toBeTrue();
    expect($this->user->fresh()->globalJobFilter)->not->toBeNull();
    expect($this->user->fresh()->globalJobFilter->remote_only)->toBeTrue();
});

it('skips already followed companies in follow-many', function (): void {
    $company = Company::factory()->create(['is_active' => true]);
    $this->user->subscribedCompanies()->attach($company->id);

    $this->postJson('/explore/follow-many', [
        'company_ids' => [$company->id],
        'filters' => [
            'title_include' => [],
            'title_exclude' => [],
            'country_ids' => [],
            'remote_only' => false,
            'department_include' => [],
        ],
    ])->assertOk();

    expect($this->user->fresh()->subscribedCompanies)->toHaveCount(1);
});

it('can skip onboarding', function (): void {
    $this->postJson('/explore/skip-onboarding')
        ->assertOk();

    expect($this->user->fresh()->has_completed_onboarding)->toBeTrue();
});
