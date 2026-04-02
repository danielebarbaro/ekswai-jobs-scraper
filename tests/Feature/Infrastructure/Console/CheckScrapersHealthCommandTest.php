<?php

declare(strict_types=1);

use App\Infrastructure\Services\Scraping\ScraperHealthChecker;

it('outputs pass results when all providers pass', function () {
    $mock = Mockery::mock(ScraperHealthChecker::class);
    $mock->shouldReceive('checkAll')
        ->once()
        ->andReturn(collect([
            ['provider' => 'teamtailor', 'passed' => true],
        ]));

    $this->app->instance(ScraperHealthChecker::class, $mock);

    $this->artisan('jobs:check-scrapers-health')
        ->assertSuccessful();
});

it('outputs failure result when provider fails', function () {
    $mock = Mockery::mock(ScraperHealthChecker::class);
    $mock->shouldReceive('checkAll')
        ->once()
        ->andReturn(collect([
            ['provider' => 'teamtailor', 'passed' => false, 'error' => 'HTTP 500'],
        ]));

    $this->app->instance(ScraperHealthChecker::class, $mock);

    $this->artisan('jobs:check-scrapers-health')
        ->assertFailed();
});

it('handles empty results when no providers to check', function () {
    $mock = Mockery::mock(ScraperHealthChecker::class);
    $mock->shouldReceive('checkAll')
        ->once()
        ->andReturn(collect());

    $this->app->instance(ScraperHealthChecker::class, $mock);

    $this->artisan('jobs:check-scrapers-health')
        ->assertSuccessful();
});
