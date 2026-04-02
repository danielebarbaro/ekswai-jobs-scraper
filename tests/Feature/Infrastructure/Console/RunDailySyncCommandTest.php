<?php

declare(strict_types=1);

use App\Application\Actions\Sync\RunDailySyncAction;

it('outputs success table on successful sync', function () {
    $mock = Mockery::mock(RunDailySyncAction::class);
    $mock->shouldReceive('execute')
        ->once()
        ->andReturn([
            'companies_synced' => 3,
            'new_jobs_found' => 10,
            'users_notified' => 2,
        ]);

    $this->app->instance(RunDailySyncAction::class, $mock);

    $this->artisan('jobs:sync-daily')
        ->assertSuccessful();
});

it('outputs failure on exception', function () {
    $mock = Mockery::mock(RunDailySyncAction::class);
    $mock->shouldReceive('execute')
        ->once()
        ->andThrow(new RuntimeException('Connection failed'));

    $this->app->instance(RunDailySyncAction::class, $mock);

    $this->artisan('jobs:sync-daily')
        ->assertFailed();
});
