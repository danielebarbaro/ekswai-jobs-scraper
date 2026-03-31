<?php

declare(strict_types=1);

use App\Domain\Company\Company;
use App\Domain\User\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('can create a company', function () {
    $company = Company::create([
        'user_id' => $this->user->id,
        'name' => 'Test Company',
        'workable_account_slug' => 'test-company',
        'is_active' => true,
    ]);

    expect($company)
        ->name->toBe('Test Company')
        ->workable_account_slug->toBe('test-company')
        ->is_active->toBeTrue()
        ->user_id->toBe($this->user->id);
});

it('can toggle company activation', function () {
    $company = Company::factory()->create([
        'user_id' => $this->user->id,
        'is_active' => true,
    ]);

    expect($company->is_active)->toBeTrue();

    $company->toggleActivation();

    expect($company->fresh()->is_active)->toBeFalse();

    $company->toggleActivation();

    expect($company->fresh()->is_active)->toBeTrue();
});

it('scopes only active companies', function () {
    Company::factory()->count(3)->create([
        'user_id' => $this->user->id,
        'is_active' => true,
    ]);

    Company::factory()->count(2)->create([
        'user_id' => $this->user->id,
        'is_active' => false,
    ]);

    $activeCompanies = Company::active()->get();

    expect($activeCompanies)->toHaveCount(3);
});
