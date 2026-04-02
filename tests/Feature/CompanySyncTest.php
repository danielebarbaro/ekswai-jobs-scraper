<?php

declare(strict_types=1);

use App\Application\DTOs\JobPostingDTO;
use App\Domain\Company\Company;
use App\Domain\User\User;
use App\Infrastructure\Services\Contracts\JobBoardClient;
use App\Infrastructure\Services\JobBoardClientFactory;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    $this->company = Company::factory()->create();
    $this->user->subscribedCompanies()->attach($this->company->id);

    $mockClient = Mockery::mock(JobBoardClient::class);
    $mockClient->shouldReceive('fetchJobsForCompany')
        ->andReturn(collect([
            new JobPostingDTO('1', 'Engineer', 'Berlin', 'http://example.com/1', 'Engineering', []),
        ]));

    $mockFactory = Mockery::mock(JobBoardClientFactory::class);
    $mockFactory->shouldReceive('make')->andReturn($mockClient);
    $this->app->instance(JobBoardClientFactory::class, $mockFactory);
});

it('syncs a company and updates last_synced_at', function () {
    $this->post(route('companies.sync', $this->company))
        ->assertRedirect();

    expect($this->company->fresh()->last_synced_at)->not->toBeNull();
});

it('blocks sync after 2 attempts per day', function () {
    $this->post(route('companies.sync', $this->company))->assertRedirect();
    $this->post(route('companies.sync', $this->company))->assertRedirect();

    $this->post(route('companies.sync', $this->company))
        ->assertRedirect()
        ->assertSessionHas('error');
});

it('prevents syncing unsubscribed company', function () {
    $other = Company::factory()->create();

    $this->post(route('companies.sync', $other))
        ->assertNotFound();
});
