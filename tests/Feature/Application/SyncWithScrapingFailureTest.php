<?php

declare(strict_types=1);

use App\Application\Actions\Sync\RunDailySyncAction;
use App\Application\DTOs\JobPostingDTO;
use App\Domain\Company\Company;
use App\Domain\Company\JobBoardProvider;
use App\Domain\ScraperConfig\ScraperConfig;
use App\Domain\User\User;
use App\Infrastructure\Mail\NewJobsFoundMail;
use App\Infrastructure\Services\Contracts\JobBoardClient;
use App\Infrastructure\Services\JobBoardClientFactory;
use App\Infrastructure\Services\Scraping\Exceptions\DomStructureChangedException;
use Illuminate\Support\Facades\Mail;

beforeEach(function (): void {
    Mail::fake();
    $this->user = User::factory()->create();
});

it('collects scraping failures and includes them in user email', function (): void {
    $workableCompany = Company::factory()->create([
        'provider' => 'workable',
        'provider_slug' => 'good-co',
    ]);
    $workableCompany->subscribers()->attach($this->user->id, ['email_notifications' => true]);

    ScraperConfig::factory()->create(['provider' => 'teamtailor']);
    $ttCompany = Company::factory()->teamtailor()->create(['provider_slug' => 'broken-co']);
    $ttCompany->subscribers()->attach($this->user->id, ['email_notifications' => true]);

    $mockClient = Mockery::mock(JobBoardClient::class);
    $mockClient->shouldReceive('fetchJobsForCompany')
        ->with('good-co')
        ->andReturn(collect([
            new JobPostingDTO('j1', 'Engineer', 'Remote', 'https://example.com/j1', 'Eng', []),
        ]));

    $failingClient = Mockery::mock(JobBoardClient::class);
    $failingClient->shouldReceive('fetchJobsForCompany')
        ->with('broken-co')
        ->andThrow(new DomStructureChangedException(
            JobBoardProvider::Teamtailor, 'broken-co', 3, 'ul[data-jobs-list]', '<html>changed</html>'
        ));

    $factory = Mockery::mock(JobBoardClientFactory::class);
    $factory->shouldReceive('make')
        ->with(JobBoardProvider::Workable)
        ->andReturn($mockClient);
    $factory->shouldReceive('make')
        ->with(JobBoardProvider::Teamtailor)
        ->andReturn($failingClient);

    app()->instance(JobBoardClientFactory::class, $factory);

    $action = app(RunDailySyncAction::class);
    $stats = $action->execute();

    expect($stats['companies_synced'])->toBe(1)
        ->and($stats['companies_failed'])->toBe(1);

    Mail::assertQueued(NewJobsFoundMail::class, fn ($mail): bool => $mail->failures->isNotEmpty()
        && $mail->failures->first()['company_name'] === $ttCompany->name);
});

it('does not send email when only failures and no new jobs', function (): void {
    ScraperConfig::factory()->create(['provider' => 'teamtailor']);
    $company = Company::factory()->teamtailor()->create(['provider_slug' => 'broken-co']);
    $company->subscribers()->attach($this->user->id, ['email_notifications' => true]);

    $failingClient = Mockery::mock(JobBoardClient::class);
    $failingClient->shouldReceive('fetchJobsForCompany')
        ->andThrow(new DomStructureChangedException(
            JobBoardProvider::Teamtailor, 'broken-co', 3, 'ul[data-jobs-list]', '<html>changed</html>'
        ));

    $factory = Mockery::mock(JobBoardClientFactory::class);
    $factory->shouldReceive('make')->andReturn($failingClient);
    app()->instance(JobBoardClientFactory::class, $factory);

    $action = app(RunDailySyncAction::class);
    $action->execute();

    Mail::assertNotQueued(NewJobsFoundMail::class);
});
