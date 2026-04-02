<?php

declare(strict_types=1);

use App\Application\DTOs\JobPostingDTO;
use App\Infrastructure\Services\Ashby\AshbyHttpClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    $this->client = new AshbyHttpClient;
});

it('fetches jobs for a valid company slug', function (): void {
    Http::fake([
        'api.ashbyhq.com/posting-api/job-board/testco' => Http::response([
            'jobs' => [
                [
                    'id' => 'abc-123',
                    'title' => 'Backend Engineer',
                    'location' => 'Paris',
                    'department' => 'Engineering',
                    'jobUrl' => 'https://jobs.ashbyhq.com/testco/abc-123',
                    'isRemote' => false,
                ],
                [
                    'id' => 'def-456',
                    'title' => 'Frontend Engineer',
                    'location' => 'Amsterdam',
                    'department' => 'Design',
                    'jobUrl' => 'https://jobs.ashbyhq.com/testco/def-456',
                    'isRemote' => false,
                ],
            ],
        ]),
    ]);

    $jobs = $this->client->fetchJobsForCompany('testco');

    expect($jobs)->toHaveCount(2)
        ->and($jobs->first())->toBeInstanceOf(JobPostingDTO::class)
        ->and($jobs->first()->externalId)->toBe('abc-123')
        ->and($jobs->first()->title)->toBe('Backend Engineer')
        ->and($jobs->first()->location)->toBe('Paris')
        ->and($jobs->first()->url)->toBe('https://jobs.ashbyhq.com/testco/abc-123')
        ->and($jobs->first()->department)->toBe('Engineering');
});

it('returns empty collection for empty jobs array', function (): void {
    Http::fake([
        'api.ashbyhq.com/posting-api/job-board/testco' => Http::response(['jobs' => []]),
    ]);

    $jobs = $this->client->fetchJobsForCompany('testco');

    expect($jobs)->toBeEmpty();
});

it('returns empty collection on failed http response', function (): void {
    Http::fake([
        'api.ashbyhq.com/posting-api/job-board/broken' => Http::response('Server Error', 500),
    ]);

    $jobs = $this->client->fetchJobsForCompany('broken');

    expect($jobs)->toBeEmpty();
});

it('returns empty collection on connection error', function (): void {
    Http::fake([
        'api.ashbyhq.com/posting-api/job-board/timeout' => fn () => throw new ConnectionException('Connection timed out'),
    ]);

    $jobs = $this->client->fetchJobsForCompany('timeout');

    expect($jobs)->toBeEmpty();
});

it('returns empty collection when jobs key is missing', function (): void {
    Http::fake([
        'api.ashbyhq.com/posting-api/job-board/invalid' => Http::response(['error' => 'not found']),
    ]);

    $jobs = $this->client->fetchJobsForCompany('invalid');

    expect($jobs)->toBeEmpty();
});

it('appends remote to location when isRemote is true and location exists', function (): void {
    Http::fake([
        'api.ashbyhq.com/posting-api/job-board/testco' => Http::response([
            'jobs' => [
                [
                    'id' => 'abc-123',
                    'title' => 'Engineer',
                    'location' => 'Berlin',
                    'jobUrl' => 'https://jobs.ashbyhq.com/testco/abc-123',
                    'isRemote' => true,
                ],
            ],
        ]),
    ]);

    $jobs = $this->client->fetchJobsForCompany('testco');

    expect($jobs->first()->location)->toBe('Berlin (Remote)');
});

it('sets location to remote when isRemote is true and no location', function (): void {
    Http::fake([
        'api.ashbyhq.com/posting-api/job-board/testco' => Http::response([
            'jobs' => [
                [
                    'id' => 'abc-123',
                    'title' => 'Engineer',
                    'jobUrl' => 'https://jobs.ashbyhq.com/testco/abc-123',
                    'isRemote' => true,
                ],
            ],
        ]),
    ]);

    $jobs = $this->client->fetchJobsForCompany('testco');

    expect($jobs->first()->location)->toBe('Remote');
});

it('validates a valid slug and returns company name', function (): void {
    Http::fake([
        'api.ashbyhq.com/posting-api/job-board/testco' => Http::response([
            'jobBoard' => ['title' => 'TestCo Inc'],
            'jobs' => [],
        ]),
    ]);

    $name = $this->client->validateSlug('testco');

    expect($name)->toBe('TestCo Inc');
});

it('returns null for invalid slug validation', function (): void {
    Http::fake([
        'api.ashbyhq.com/posting-api/job-board/nonexistent' => Http::response('Not Found', 404),
    ]);

    $name = $this->client->validateSlug('nonexistent');

    expect($name)->toBeNull();
});

it('returns null for slug validation on connection error', function (): void {
    Http::fake([
        'api.ashbyhq.com/posting-api/job-board/timeout' => fn () => throw new ConnectionException('Connection timed out'),
    ]);

    $name = $this->client->validateSlug('timeout');

    expect($name)->toBeNull();
});

it('returns null when validate slug response is missing jobBoard title', function (): void {
    Http::fake([
        'api.ashbyhq.com/posting-api/job-board/testco' => Http::response(['jobs' => []]),
    ]);

    $name = $this->client->validateSlug('testco');

    expect($name)->toBeNull();
});

it('stores raw payload in dto', function (): void {
    Http::fake([
        'api.ashbyhq.com/posting-api/job-board/testco' => Http::response([
            'jobs' => [
                [
                    'id' => 'abc-123',
                    'title' => 'Engineer',
                    'jobUrl' => 'https://example.com',
                    'customField' => 'value',
                ],
            ],
        ]),
    ]);

    $jobs = $this->client->fetchJobsForCompany('testco');

    expect($jobs->first()->rawPayload)->toHaveKey('customField', 'value');
});
