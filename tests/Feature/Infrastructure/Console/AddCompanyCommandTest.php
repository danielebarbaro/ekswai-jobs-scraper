<?php

declare(strict_types=1);

use App\Domain\Company\Company;
use App\Infrastructure\Services\Contracts\JobBoardClient;
use App\Infrastructure\Services\JobBoardClientFactory;

it('adds a company successfully', function () {
    $mockClient = Mockery::mock(JobBoardClient::class);
    $mockClient->shouldReceive('validateSlug')
        ->with('testco')
        ->once()
        ->andReturn('TestCo Inc');

    $mockFactory = Mockery::mock(JobBoardClientFactory::class);
    $mockFactory->shouldReceive('make')
        ->once()
        ->andReturn($mockClient);

    $this->app->instance(JobBoardClientFactory::class, $mockFactory);

    $this->artisan('companies:add', ['provider' => 'workable', 'slug' => 'testco'])
        ->assertSuccessful();

    expect(Company::where('provider_slug', 'testco')->exists())->toBeTrue();
});

it('fails with invalid provider', function () {
    $this->artisan('companies:add', ['provider' => 'invalid', 'slug' => 'testco'])
        ->assertFailed();
});

it('fails when company already exists', function () {
    Company::factory()->create([
        'provider' => 'workable',
        'provider_slug' => 'existing',
        'name' => 'Existing Co',
    ]);

    $this->artisan('companies:add', ['provider' => 'workable', 'slug' => 'existing'])
        ->assertFailed();
});

it('fails when slug validation returns null', function () {
    $mockClient = Mockery::mock(JobBoardClient::class);
    $mockClient->shouldReceive('validateSlug')
        ->with('invalid-slug')
        ->once()
        ->andReturn(null);

    $mockFactory = Mockery::mock(JobBoardClientFactory::class);
    $mockFactory->shouldReceive('make')
        ->once()
        ->andReturn($mockClient);

    $this->app->instance(JobBoardClientFactory::class, $mockFactory);

    $this->artisan('companies:add', ['provider' => 'workable', 'slug' => 'invalid-slug'])
        ->assertFailed();
});

it('uses name option when provided', function () {
    $mockClient = Mockery::mock(JobBoardClient::class);
    $mockClient->shouldReceive('validateSlug')
        ->with('testco')
        ->once()
        ->andReturn('Auto Detected Name');

    $mockFactory = Mockery::mock(JobBoardClientFactory::class);
    $mockFactory->shouldReceive('make')
        ->once()
        ->andReturn($mockClient);

    $this->app->instance(JobBoardClientFactory::class, $mockFactory);

    $this->artisan('companies:add', [
        'provider' => 'lever',
        'slug' => 'testco',
        '--name' => 'Custom Name',
    ])->assertSuccessful();

    expect(Company::where('name', 'Custom Name')->exists())->toBeTrue();
});
