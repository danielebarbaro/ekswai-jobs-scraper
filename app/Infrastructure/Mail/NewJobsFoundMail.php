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
     */
    public function __construct(
        public User $user,
        public Collection $jobsByCompany
    ) {}

    public function envelope(): Envelope
    {
        $totalJobs = $this->jobsByCompany->sum(fn ($item) => $item['jobs']->count());
        $companiesCount = $this->jobsByCompany->count();

        $subject = sprintf(
            '%d New Job%s Found Across %d Compan%s',
            $totalJobs,
            $totalJobs === 1 ? '' : 's',
            $companiesCount,
            $companiesCount === 1 ? 'y' : 'ies'
        );

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
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
