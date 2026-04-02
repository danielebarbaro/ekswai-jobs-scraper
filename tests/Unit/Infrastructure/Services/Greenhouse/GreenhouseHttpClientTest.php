<?php

declare(strict_types=1);

use App\Application\DTOs\JobPostingDTO;
use App\Infrastructure\Services\Greenhouse\GreenhouseHttpClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->client = new GreenhouseHttpClient;
});

it('fetches jobs for a valid company slug', function () {
    Http::fake([
        'boards-api.greenhouse.io/v1/boards/testco/jobs' => Http::response([
            'jobs' => [
                [
                    'id' => 12345,
                    'title' => 'Backend Engineer',
                    'location' => ['name' => 'Paris'],
                    'absolute_url' => 'https://boards.greenhouse.io/testco/jobs/12345',
                ],
                [
                    'id' => 67890,
                    'title' => 'Frontend Engineer',
                    'location' => ['name' => 'Amsterdam'],
                    'absolute_url' => 'https://boards.greenhouse.io/testco/jobs/67890',
                ],
            ],
        ]),
    ]);

    $jobs = $this->client->fetchJobsForCompany('testco');

    expect($jobs)->toHaveCount(2)
        ->and($jobs->first())->toBeInstanceOf(JobPostingDTO::class)
        ->and($jobs->first()->externalId)->toBe('12345')
        ->and($jobs->first()->title)->toBe('Backend Engineer')
        ->and($jobs->first()->location)->toBe('Paris')
        ->and($jobs->first()->url)->toBe('https://boards.greenhouse.io/testco/jobs/12345')
        ->and($jobs->first()->department)->toBeNull();
});

it('returns empty collection for empty jobs array', function () {
    Http::fake([
        'boards-api.greenhouse.io/v1/boards/testco/jobs' => Http::response(['jobs' => []]),
    ]);

    $jobs = $this->client->fetchJobsForCompany('testco');

    expect($jobs)->toBeEmpty();
});

it('returns empty collection on failed http response', function () {
    Http::fake([
        'boards-api.greenhouse.io/v1/boards/broken/jobs' => Http::response('Server Error', 500),
    ]);

    $jobs = $this->client->fetchJobsForCompany('broken');

    expect($jobs)->toBeEmpty();
});

it('returns empty collection on connection error', function () {
    Http::fake([
        'boards-api.greenhouse.io/v1/boards/timeout/jobs' => fn () => throw new ConnectionException('Connection timed out'),
    ]);

    $jobs = $this->client->fetchJobsForCompany('timeout');

    expect($jobs)->toBeEmpty();
});

it('returns empty collection when jobs key is missing', function () {
    Http::fake([
        'boards-api.greenhouse.io/v1/boards/invalid/jobs' => Http::response(['error' => 'not found']),
    ]);

    $jobs = $this->client->fetchJobsForCompany('invalid');

    expect($jobs)->toBeEmpty();
});

it('handles missing location gracefully', function () {
    Http::fake([
        'boards-api.greenhouse.io/v1/boards/testco/jobs' => Http::response([
            'jobs' => [
                [
                    'id' => 12345,
                    'title' => 'Remote Engineer',
                    'location' => [],
                    'absolute_url' => 'https://boards.greenhouse.io/testco/jobs/12345',
                ],
            ],
        ]),
    ]);

    $jobs = $this->client->fetchJobsForCompany('testco');

    expect($jobs->first()->location)->toBeNull();
});

it('validates a valid slug and returns company name from jobs', function () {
    Http::fake([
        'boards-api.greenhouse.io/v1/boards/testco/jobs' => Http::response([
            'jobs' => [
                [
                    'id' => 12345,
                    'title' => 'Engineer',
                    'company_name' => 'TestCo Inc',
                    'location' => ['name' => 'Paris'],
                    'absolute_url' => 'https://boards.greenhouse.io/testco/jobs/12345',
                ],
            ],
        ]),
    ]);

    $name = $this->client->validateSlug('testco');

    expect($name)->toBe('TestCo Inc');
});

it('returns slug when jobs exist but company_name is missing', function () {
    Http::fake([
        'boards-api.greenhouse.io/v1/boards/testco/jobs' => Http::response([
            'jobs' => [
                [
                    'id' => 12345,
                    'title' => 'Engineer',
                    'location' => ['name' => 'Paris'],
                    'absolute_url' => 'https://boards.greenhouse.io/testco/jobs/12345',
                ],
            ],
        ]),
    ]);

    $name = $this->client->validateSlug('testco');

    expect($name)->toBe('testco');
});

it('returns slug when validate slug returns empty jobs array', function () {
    Http::fake([
        'boards-api.greenhouse.io/v1/boards/testco/jobs' => Http::response(['jobs' => []]),
    ]);

    $name = $this->client->validateSlug('testco');

    expect($name)->toBe('testco');
});

it('returns null for invalid slug validation', function () {
    Http::fake([
        'boards-api.greenhouse.io/v1/boards/nonexistent/jobs' => Http::response('Not Found', 404),
    ]);

    $name = $this->client->validateSlug('nonexistent');

    expect($name)->toBeNull();
});

it('returns null for slug validation on connection error', function () {
    Http::fake([
        'boards-api.greenhouse.io/v1/boards/timeout/jobs' => fn () => throw new ConnectionException('Connection timed out'),
    ]);

    $name = $this->client->validateSlug('timeout');

    expect($name)->toBeNull();
});

it('stores raw payload in dto', function () {
    Http::fake([
        'boards-api.greenhouse.io/v1/boards/testco/jobs' => Http::response([
            'jobs' => [
                [
                    'id' => 12345,
                    'title' => 'Engineer',
                    'location' => ['name' => 'Berlin'],
                    'absolute_url' => 'https://example.com',
                    'metadata' => ['key' => 'value'],
                ],
            ],
        ]),
    ]);

    $jobs = $this->client->fetchJobsForCompany('testco');

    expect($jobs->first()->rawPayload)->toHaveKey('metadata');
});
