<?php

declare(strict_types=1);

use App\Domain\Company\JobBoardProvider;
use App\Domain\ScraperConfig\ScraperConfig;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;

it('casts provider to JobBoardProvider enum', function () {
    $config = ScraperConfig::factory()->create([
        'provider' => 'teamtailor',
    ]);

    expect($config->provider)->toBe(JobBoardProvider::Teamtailor);
});

it('casts selectors to array', function () {
    $selectors = ['job_list' => 'li.job', 'job_title' => 'h3'];

    $config = ScraperConfig::factory()->create([
        'selectors' => $selectors,
    ]);

    $config->refresh();
    expect($config->selectors)->toBe($selectors);
});

it('casts boolean fields correctly', function () {
    $config = ScraperConfig::factory()->create([
        'is_active' => true,
        'last_health_check_passed' => false,
    ]);

    expect($config->is_active)->toBeTrue()
        ->and($config->last_health_check_passed)->toBeFalse();
});

it('casts last_health_check_at to datetime', function () {
    $config = ScraperConfig::factory()->create([
        'last_health_check_at' => '2026-04-01 10:00:00',
    ]);

    expect($config->last_health_check_at)->toBeInstanceOf(Carbon::class);
});

it('enforces unique provider constraint', function () {
    ScraperConfig::factory()->create(['provider' => 'teamtailor']);

    ScraperConfig::factory()->create(['provider' => 'teamtailor']);
})->throws(QueryException::class);

it('scopes to active configs', function () {
    ScraperConfig::factory()->create(['provider' => 'teamtailor', 'is_active' => true]);
    ScraperConfig::factory()->create(['provider' => 'factorial', 'is_active' => false]);

    expect(ScraperConfig::query()->where('is_active', true)->count())->toBe(1);
});
