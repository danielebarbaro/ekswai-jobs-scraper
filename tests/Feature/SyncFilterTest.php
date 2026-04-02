<?php

declare(strict_types=1);

use App\Application\Actions\Sync\RunDailySyncAction;
use App\Application\DTOs\JobPostingDTO;
use App\Domain\Company\Company;
use App\Domain\JobFilter\JobFilter;
use App\Domain\User\User;
use App\Infrastructure\Mail\NewJobsFoundMail;
use App\Infrastructure\Services\Contracts\JobBoardClient;
use App\Infrastructure\Services\JobBoardClientFactory;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
});

it('filters notification jobs based on user global filter', function () {
    $user = User::factory()->create();
    $company = Company::factory()->create();
    $user->subscribedCompanies()->attach($company->id, ['email_notifications' => true]);

    JobFilter::factory()->create([
        'user_id' => $user->id,
        'title_exclude' => ['VP'],
    ]);

    $mockClient = Mockery::mock(JobBoardClient::class);
    $mockClient->shouldReceive('fetchJobsForCompany')
        ->andReturn(collect([
            new JobPostingDTO('1', 'Senior Engineer', 'Berlin', 'http://example.com/1', 'Engineering', []),
            new JobPostingDTO('2', 'VP of Sales', 'Berlin', 'http://example.com/2', 'Sales', []),
        ]));

    $mockFactory = Mockery::mock(JobBoardClientFactory::class);
    $mockFactory->shouldReceive('make')->andReturn($mockClient);
    $this->app->instance(JobBoardClientFactory::class, $mockFactory);

    app(RunDailySyncAction::class)->execute();

    Mail::assertQueued(NewJobsFoundMail::class, function (NewJobsFoundMail $mail) {
        $totalJobs = $mail->jobsByCompany->sum(fn ($item) => $item['jobs']->count());

        return $totalJobs === 1;
    });
});

it('sends all jobs when user has no filter', function () {
    $user = User::factory()->create();
    $company = Company::factory()->create();
    $user->subscribedCompanies()->attach($company->id, ['email_notifications' => true]);

    $mockClient = Mockery::mock(JobBoardClient::class);
    $mockClient->shouldReceive('fetchJobsForCompany')
        ->andReturn(collect([
            new JobPostingDTO('1', 'Senior Engineer', 'Berlin', 'http://example.com/1', 'Engineering', []),
            new JobPostingDTO('2', 'VP of Sales', 'Berlin', 'http://example.com/2', 'Sales', []),
        ]));

    $mockFactory = Mockery::mock(JobBoardClientFactory::class);
    $mockFactory->shouldReceive('make')->andReturn($mockClient);
    $this->app->instance(JobBoardClientFactory::class, $mockFactory);

    app(RunDailySyncAction::class)->execute();

    Mail::assertQueued(NewJobsFoundMail::class, function (NewJobsFoundMail $mail) {
        $totalJobs = $mail->jobsByCompany->sum(fn ($item) => $item['jobs']->count());

        return $totalJobs === 2;
    });
});

it('skips notification entirely when all jobs are filtered out', function () {
    $user = User::factory()->create();
    $company = Company::factory()->create();
    $user->subscribedCompanies()->attach($company->id, ['email_notifications' => true]);

    JobFilter::factory()->create([
        'user_id' => $user->id,
        'title_include' => ['Accountant'],
    ]);

    $mockClient = Mockery::mock(JobBoardClient::class);
    $mockClient->shouldReceive('fetchJobsForCompany')
        ->andReturn(collect([
            new JobPostingDTO('1', 'Senior Engineer', 'Berlin', 'http://example.com/1', 'Engineering', []),
        ]));

    $mockFactory = Mockery::mock(JobBoardClientFactory::class);
    $mockFactory->shouldReceive('make')->andReturn($mockClient);
    $this->app->instance(JobBoardClientFactory::class, $mockFactory);

    app(RunDailySyncAction::class)->execute();

    Mail::assertNotQueued(NewJobsFoundMail::class);
});
