<?php

declare(strict_types=1);

use App\Infrastructure\Mail\ScraperHealthAlertMail;

it('builds the correct subject with singular failure', function (): void {
    $failures = collect([
        ['provider' => 'teamtailor', 'error' => 'timeout'],
    ]);

    $mail = new ScraperHealthAlertMail($failures);
    $envelope = $mail->envelope();

    expect($envelope->subject)->toBe('Scraper Health Alert: 1 provider failing');
});

it('builds the correct subject with multiple failures', function (): void {
    $failures = collect([
        ['provider' => 'teamtailor', 'error' => 'timeout'],
        ['provider' => 'factorial', 'error' => 'selector not found'],
    ]);

    $mail = new ScraperHealthAlertMail($failures);
    $envelope = $mail->envelope();

    expect($envelope->subject)->toBe('Scraper Health Alert: 2 providers failing');
});

it('renders the email content with failures data', function (): void {
    $failures = collect([
        ['provider' => 'teamtailor', 'error' => 'timeout'],
    ]);

    $mail = new ScraperHealthAlertMail($failures);
    $content = $mail->content();

    expect($content->view)->toBe('emails.scraper-health-alert')
        ->and($content->with)->toHaveKey('failures');
});

it('has no attachments', function (): void {
    $mail = new ScraperHealthAlertMail(collect());

    expect($mail->attachments())->toBeEmpty();
});
