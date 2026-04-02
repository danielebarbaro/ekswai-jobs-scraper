<?php

declare(strict_types=1);

use App\Domain\Company\Company;
use App\Domain\JobFilter\JobFilter;
use App\Domain\JobPosting\JobPosting;
use App\Domain\ScraperConfig\ScraperConfig;
use App\Domain\User\User;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($this->admin);
});

it('can access the admin panel', function () {
    $this->get('/admin')
        ->assertOk();
});

it('can list companies', function () {
    Company::factory()->count(3)->create();

    $this->get('/admin/companies')
        ->assertOk();
});

it('can view create company form', function () {
    $this->get('/admin/companies/create')
        ->assertOk();
});

it('can view edit company form', function () {
    $company = Company::factory()->create();

    $this->get("/admin/companies/{$company->id}/edit")
        ->assertOk();
});

it('can list job postings', function () {
    JobPosting::factory()->count(2)->create();

    $this->get('/admin/job-postings')
        ->assertOk();
});

it('can list users', function () {
    User::factory()->count(2)->create();

    $this->get('/admin/users')
        ->assertOk();
});

it('can list scraper configs', function () {
    ScraperConfig::factory()->create();
    ScraperConfig::factory()->factorial()->create();

    $this->get('/admin/scraper-configs')
        ->assertOk();
});

it('can list job filters', function () {
    JobFilter::factory()->count(2)->create();

    $this->get('/admin/job-filters')
        ->assertOk();
});
