<?php

declare(strict_types=1);

use App\Domain\Company\Company;
use App\Domain\Company\JobBoardProvider;
use App\Domain\JobPosting\JobPosting;
use App\Domain\User\User;

it('can create a company without user_id', function (): void {
    $company = Company::factory()->create();

    expect($company)->toBeInstanceOf(Company::class)
        ->and($company->name)->toBeString()
        ->and($company->provider_slug)->toBeString()
        ->and($company->provider)->toBe(JobBoardProvider::Workable);
});

it('casts provider to JobBoardProvider enum', function (): void {
    $company = Company::factory()->create(['provider' => 'workable']);

    expect($company->provider)->toBe(JobBoardProvider::Workable);
});

it('has subscribers relationship', function (): void {
    $company = Company::factory()->create();
    $user = User::factory()->create();

    $company->subscribers()->attach($user->id, ['email_notifications' => true]);

    expect($company->subscribers)->toHaveCount(1)
        ->and($company->subscribers->first()->id)->toBe($user->id)
        ->and((bool) $company->subscribers->first()->pivot->email_notifications)->toBeTrue();
});

it('has job postings relationship', function (): void {
    $company = Company::factory()->create();
    JobPosting::factory()->count(3)->create(['company_id' => $company->id]);

    expect($company->jobPostings)->toHaveCount(3);
});

it('can toggle company activation', function (): void {
    $company = Company::factory()->create(['is_active' => true]);

    $company->toggleActivation();

    expect($company->fresh()->is_active)->toBeFalse();
});

it('scopes only active companies', function (): void {
    Company::factory()->count(2)->create(['is_active' => true]);
    Company::factory()->create(['is_active' => false]);

    expect(Company::query()->active()->count())->toBe(2);
});

it('scopes companies with subscribers', function (): void {
    $companyWithSub = Company::factory()->create();
    $companyWithoutSub = Company::factory()->create();
    $user = User::factory()->create();

    $companyWithSub->subscribers()->attach($user->id);

    expect(Company::query()->whereHas('subscribers')->count())->toBe(1);
});
