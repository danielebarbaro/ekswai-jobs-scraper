<?php

declare(strict_types=1);

use App\Application\Actions\Company\LoadDemoCompaniesAction;
use App\Application\Actions\JobPosting\SyncCompanyJobPostingsAction;
use App\Domain\User\User;

it('subscribes user to demo companies', function () {
    $syncMock = Mockery::mock(SyncCompanyJobPostingsAction::class);
    $syncMock->shouldReceive('execute')->andReturn(collect());

    $this->app->instance(SyncCompanyJobPostingsAction::class, $syncMock);

    $user = User::factory()->create();
    $action = app(LoadDemoCompaniesAction::class);

    $subscribed = $action->execute($user);

    expect($subscribed)->toBe(9)
        ->and($user->subscribedCompanies()->count())->toBe(9);
});

it('skips already subscribed companies', function () {
    $syncMock = Mockery::mock(SyncCompanyJobPostingsAction::class);
    $syncMock->shouldReceive('execute')->andReturn(collect());

    $this->app->instance(SyncCompanyJobPostingsAction::class, $syncMock);

    $user = User::factory()->create();
    $action = app(LoadDemoCompaniesAction::class);

    $action->execute($user);
    $secondRun = $action->execute($user);

    expect($secondRun)->toBe(0)
        ->and($user->subscribedCompanies()->count())->toBe(9);
});

it('handles sync failure gracefully', function () {
    $syncMock = Mockery::mock(SyncCompanyJobPostingsAction::class);
    $syncMock->shouldReceive('execute')->andThrow(new RuntimeException('API down'));

    $this->app->instance(SyncCompanyJobPostingsAction::class, $syncMock);

    $user = User::factory()->create();
    $action = app(LoadDemoCompaniesAction::class);

    $subscribed = $action->execute($user);

    expect($subscribed)->toBe(9);
});
