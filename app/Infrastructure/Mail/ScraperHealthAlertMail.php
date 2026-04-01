<?php

declare(strict_types=1);

namespace App\Infrastructure\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class ScraperHealthAlertMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Collection $failures
    ) {}

    public function envelope(): Envelope
    {
        $count = $this->failures->count();

        return new Envelope(
            subject: sprintf(
                'Scraper Health Alert: %d provider%s failing',
                $count,
                $count === 1 ? '' : 's'
            ),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.scraper-health-alert',
            with: ['failures' => $this->failures],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
