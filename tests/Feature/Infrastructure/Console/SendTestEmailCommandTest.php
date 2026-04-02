<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Mail;

it('sends a test email to specified address', function () {
    Mail::fake();

    $this->artisan('mail:test', ['email' => 'test@example.com'])
        ->assertSuccessful();
});

it('runs successfully with default email', function () {
    Mail::fake();

    $this->artisan('mail:test')
        ->assertSuccessful();
});
