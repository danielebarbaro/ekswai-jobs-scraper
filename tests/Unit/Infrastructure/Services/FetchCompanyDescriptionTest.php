<?php

declare(strict_types=1);

use App\Infrastructure\Services\Ashby\AshbyHttpClient;
use App\Infrastructure\Services\Greenhouse\GreenhouseHttpClient;
use App\Infrastructure\Services\Lever\LeverHttpClient;
use App\Infrastructure\Services\Workable\WorkableHttpClient;
use Illuminate\Support\Facades\Http;

it('workable fetches description from api response', function (): void {
    Http::fake([
        'apply.workable.com/api/v1/widget/accounts/test-co' => Http::response([
            'name' => 'Test Co',
            'description' => 'We build great software.',
            'jobs' => [],
        ]),
    ]);

    $client = new WorkableHttpClient;
    $description = $client->fetchCompanyDescription('test-co');

    expect($description)->toBe('We build great software.');
});

it('workable returns null when no description in response', function (): void {
    Http::fake([
        'apply.workable.com/api/v1/widget/accounts/no-desc' => Http::response([
            'name' => 'No Desc Co',
            'jobs' => [],
        ]),
    ]);

    $client = new WorkableHttpClient;
    $description = $client->fetchCompanyDescription('no-desc');

    expect($description)->toBeNull();
});

it('lever fetches description from careers page meta', function (): void {
    Http::fake([
        'jobs.lever.co/test-co' => Http::response(
            '<html><head><meta name="description" content="Join our team at Test Co"></head></html>'
        ),
    ]);

    $client = new LeverHttpClient;
    $description = $client->fetchCompanyDescription('test-co');

    expect($description)->toBe('Join our team at Test Co');
});

it('ashby fetches description from api response', function (): void {
    Http::fake([
        'api.ashbyhq.com/posting-api/job-board/test-co' => Http::response([
            'jobBoard' => [
                'title' => 'Test Co',
                'description' => 'Ashby company description.',
            ],
            'jobs' => [],
        ]),
    ]);

    $client = new AshbyHttpClient;
    $description = $client->fetchCompanyDescription('test-co');

    expect($description)->toBe('Ashby company description.');
});

it('greenhouse fetches description from board api', function (): void {
    Http::fake([
        'boards-api.greenhouse.io/v1/boards/test-co' => Http::response([
            'name' => 'Test Co',
            'content' => '<p>Greenhouse company description.</p>',
        ]),
    ]);

    $client = new GreenhouseHttpClient;
    $description = $client->fetchCompanyDescription('test-co');

    expect($description)->toBe('Greenhouse company description.');
});

it('returns null on http failure', function (): void {
    Http::fake([
        'apply.workable.com/*' => Http::response([], 500),
    ]);

    $client = new WorkableHttpClient;
    expect($client->fetchCompanyDescription('fail'))->toBeNull();
});
