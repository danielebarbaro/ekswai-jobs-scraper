<?php

declare(strict_types=1);

use App\Application\Actions\Company\ScanItaliaremoteAction;
use App\Application\DTOs\ScanItaliaremoteSummary;

it('outputs summary table on successful scan', function (): void {
    $mock = Mockery::mock(ScanItaliaremoteAction::class);
    $mock->shouldReceive('execute')
        ->once()
        ->andReturn(new ScanItaliaremoteSummary(
            total: 312,
            matched: 47,
            created: 12,
            skipped: 35,
            failed: 0,
        ));

    $this->app->instance(ScanItaliaremoteAction::class, $mock);

    $this->artisan('italiaremote:scan')
        ->assertSuccessful();
});

it('outputs failure on exception', function (): void {
    $mock = Mockery::mock(ScanItaliaremoteAction::class);
    $mock->shouldReceive('execute')
        ->once()
        ->andThrow(new RuntimeException('Network error'));

    $this->app->instance(ScanItaliaremoteAction::class, $mock);

    $this->artisan('italiaremote:scan')
        ->assertFailed();
});
