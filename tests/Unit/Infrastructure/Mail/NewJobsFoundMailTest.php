<?php

declare(strict_types=1);

use App\Domain\Company\Company;
use App\Domain\JobPosting\JobPosting;
use App\Domain\User\User;
use App\Infrastructure\Mail\NewJobsFoundMail;

it('builds the correct subject with singular job and company', function (): void {
    $user = User::factory()->create();
    $company = Company::factory()->create();
    $job = JobPosting::factory()->create(['company_id' => $company->id]);

    $jobsByCompany = collect([
        ['company' => $company, 'jobs' => collect([$job])],
    ]);

    $mail = new NewJobsFoundMail($user, $jobsByCompany);
    $envelope = $mail->envelope();

    expect($envelope->subject)->toContain('1')
        ->and($envelope->subject)->toContain('1');
});

it('builds the correct subject with multiple jobs and companies', function (): void {
    $user = User::factory()->create();
    $company1 = Company::factory()->create();
    $company2 = Company::factory()->create();
    $jobs1 = JobPosting::factory()->count(2)->create(['company_id' => $company1->id]);
    $jobs2 = JobPosting::factory()->count(3)->create(['company_id' => $company2->id]);

    $jobsByCompany = collect([
        ['company' => $company1, 'jobs' => $jobs1],
        ['company' => $company2, 'jobs' => $jobs2],
    ]);

    $mail = new NewJobsFoundMail($user, $jobsByCompany);
    $envelope = $mail->envelope();

    expect($envelope->subject)->toContain('5')
        ->and($envelope->subject)->toContain('2');
});

it('renders the email content with correct data', function (): void {
    $user = User::factory()->create();
    $company = Company::factory()->create(['name' => 'Acme Corp']);
    $job = JobPosting::factory()->create([
        'company_id' => $company->id,
        'title' => 'Senior Developer',
    ]);

    $jobsByCompany = collect([
        ['company' => $company, 'jobs' => collect([$job])],
    ]);

    $mail = new NewJobsFoundMail($user, $jobsByCompany);
    $content = $mail->content();

    expect($content->view)->toBe('emails.new-jobs-found')
        ->and($content->with)->toHaveKey('user')
        ->and($content->with)->toHaveKey('jobsByCompany')
        ->and($content->with)->toHaveKey('totalJobs', 1)
        ->and($content->with)->toHaveKey('failures');
});

it('has no attachments', function (): void {
    $user = User::factory()->create();
    $mail = new NewJobsFoundMail($user, collect());

    expect($mail->attachments())->toBeEmpty();
});

it('accepts failures collection', function (): void {
    $user = User::factory()->create();
    $failures = collect([['company_name' => 'FailCo']]);
    $mail = new NewJobsFoundMail($user, collect(), $failures);

    $content = $mail->content();

    expect($content->with['failures'])->toHaveCount(1);
});
