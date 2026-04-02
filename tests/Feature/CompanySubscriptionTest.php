<?php

declare(strict_types=1);

use App\Domain\Company\Company;
use App\Domain\JobPosting\JobPosting;
use App\Domain\User\User;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('shows the companies page', function () {
    $company = Company::factory()->create();
    $this->user->subscribedCompanies()->attach($company->id, ['email_notifications' => true]);

    $this->get(route('companies.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('companies')
            ->has('companies', 1)
        );
});

it('can follow a company', function () {
    Http::fake([
        'apply.workable.com/api/v1/widget/accounts/laravel' => Http::response([
            'name' => 'Laravel',
            'jobs' => [],
        ]),
    ]);

    $this->post(route('companies.follow'), ['slug' => 'laravel', 'provider' => 'workable'])
        ->assertRedirect();

    expect($this->user->fresh()->subscribedCompanies)->toHaveCount(1)
        ->and($this->user->subscribedCompanies->first()->provider_slug)->toBe('laravel');
});

it('can follow a company by URL without provider', function () {
    Http::fake([
        'apply.workable.com/api/v1/widget/accounts/laravel' => Http::response([
            'name' => 'Laravel',
            'jobs' => [],
        ]),
    ]);

    $this->post(route('companies.follow'), ['slug' => 'https://apply.workable.com/laravel'])
        ->assertRedirect();

    expect($this->user->fresh()->subscribedCompanies)->toHaveCount(1)
        ->and($this->user->subscribedCompanies->first()->provider_slug)->toBe('laravel');
});

it('returns validation error for slug without provider', function () {
    $this->post(route('companies.follow'), ['slug' => 'nonexistent'])
        ->assertSessionHasErrors('slug');
});

it('returns validation error for invalid slug', function () {
    Http::fake([
        'apply.workable.com/api/v1/widget/accounts/nonexistent' => Http::response([], 404),
    ]);

    $this->post(route('companies.follow'), ['slug' => 'nonexistent', 'provider' => 'workable'])
        ->assertSessionHasErrors('slug');
});

it('returns validation error when already following', function () {
    $company = Company::factory()->create(['provider_slug' => 'already']);
    $this->user->subscribedCompanies()->attach($company->id);

    $this->post(route('companies.follow'), ['slug' => 'already', 'provider' => 'workable'])
        ->assertSessionHasErrors('slug');
});

it('can unfollow a company', function () {
    $company = Company::factory()->create();
    $this->user->subscribedCompanies()->attach($company->id);

    $this->delete(route('companies.unfollow', $company))
        ->assertRedirect();

    expect($this->user->fresh()->subscribedCompanies)->toHaveCount(0);
});

it('removes job_posting_user records when unfollowing', function () {
    $company = Company::factory()->create();
    $this->user->subscribedCompanies()->attach($company->id);
    $jp = JobPosting::factory()->create(['company_id' => $company->id]);
    $this->user->jobPostingStatuses()->attach($jp->id, ['status' => 'new']);

    $this->delete(route('companies.unfollow', $company));

    expect($this->user->fresh()->jobPostingStatuses)->toHaveCount(0);
});

it('can toggle email notifications', function () {
    $company = Company::factory()->create();
    $this->user->subscribedCompanies()->attach($company->id, ['email_notifications' => true]);

    $this->patch(route('companies.notifications', $company));

    $pivot = $this->user->subscribedCompanies()->where('company_id', $company->id)->first()->pivot;
    expect((bool) $pivot->email_notifications)->toBeFalse();
});

it('requires authentication for all routes', function () {
    auth()->logout();

    $this->get(route('companies.index'))->assertRedirect('/login');
    $this->post(route('companies.follow'))->assertRedirect('/login');
});
