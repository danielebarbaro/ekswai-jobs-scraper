<?php

declare(strict_types=1);

use App\Domain\Company\Company;
use App\Domain\ScraperConfig\ScraperConfig;
use App\Domain\User\User;
use App\Infrastructure\Mail\ScraperHealthAlertMail;
use App\Infrastructure\Services\Scraping\ScraperHealthChecker;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

beforeEach(function (): void {
    Mail::fake();
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->user = User::factory()->create(['is_admin' => false]);
});

it('marks health check as passed when selector is found', function (): void {
    $config = ScraperConfig::factory()->create(['provider' => 'teamtailor']);
    Company::factory()->teamtailor()->create(['provider_slug' => 'test-co']);

    $html = '<html><body><ul id="jobs_list_container"><li>Job</li></ul></body></html>';
    Http::fake(['https://test-co.teamtailor.com/jobs' => Http::response($html, 200)]);

    $checker = app(ScraperHealthChecker::class);
    $results = $checker->checkAll();

    $config->refresh();
    expect($config->last_health_check_passed)->toBeTrue()
        ->and($config->last_health_check_at)->not->toBeNull();

    Mail::assertNothingSent();
});

it('marks health check as failed and sends alert when selector not found', function (): void {
    $config = ScraperConfig::factory()->create(['provider' => 'teamtailor']);
    Company::factory()->teamtailor()->create(['provider_slug' => 'test-co']);

    $html = '<html><body><p>Page redesigned</p></body></html>';
    Http::fake(['https://test-co.teamtailor.com/jobs' => Http::response($html, 200)]);

    $checker = app(ScraperHealthChecker::class);
    $checker->checkAll();

    $config->refresh();
    expect($config->last_health_check_passed)->toBeFalse();

    Mail::assertQueued(ScraperHealthAlertMail::class, fn ($mail) => $mail->hasTo($this->admin->email));
});

it('does not send alert to non-admin users', function (): void {
    ScraperConfig::factory()->create(['provider' => 'teamtailor']);
    Company::factory()->teamtailor()->create(['provider_slug' => 'test-co']);

    $html = '<html><body><p>Changed</p></body></html>';
    Http::fake(['https://test-co.teamtailor.com/jobs' => Http::response($html, 200)]);

    $checker = app(ScraperHealthChecker::class);
    $checker->checkAll();

    Mail::assertNotQueued(ScraperHealthAlertMail::class, fn ($mail) => $mail->hasTo($this->user->email));
});

it('skips providers with no active companies', function (): void {
    ScraperConfig::factory()->create(['provider' => 'teamtailor']);
    // No company with teamtailor provider

    $checker = app(ScraperHealthChecker::class);
    $results = $checker->checkAll();

    expect($results)->toBeEmpty();
    Http::assertNothingSent();
});

it('skips inactive scraper configs', function (): void {
    ScraperConfig::factory()->inactive()->create(['provider' => 'teamtailor']);
    Company::factory()->teamtailor()->create(['provider_slug' => 'test-co']);

    $checker = app(ScraperHealthChecker::class);
    $results = $checker->checkAll();

    expect($results)->toBeEmpty();
    Http::assertNothingSent();
});

it('handles http errors gracefully and marks as failed', function (): void {
    $config = ScraperConfig::factory()->create([
        'provider' => 'teamtailor',
        'retry_attempts' => 0,
        'retry_delay_seconds' => 0,
    ]);
    Company::factory()->teamtailor()->create(['provider_slug' => 'test-co']);

    Http::fake(['https://test-co.teamtailor.com/jobs' => Http::response('Error', 500)]);

    $checker = app(ScraperHealthChecker::class);
    $checker->checkAll();

    $config->refresh();
    expect($config->last_health_check_passed)->toBeFalse();

    Mail::assertQueued(ScraperHealthAlertMail::class);
});
