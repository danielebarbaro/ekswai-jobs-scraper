<?php

declare(strict_types=1);

namespace App\Infrastructure\Mail;

use App\Domain\User\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class NewJobsFoundMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * Create a new message instance.
     *
     * @param  Collection  $jobsByCompany  Collection of ['company' => Company, 'jobs' => Collection<JobPosting>]
     * @param  Collection  $failures  Collection of ['company_name' => string]
     */
    public function __construct(
        public User $user,
        public Collection $jobsByCompany,
        public Collection $failures = new Collection
    ) {}

    public function envelope(): Envelope
    {
        $totalJobs = $this->jobsByCompany->sum(fn ($item) => $item['jobs']->count());
        $companiesCount = $this->jobsByCompany->count();

        $jobsLabel = $totalJobs === 1 ? __('emails.job_singular') : __('emails.job_plural');
        $companiesLabel = $companiesCount === 1 ? __('emails.company_singular') : __('emails.company_plural');

        $subject = sprintf('%d %s — %d %s', $totalJobs, $jobsLabel, $companiesCount, $companiesLabel);

        return new Envelope(
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.new-jobs-found',
            with: [
                'user' => $this->user,
                'jobsByCompany' => $this->jobsByCompany,
                'totalJobs' => $this->jobsByCompany->sum(fn ($item) => $item['jobs']->count()),
                'failures' => $this->failures,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
