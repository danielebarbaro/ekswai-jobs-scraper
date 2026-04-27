<?php

declare(strict_types=1);

use App\Application\DTOs\JobPostingDTO;
use App\Infrastructure\Services\Personio\PersonioHttpClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    $this->client = new PersonioHttpClient;
    $this->fixture = file_get_contents(base_path('tests/Fixtures/personio-koro-jobs.xml'));
});

it('fetches jobs from the personio xml feed and maps them to DTOs', function (): void {
    Http::fake([
        'koro-handels-gmbh.jobs.personio.de/xml*' => Http::response($this->fixture, 200, ['Content-Type' => 'application/xml']),
    ]);

    $jobs = $this->client->fetchJobsForCompany('koro-handels-gmbh');

    expect($jobs)->toHaveCount(2)
        ->and($jobs->first())->toBeInstanceOf(JobPostingDTO::class)
        ->and($jobs->first()->externalId)->toBe('1234567')
        ->and($jobs->first()->title)->toBe('Senior Brand Manager')
        ->and($jobs->first()->location)->toBe('Berlin')
        ->and($jobs->first()->department)->toBe('Marketing')
        ->and($jobs->first()->url)->toBe('https://koro-handels-gmbh.jobs.personio.de/job/1234567?language=en')
        ->and($jobs->first()->rawPayload)->toHaveKey('seniority')
        ->and($jobs->last()->location)->toBe('Frankfurt')
        ->and($jobs->last()->rawPayload['additionalOffices']['office'] ?? null)->toBe('Lyon');
});

it('falls back to the slug as the title when name is empty', function (): void {
    Http::fake([
        'empty-name.jobs.personio.de/xml*' => Http::response(
            '<?xml version="1.0"?><workzag-jobs><position><id>1</id><name></name><office>Berlin</office></position></workzag-jobs>',
            200,
            ['Content-Type' => 'application/xml']
        ),
    ]);

    $jobs = $this->client->fetchJobsForCompany('empty-name');

    expect($jobs)->toHaveCount(1)
        ->and($jobs->first()->title)->toBe('Untitled Position');
});

it('returns empty collection on failed http response', function (): void {
    Http::fake([
        'broken.jobs.personio.de/xml*' => Http::response('Server Error', 500),
    ]);

    expect($this->client->fetchJobsForCompany('broken'))->toBeEmpty();
});

it('returns empty collection on malformed xml', function (): void {
    Http::fake([
        'malformed.jobs.personio.de/xml*' => Http::response('<not-xml<<', 200),
    ]);

    expect($this->client->fetchJobsForCompany('malformed'))->toBeEmpty();
});

it('returns empty collection on connection error', function (): void {
    Http::fake([
        'timeout.jobs.personio.de/xml*' => fn () => throw new ConnectionException('Connection timed out'),
    ]);

    expect($this->client->fetchJobsForCompany('timeout'))->toBeEmpty();
});
