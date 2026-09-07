<?php

declare(strict_types=1);

use App\Application\Actions\Company\LoadDemoCompaniesAction;
use App\Application\Actions\JobPosting\SyncCompanyJobPostingsAction;
use App\Application\Services\DefaultCompanyList;
use App\Domain\User\User;

it('subscribes user to demo companies', function (): void {
    $syncMock = Mockery::mock(SyncCompanyJobPostingsAction::class);
    $syncMock->shouldReceive('execute')->andReturn(collect());

    $this->app->instance(SyncCompanyJobPostingsAction::class, $syncMock);

    $user = User::factory()->create();
    $action = app(LoadDemoCompaniesAction::class);

    $subscribed = $action->execute($user);

    $expectedCount = count(DefaultCompanyList::all());

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
        ->and($user->subscribedCompanies()->count())->toBe(count(DefaultCompanyList::all()));
});

it('handles sync failure gracefully', function (): void {
    $syncMock = Mockery::mock(SyncCompanyJobPostingsAction::class);
    $syncMock->shouldReceive('execute')->andThrow(new RuntimeException('API down'));

    $this->app->instance(SyncCompanyJobPostingsAction::class, $syncMock);

    $user = User::factory()->create();
    $action = app(LoadDemoCompaniesAction::class);

    $subscribed = $action->execute($user);

    expect($subscribed)->toBe(count(DefaultCompanyList::all()));
});
