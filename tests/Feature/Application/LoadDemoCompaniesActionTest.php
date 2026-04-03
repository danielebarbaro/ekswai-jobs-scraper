<?php

declare(strict_types=1);

use App\Application\Actions\Company\LoadDemoCompaniesAction;
use App\Application\Actions\JobPosting\SyncCompanyJobPostingsAction;
use App\Domain\User\User;

it('subscribes user to demo companies', function (): void {
    $syncMock = Mockery::mock(SyncCompanyJobPostingsAction::class);
    $syncMock->shouldReceive('execute')->andReturn(collect());

    $this->app->instance(SyncCompanyJobPostingsAction::class, $syncMock);

    $user = User::factory()->create();
    $action = app(LoadDemoCompaniesAction::class);

    $subscribed = $action->execute($user);

    $expectedCount = count(LoadDemoCompaniesAction::DEMO_COMPANIES);

    expect($subscribed)->toBe($expectedCount)
        ->and($user->subscribedCompanies()->count())->toBe($expectedCount);
});

it('skips already subscribed companies', function (): void {
    $syncMock = Mockery::mock(SyncCompanyJobPostingsAction::class);
    $syncMock->shouldReceive('execute')->andReturn(collect());

    $this->app->instance(SyncCompanyJobPostingsAction::class, $syncMock);

    $user = User::factory()->create();
    $action = app(LoadDemoCompaniesAction::class);

    $action->execute($user);
    $secondRun = $action->execute($user);

    expect($secondRun)->toBe(0)
        ->and($user->subscribedCompanies()->count())->toBe(count(LoadDemoCompaniesAction::DEMO_COMPANIES));
});

it('handles sync failure gracefully', function (): void {
    $syncMock = Mockery::mock(SyncCompanyJobPostingsAction::class);
    $syncMock->shouldReceive('execute')->andThrow(new RuntimeException('API down'));

    $this->app->instance(SyncCompanyJobPostingsAction::class, $syncMock);

    $user = User::factory()->create();
    $action = app(LoadDemoCompaniesAction::class);

    $subscribed = $action->execute($user);

    expect($subscribed)->toBe(count(LoadDemoCompaniesAction::DEMO_COMPANIES));
});
