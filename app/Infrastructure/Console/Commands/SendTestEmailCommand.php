<?php

declare(strict_types=1);

namespace App\Infrastructure\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendTestEmailCommand extends Command
{
    protected $signature = 'mail:test {email? : The email address to send to}';

    protected $description = 'Send a test email to verify mail configuration';

    public function handle(): int
    {
        $email = $this->argument('email') ?? 'me@plincode.tech';

        $this->info("Sending test email to {$email}...");

        Mail::raw('This is a test email from ekswai. If you received this, your mail configuration is working correctly.', function ($message) use ($email): void {
            $message->to($email)
                ->subject('ekswai test email');
        });

        $this->info('Test email sent successfully.');

        return self::SUCCESS;
    }
}
