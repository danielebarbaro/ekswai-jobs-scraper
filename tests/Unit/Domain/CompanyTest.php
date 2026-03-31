<?php

declare(strict_types=1);

use App\Domain\Company\Company;
use App\Domain\JobPosting\JobPosting;
use App\Domain\User\User;

it('can create a company without user_id', function () {
    $company = Company::factory()->create();

    expect($company)->toBeInstanceOf(Company::class)
        ->and($company->name)->toBeString()
        ->and($company->workable_account_slug)->toBeString();
});

it('has subscribers relationship', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create();

    $company->subscribers()->attach($user->id, ['email_notifications' => true]);

    expect($company->subscribers)->toHaveCount(1)
        ->and($company->subscribers->first()->id)->toBe($user->id)
        ->and((bool) $company->subscribers->first()->pivot->email_notifications)->toBeTrue();
});

it('has job postings relationship', function () {
    $company = Company::factory()->create();
    JobPosting::factory()->count(3)->create(['company_id' => $company->id]);

    expect($company->jobPostings)->toHaveCount(3);
});

it('can toggle company activation', function () {
    $company = Company::factory()->create(['is_active' => true]);

    $company->toggleActivation();

    expect($company->fresh()->is_active)->toBeFalse();
});

it('scopes only active companies', function () {
    Company::factory()->count(2)->create(['is_active' => true]);
    Company::factory()->create(['is_active' => false]);

    expect(Company::active()->count())->toBe(2);
});

it('scopes companies with subscribers', function () {
    $companyWithSub = Company::factory()->create();
    $companyWithoutSub = Company::factory()->create();
    $user = User::factory()->create();

    $companyWithSub->subscribers()->attach($user->id);

    expect(Company::whereHas('subscribers')->count())->toBe(1);
});
