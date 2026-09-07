<?php

declare(strict_types=1);

use App\Application\DTOs\JobPostingDTO;
use App\Infrastructure\Services\SmartRecruiters\SmartRecruitersHttpClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    $this->client = new SmartRecruitersHttpClient;
    $this->fixture = file_get_contents(base_path('tests/Fixtures/smartrecruiters-aboutyou-postings.json'));
});

function srPosting(array $overrides = []): array
{
    return array_merge([
        'id' => '744000000000001',
        'name' => 'Some Job',
        'uuid' => '00000000-0000-0000-0000-000000000001',
        'refNumber' => 'REF-001',
        'company' => ['identifier' => 'ACME', 'name' => 'Acme Inc.'],
        'location' => ['city' => 'Berlin', 'region' => 'BE', 'country' => 'de', 'fullLocation' => 'Berlin, BE, Germany'],
        'department' => ['id' => '1', 'label' => 'Engineering'],
        'language' => ['code' => 'en', 'label' => 'English'],
    ], $overrides);
}

function srPage(array $content, int $totalFound, int $offset = 0, int $limit = 100): string
{
    return (string) json_encode([
        'offset' => $offset,
        'limit' => $limit,
        'totalFound' => $totalFound,
        'content' => $content,
    ]);
}

it('fetches postings and maps them to DTOs', function (): void {
    Http::fake([
        'api.smartrecruiters.com/v1/companies/ABOUTYOUGmbH/postings*' => Http::response($this->fixture, 200),
    ]);

    $jobs = $this->client->fetchJobsForCompany('ABOUTYOUGmbH');

    expect($jobs)->toHaveCount(2)
        ->and($jobs->first())->toBeInstanceOf(JobPostingDTO::class)
        ->and($jobs->first()->externalId)->toBe('ID2609-00480A')
        ->and($jobs->first()->title)->toBe('Intern Marketing & GTM Strategy (all genders)')
        ->and($jobs->first()->location)->toBe('Hamburg, HH, Germany')
        ->and($jobs->first()->department)->toBe('Business')
        ->and($jobs->first()->url)->toBe('https://jobs.smartrecruiters.com/ABOUTYOUGmbH/744000147564650')
        ->and($jobs->first()->rawPayload['uuid'])->toBe('94d0cec3-a218-4d2a-9e77-fb566d4eebd8')
        ->and($jobs->last()->externalId)->toBe('ID2608-00454A')
        ->and($jobs->last()->department)->toBe('Tech');
});

it('composes the location from city, region and country when fullLocation is absent', function (): void {
    Http::fake([
        'api.smartrecruiters.com/v1/companies/ABOUTYOUGmbH/postings*' => Http::response($this->fixture, 200),
    ]);

    $jobs = $this->client->fetchJobsForCompany('ABOUTYOUGmbH');

    expect($jobs->last()->location)->toBe('Hamburg, HH, de');
});

it('returns a null location when the posting has no usable location data', function (): void {
    Http::fake([
        'api.smartrecruiters.com/v1/companies/acme/postings*' => Http::response(
            srPage([srPosting(['location' => ['remote' => true]])], 1),
            200
        ),
    ]);

    expect($this->client->fetchJobsForCompany('acme')->first()->location)->toBeNull();
});

it('collapses language duplicates that share a refNumber and keeps the english copy', function (): void {
    Http::fake([
        'api.smartrecruiters.com/v1/companies/acme/postings*' => Http::response(srPage([
            srPosting([
                'id' => '111',
                'name' => 'Softwareentwickler (m/w/d)',
                'refNumber' => 'SHARED-REF',
                'language' => ['code' => 'de', 'label' => 'German'],
            ]),
            srPosting([
                'id' => '222',
                'name' => 'Software Engineer (m/f/d)',
                'refNumber' => 'SHARED-REF',
                'language' => ['code' => 'en-US', 'label' => 'English (US)'],
            ]),
        ], 2), 200),
    ]);

    $jobs = $this->client->fetchJobsForCompany('acme');

    expect($jobs)->toHaveCount(1)
        ->and($jobs->first()->externalId)->toBe('SHARED-REF')
        ->and($jobs->first()->title)->toBe('Software Engineer (m/f/d)')
        ->and($jobs->first()->url)->toBe('https://jobs.smartrecruiters.com/acme/222');
});

it('keeps the first copy when no language variant is english', function (): void {
    Http::fake([
        'api.smartrecruiters.com/v1/companies/acme/postings*' => Http::response(srPage([
            srPosting(['id' => '111', 'name' => 'Softwareentwickler', 'refNumber' => 'SHARED-REF', 'language' => ['code' => 'de']]),
            srPosting(['id' => '222', 'name' => 'Sviluppatore', 'refNumber' => 'SHARED-REF', 'language' => ['code' => 'it']]),
        ], 2), 200),
    ]);

    $jobs = $this->client->fetchJobsForCompany('acme');

    expect($jobs)->toHaveCount(1)
        ->and($jobs->first()->title)->toBe('Softwareentwickler');
});

it('pages through the postings endpoint until totalFound is reached', function (): void {
    Http::fakeSequence('api.smartrecruiters.com/v1/companies/acme/postings*')
        ->push(srPage([
            srPosting(['id' => '1', 'refNumber' => 'REF-1']),
            srPosting(['id' => '2', 'refNumber' => 'REF-2']),
        ], 150), 200)
        ->push(srPage([
            srPosting(['id' => '3', 'refNumber' => 'REF-3']),
        ], 150, 100), 200);

    $jobs = $this->client->fetchJobsForCompany('acme');

    expect($jobs)->toHaveCount(3)
        ->and($jobs->pluck('externalId')->all())->toBe(['REF-1', 'REF-2', 'REF-3']);

    Http::assertSentCount(2);
    Http::assertSent(fn ($request): bool => str_contains($request->url(), 'offset=100&limit=100'));
});

