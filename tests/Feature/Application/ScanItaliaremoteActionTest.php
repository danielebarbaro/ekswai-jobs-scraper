<?php

declare(strict_types=1);

use App\Application\Actions\Company\ScanItaliaremoteAction;
use App\Application\Actions\JobPosting\SyncCompanyJobPostingsAction;
use App\Application\DTOs\ScanItaliaremoteSummary;
use App\Domain\Company\Company;
use App\Domain\Company\JobBoardProvider;
use App\Infrastructure\Services\Contracts\JobBoardClient;
use App\Infrastructure\Services\JobBoardClientFactory;
use Illuminate\Support\Facades\Http;

const ITALIAREMOTE_JSON_URL = 'https://raw.githubusercontent.com/italiaremote/awesome-italia-remote/refs/heads/main/outputs.json';

beforeEach(function (): void {
    $this->mockClient = Mockery::mock(JobBoardClient::class);
    $this->mockFactory = Mockery::mock(JobBoardClientFactory::class);
    $this->app->instance(JobBoardClientFactory::class, $this->mockFactory);

    $this->syncMock = Mockery::mock(SyncCompanyJobPostingsAction::class);
    $this->app->instance(SyncCompanyJobPostingsAction::class, $this->syncMock);

    $this->action = $this->app->make(ScanItaliaremoteAction::class);
});

it('creates a company for a new matching entry', function (): void {
    $this->mockFactory
        ->shouldReceive('make')
        ->with(JobBoardProvider::Workable)
        ->andReturn($this->mockClient);

    $this->mockClient->shouldReceive('validateSlug')->with('newco')->andReturn('New Co');
    $this->mockClient->shouldReceive('fetchCompanyDescription')->with('newco')->andReturn(null);

    $this->syncMock->shouldReceive('execute')->once()->andReturn(collect());

    Http::fake([
        ITALIAREMOTE_JSON_URL => Http::response([
            ['name' => 'New Co', 'career_page_url' => 'https://apply.workable.com/newco'],
        ], 200),
    ]);

    $summary = $this->action->execute();

    expect($summary)->toBeInstanceOf(ScanItaliaremoteSummary::class)
        ->and($summary->total)->toBe(1)
        ->and($summary->matched)->toBe(1)
        ->and($summary->created)->toBe(1)
        ->and($summary->skipped)->toBe(0)
        ->and($summary->failed)->toBe(0)
        ->and(Company::query()->where('provider_slug', 'newco')->exists())->toBeTrue();
});

it('skips an entry whose provider+slug already exists', function (): void {
    Company::factory()->create([
        'provider' => JobBoardProvider::Workable,
        'provider_slug' => 'existingco',
        'name' => 'Existing Co',
    ]);

    $this->syncMock->shouldNotReceive('execute');

    Http::fake([
        ITALIAREMOTE_JSON_URL => Http::response([
            ['name' => 'Existing Co', 'career_page_url' => 'https://apply.workable.com/existingco'],
        ], 200),
    ]);

    $summary = $this->action->execute();

    expect($summary->total)->toBe(1)
        ->and($summary->matched)->toBe(1)
        ->and($summary->created)->toBe(0)
        ->and($summary->skipped)->toBe(1)
        ->and($summary->failed)->toBe(0);
});

it('ignores entries without a career_page_url', function (): void {
    $this->syncMock->shouldNotReceive('execute');

    Http::fake([
        ITALIAREMOTE_JSON_URL => Http::response([
            ['name' => 'No URL Co'],
        ], 200),
    ]);

    $summary = $this->action->execute();

    expect($summary->total)->toBe(1)
        ->and($summary->matched)->toBe(0)
        ->and($summary->created)->toBe(0);
});

it('ignores entries with an unsupported career_page_url', function (): void {
    $this->syncMock->shouldNotReceive('execute');

    Http::fake([
        ITALIAREMOTE_JSON_URL => Http::response([
            ['name' => 'Bespoke Co', 'career_page_url' => 'https://careers.bespoke.io/jobs'],
        ], 200),
    ]);

    $summary = $this->action->execute();

    expect($summary->total)->toBe(1)
        ->and($summary->matched)->toBe(0)
        ->and($summary->created)->toBe(0);
});

it('counts a failed validation as failed and does not create a company', function (): void {
    $this->mockFactory
        ->shouldReceive('make')
        ->with(JobBoardProvider::Workable)
        ->andReturn($this->mockClient);

    $this->mockClient->shouldReceive('validateSlug')->with('gone')->andReturn(null);

    $this->syncMock->shouldNotReceive('execute');

    Http::fake([
        ITALIAREMOTE_JSON_URL => Http::response([
            ['name' => 'Gone Co', 'career_page_url' => 'https://apply.workable.com/gone'],
        ], 200),
    ]);

    $summary = $this->action->execute();

    expect($summary->total)->toBe(1)
        ->and($summary->matched)->toBe(1)
        ->and($summary->created)->toBe(0)
        ->and($summary->skipped)->toBe(0)
        ->and($summary->failed)->toBe(1)
        ->and(Company::query()->where('provider_slug', 'gone')->exists())->toBeFalse();
});

it('auto-sync runs for newly created companies and is skipped for existing ones', function (): void {
    Company::factory()->create([
        'provider' => JobBoardProvider::Workable,
        'provider_slug' => 'existingco',
        'name' => 'Existing Co',
    ]);

    $this->mockFactory
        ->shouldReceive('make')
        ->with(JobBoardProvider::Workable)
        ->andReturn($this->mockClient);

    $this->mockClient->shouldReceive('validateSlug')->with('newco')->andReturn('New Co');
    $this->mockClient->shouldReceive('fetchCompanyDescription')->with('newco')->andReturn(null);

    $this->syncMock->shouldReceive('execute')
        ->once()
        ->withArgs(fn (Company $c): bool => $c->provider_slug === 'newco')
        ->andReturn(collect());

    Http::fake([
        ITALIAREMOTE_JSON_URL => Http::response([
            ['name' => 'Existing Co', 'career_page_url' => 'https://apply.workable.com/existingco'],
            ['name' => 'New Co', 'career_page_url' => 'https://apply.workable.com/newco'],
        ], 200),
    ]);

    $summary = $this->action->execute();

    expect($summary->total)->toBe(2)
        ->and($summary->matched)->toBe(2)
        ->and($summary->created)->toBe(1)
        ->and($summary->skipped)->toBe(1)
        ->and($summary->failed)->toBe(0);
});
