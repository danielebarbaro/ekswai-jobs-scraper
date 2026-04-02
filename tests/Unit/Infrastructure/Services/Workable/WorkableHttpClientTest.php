<?php

declare(strict_types=1);

use App\Application\DTOs\JobPostingDTO;
use App\Infrastructure\Services\Workable\WorkableHttpClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->client = new WorkableHttpClient;
});

it('fetches jobs for a valid company slug', function () {
    Http::fake([
        'apply.workable.com/api/v1/widget/accounts/testco' => Http::response([
            'jobs' => [
                [
                    'shortcode' => 'ABC123',
                    'title' => 'Backend Engineer',
                    'city' => 'Paris',
                    'country' => 'France',
                    'department' => 'Engineering',
                    'url' => 'https://apply.workable.com/testco/j/ABC123',
                ],
                [
                    'shortcode' => 'DEF456',
                    'title' => 'Frontend Engineer',
                    'city' => 'Amsterdam',
                    'country' => 'Netherlands',
                    'department' => 'Design',
                    'url' => 'https://apply.workable.com/testco/j/DEF456',
                ],
            ],
        ]),
    ]);

    $jobs = $this->client->fetchJobsForCompany('testco');

    expect($jobs)->toHaveCount(2)
        ->and($jobs->first())->toBeInstanceOf(JobPostingDTO::class)
        ->and($jobs->first()->externalId)->toBe('ABC123')
        ->and($jobs->first()->title)->toBe('Backend Engineer')
        ->and($jobs->first()->location)->toBe('Paris, France')
        ->and($jobs->first()->url)->toBe('https://apply.workable.com/testco/j/ABC123')
        ->and($jobs->first()->department)->toBe('Engineering');
});

it('returns empty collection for empty jobs array', function () {
    Http::fake([
        'apply.workable.com/api/v1/widget/accounts/testco' => Http::response(['jobs' => []]),
    ]);

    $jobs = $this->client->fetchJobsForCompany('testco');

    expect($jobs)->toBeEmpty();
});

it('returns empty collection on failed http response', function () {
    Http::fake([
        'apply.workable.com/api/v1/widget/accounts/broken' => Http::response('Server Error', 500),
    ]);

    $jobs = $this->client->fetchJobsForCompany('broken');

    expect($jobs)->toBeEmpty();
});

it('returns empty collection on connection error', function () {
    Http::fake([
        'apply.workable.com/api/v1/widget/accounts/timeout' => fn () => throw new ConnectionException('Connection timed out'),
    ]);

    $jobs = $this->client->fetchJobsForCompany('timeout');

    expect($jobs)->toBeEmpty();
});

it('returns empty collection when jobs key is missing', function () {
    Http::fake([
        'apply.workable.com/api/v1/widget/accounts/invalid' => Http::response(['error' => 'not found']),
    ]);

    $jobs = $this->client->fetchJobsForCompany('invalid');

    expect($jobs)->toBeEmpty();
});

it('handles location with only city', function () {
    Http::fake([
        'apply.workable.com/api/v1/widget/accounts/testco' => Http::response([
            'jobs' => [
                [
                    'shortcode' => 'ABC123',
                    'title' => 'Engineer',
                    'city' => 'Berlin',
                    'url' => 'https://example.com',
                ],
            ],
        ]),
    ]);

    $jobs = $this->client->fetchJobsForCompany('testco');

    expect($jobs->first()->location)->toBe('Berlin');
});

it('handles location with only country', function () {
    Http::fake([
        'apply.workable.com/api/v1/widget/accounts/testco' => Http::response([
            'jobs' => [
                [
                    'shortcode' => 'ABC123',
                    'title' => 'Engineer',
                    'country' => 'Germany',
                    'url' => 'https://example.com',
                ],
            ],
        ]),
    ]);

    $jobs = $this->client->fetchJobsForCompany('testco');

    expect($jobs->first()->location)->toBe('Germany');
});

it('handles missing location fields', function () {
    Http::fake([
        'apply.workable.com/api/v1/widget/accounts/testco' => Http::response([
            'jobs' => [
                [
                    'shortcode' => 'ABC123',
                    'title' => 'Remote Engineer',
                    'url' => 'https://example.com',
                ],
            ],
        ]),
    ]);

    $jobs = $this->client->fetchJobsForCompany('testco');

    expect($jobs->first()->location)->toBeNull();
});

it('uses shortlink when url is missing', function () {
    Http::fake([
        'apply.workable.com/api/v1/widget/accounts/testco' => Http::response([
            'jobs' => [
                [
                    'shortcode' => 'ABC123',
                    'title' => 'Engineer',
                    'shortlink' => 'https://wkbl.co/ABC123',
                ],
            ],
        ]),
    ]);

    $jobs = $this->client->fetchJobsForCompany('testco');

    expect($jobs->first()->url)->toBe('https://wkbl.co/ABC123');
});

it('validates a valid slug and returns company name', function () {
    Http::fake([
        'apply.workable.com/api/v1/widget/accounts/testco' => Http::response([
            'name' => 'TestCo Inc',
            'jobs' => [],
        ]),
    ]);

    $name = $this->client->validateSlug('testco');

    expect($name)->toBe('TestCo Inc');
});

it('returns null for invalid slug validation', function () {
    Http::fake([
        'apply.workable.com/api/v1/widget/accounts/nonexistent' => Http::response('Not Found', 404),
    ]);

    $name = $this->client->validateSlug('nonexistent');

    expect($name)->toBeNull();
});

it('returns null for slug validation on connection error', function () {
    Http::fake([
        'apply.workable.com/api/v1/widget/accounts/timeout' => fn () => throw new ConnectionException('Connection timed out'),
    ]);

    $name = $this->client->validateSlug('timeout');

    expect($name)->toBeNull();
});

it('returns null when validate slug response is missing name', function () {
    Http::fake([
        'apply.workable.com/api/v1/widget/accounts/testco' => Http::response(['jobs' => []]),
    ]);

    $name = $this->client->validateSlug('testco');

    expect($name)->toBeNull();
});
