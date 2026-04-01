<?php

declare(strict_types=1);

use App\Application\DTOs\JobPostingDTO;
use App\Infrastructure\Services\Lever\LeverHttpClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->client = new LeverHttpClient;
});

it('fetches jobs for a valid company slug', function () {
    Http::fake([
        'api.lever.co/v0/postings/scaleway' => Http::response([
            [
                'id' => 'abc-123',
                'text' => 'Backend Engineer',
                'categories' => [
                    'location' => 'Paris',
                    'department' => 'Engineering',
                ],
                'hostedUrl' => 'https://jobs.lever.co/scaleway/abc-123',
            ],
            [
                'id' => 'def-456',
                'text' => 'Frontend Engineer',
                'categories' => [
                    'location' => 'Amsterdam',
                    'department' => 'Engineering',
                ],
                'hostedUrl' => 'https://jobs.lever.co/scaleway/def-456',
            ],
        ]),
    ]);

    $jobs = $this->client->fetchJobsForCompany('scaleway');

    expect($jobs)->toHaveCount(2)
        ->and($jobs->first())->toBeInstanceOf(JobPostingDTO::class)
        ->and($jobs->first()->externalId)->toBe('abc-123')
        ->and($jobs->first()->title)->toBe('Backend Engineer')
        ->and($jobs->first()->location)->toBe('Paris')
        ->and($jobs->first()->url)->toBe('https://jobs.lever.co/scaleway/abc-123')
        ->and($jobs->first()->department)->toBe('Engineering');
});

it('returns empty collection for empty api response', function () {
    Http::fake([
        'api.lever.co/v0/postings/nonexistent' => Http::response([]),
    ]);

    $jobs = $this->client->fetchJobsForCompany('nonexistent');

    expect($jobs)->toBeEmpty();
});

it('returns empty collection on failed http response', function () {
    Http::fake([
        'api.lever.co/v0/postings/broken' => Http::response('Server Error', 500),
    ]);

    $jobs = $this->client->fetchJobsForCompany('broken');

    expect($jobs)->toBeEmpty();
});

it('returns empty collection on connection error', function () {
    Http::fake([
        'api.lever.co/v0/postings/timeout' => fn () => throw new ConnectionException('Connection timed out'),
    ]);

    $jobs = $this->client->fetchJobsForCompany('timeout');

    expect($jobs)->toBeEmpty();
});

it('maps lever fields to dto correctly including nullable fields', function () {
    Http::fake([
        'api.lever.co/v0/postings/testco' => Http::response([
            [
                'id' => 'uuid-789',
                'text' => 'Designer',
                'categories' => [],
                'hostedUrl' => 'https://jobs.lever.co/testco/uuid-789',
                'workplaceType' => 'remote',
            ],
        ]),
    ]);

    $jobs = $this->client->fetchJobsForCompany('testco');

    expect($jobs->first()->externalId)->toBe('uuid-789')
        ->and($jobs->first()->title)->toBe('Designer')
        ->and($jobs->first()->location)->toBeNull()
        ->and($jobs->first()->department)->toBeNull()
        ->and($jobs->first()->rawPayload)->toHaveKey('workplaceType', 'remote');
});

it('validates a valid slug and returns company name', function () {
    Http::fake([
        'api.lever.co/v0/postings/scaleway' => Http::response([
            [
                'id' => 'abc-123',
                'text' => 'Backend Engineer',
                'categories' => [],
                'hostedUrl' => 'https://jobs.lever.co/scaleway/abc-123',
            ],
        ]),
    ]);

    $name = $this->client->validateSlug('scaleway');

    expect($name)->toBe('Scaleway');
});

it('returns null for invalid slug validation', function () {
    Http::fake([
        'api.lever.co/v0/postings/nonexistent' => Http::response([]),
    ]);

    $name = $this->client->validateSlug('nonexistent');

    expect($name)->toBeNull();
});

it('returns null for slug validation on connection error', function () {
    Http::fake([
        'api.lever.co/v0/postings/timeout' => fn () => throw new ConnectionException('Connection timed out'),
    ]);

    $name = $this->client->validateSlug('timeout');

    expect($name)->toBeNull();
});