it('stops paging when a page comes back empty', function (): void {
    Http::fakeSequence('api.smartrecruiters.com/v1/companies/acme/postings*')
        ->push(srPage([srPosting(['id' => '1', 'refNumber' => 'REF-1'])], 500), 200)
        ->push(srPage([], 500, 100), 200);

    expect($this->client->fetchJobsForCompany('acme'))->toHaveCount(1);

    Http::assertSentCount(2);
});

it('guards against runaway pagination', function (): void {
    Http::fake([
        'api.smartrecruiters.com/v1/companies/acme/postings*' => Http::response(
            srPage([srPosting(['id' => '1', 'refNumber' => 'REF-1'])], 999_999),
            200
        ),
    ]);

    $this->client->fetchJobsForCompany('acme');

    Http::assertSentCount(50);
});

it('falls back to the posting id when refNumber is missing or empty', function (): void {
    Http::fake([
        'api.smartrecruiters.com/v1/companies/acme/postings*' => Http::response(srPage([
            srPosting(['id' => '111', 'refNumber' => null]),
            srPosting(['id' => '222', 'refNumber' => '   ']),
            srPosting(['id' => '333', 'name' => 'No ref key at all', 'refNumber' => null]),
        ], 3), 200),
    ]);

    $jobs = $this->client->fetchJobsForCompany('acme');

    expect($jobs)->toHaveCount(3)
        ->and($jobs->pluck('externalId')->all())->toBe(['111', '222', '333']);
});

it('falls back to a placeholder title when the name is empty', function (): void {
    Http::fake([
        'api.smartrecruiters.com/v1/companies/acme/postings*' => Http::response(
            srPage([srPosting(['name' => ''])], 1),
            200
        ),
    ]);

    expect($this->client->fetchJobsForCompany('acme')->first()->title)->toBe('Untitled Position');
});

it('prefers postingUrl when the payload already carries one', function (): void {
    Http::fake([
        'api.smartrecruiters.com/v1/companies/acme/postings*' => Http::response(
            srPage([srPosting(['postingUrl' => 'https://jobs.smartrecruiters.com/ACME/999-some-job'])], 1),
            200
        ),
    ]);

    expect($this->client->fetchJobsForCompany('acme')->first()->url)
        ->toBe('https://jobs.smartrecruiters.com/ACME/999-some-job');
});

it('returns an empty collection when the board has no postings', function (): void {
    Http::fake([
        'api.smartrecruiters.com/v1/companies/empty/postings*' => Http::response(srPage([], 0), 200),
    ]);

    expect($this->client->fetchJobsForCompany('empty'))->toBeEmpty();
});

it('returns an empty collection when the response has no content key', function (): void {
    Http::fake([
        'api.smartrecruiters.com/v1/companies/weird/postings*' => Http::response(['totalFound' => 3], 200),
    ]);

    expect($this->client->fetchJobsForCompany('weird'))->toBeEmpty();
});

it('returns an empty collection on a 404 response', function (): void {
    Http::fake([
        'api.smartrecruiters.com/v1/companies/gone/postings*' => Http::response('Not Found', 404),
    ]);

    expect($this->client->fetchJobsForCompany('gone'))->toBeEmpty();
});

it('returns an empty collection on a 500 response', function (): void {
    Http::fake([
        'api.smartrecruiters.com/v1/companies/broken/postings*' => Http::response('Server Error', 500),
    ]);

    expect($this->client->fetchJobsForCompany('broken'))->toBeEmpty();
});

it('returns an empty collection on a connection error', function (): void {
    Http::fake([
        'api.smartrecruiters.com/v1/companies/timeout/postings*' => fn () => throw new ConnectionException('Connection timed out'),
    ]);

    expect($this->client->fetchJobsForCompany('timeout'))->toBeEmpty();
});

it('validateSlug returns the company name', function (): void {
    Http::fake([
        'api.smartrecruiters.com/v1/companies/ABOUTYOUGmbH/postings*' => Http::response($this->fixture, 200),
    ]);

    expect($this->client->validateSlug('ABOUTYOUGmbH'))->toBe('ABOUT YOU SE & Co. KG');
});

it('validateSlug falls back to the slug when the company name is missing', function (): void {
    Http::fake([
        'api.smartrecruiters.com/v1/companies/acme/postings*' => Http::response(
            srPage([srPosting(['company' => ['identifier' => 'ACME']])], 1),
            200
        ),
    ]);

    expect($this->client->validateSlug('acme'))->toBe('acme');
});

it('validateSlug returns null on a 404 response', function (): void {
    Http::fake([
        'api.smartrecruiters.com/v1/companies/gone/postings*' => Http::response('Not Found', 404),
    ]);

    expect($this->client->validateSlug('gone'))->toBeNull();
});

it('validateSlug returns null when the API answers 200 with no postings', function (): void {
    Http::fake([
        'api.smartrecruiters.com/v1/companies/unknown/postings*' => Http::response(srPage([], 0), 200),
    ]);

    expect($this->client->validateSlug('unknown'))->toBeNull();
});

it('fetchCompanyDescription always returns null', function (): void {
    expect($this->client->fetchCompanyDescription('any-slug'))->toBeNull();
});
