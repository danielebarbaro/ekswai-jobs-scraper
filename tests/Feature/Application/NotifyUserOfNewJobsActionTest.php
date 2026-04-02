<?php

declare(strict_types=1);

use App\Application\Actions\Notification\NotifyUserOfNewJobsAction;
use App\Domain\Company\Company;
use App\Domain\JobPosting\JobPosting;
use App\Domain\User\User;
use App\Infrastructure\Mail\NewJobsFoundMail;
use Illuminate\Support\Facades\Mail;

beforeEach(function (): void {
    Mail::fake();
    $this->action = app(NotifyUserOfNewJobsAction::class);
});

it('queues email when there are new jobs', function (): void {
    $user = User::factory()->create();
    $company = Company::factory()->create();
    $job = JobPosting::factory()->create(['company_id' => $company->id]);

    $jobsByCompany = collect([
        ['company' => $company, 'jobs' => collect([$job])],
    ]);

    $this->action->execute($user, $jobsByCompany);

    Mail::assertQueued(NewJobsFoundMail::class, fn ($mail) => $mail->hasTo($user->email));
});

it('does not queue email when jobs collection is empty', function (): void {
    $user = User::factory()->create();

    $this->action->execute($user, collect());

    Mail::assertNothingQueued();
});

it('includes failures in the email', function (): void {
    $user = User::factory()->create();
    $company = Company::factory()->create();
    $job = JobPosting::factory()->create(['company_id' => $company->id]);

    $jobsByCompany = collect([
        ['company' => $company, 'jobs' => collect([$job])],
    ]);

    $failures = collect([['company_name' => 'FailCo']]);

    $this->action->execute($user, $jobsByCompany, $failures);

    Mail::assertQueued(NewJobsFoundMail::class, fn (NewJobsFoundMail $mail): bool => $mail->failures->count() === 1);
});
