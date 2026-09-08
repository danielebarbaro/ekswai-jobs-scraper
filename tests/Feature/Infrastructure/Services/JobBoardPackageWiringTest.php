<?php

declare(strict_types=1);

use App\Domain\Company\JobBoardProvider;
use App\Infrastructure\Services\JobBoardClientFactory;
use PlinCode\JobBoards\Testing\FakePsrClient;
use Psr\Http\Client\ClientInterface;

/**
 * The plin-code/job-boards-* packages test their own parsing. What they cannot
 * test is this app's half of the seam: the package service providers bind the
 * PSR-18 client with bindIf, so a wrong binding here would silently send every
 * sync through the wrong transport. These tests drive a real package client
 * through the container with a faked PSR-18 client.
 */
beforeEach(function (): void {
    $this->psr = new FakePsrClient;
    $this->app->instance(ClientInterface::class, $this->psr);
});

it('fetches and maps job postings through the container-resolved client', function (): void {
    $this->psr->respondWhenJson('apply.workable.com/api/v1/widget/accounts/acme', [
        'name' => 'Acme',
        'jobs' => [
            [
                'shortcode' => 'ABC123',
                'title' => 'Backend Engineer',
                'city' => 'Berlin',
                'country' => 'Germany',
                'url' => 'https://apply.workable.com/acme/j/ABC123',
                'department' => 'Engineering',
            ],
        ],
    ]);

    $jobs = (new JobBoardClientFactory)
        ->make(JobBoardProvider::Workable)
        ->fetchJobsForCompany('acme');

    expect($jobs)->toHaveCount(1)
        ->and($jobs[0]->externalId)->toBe('ABC123')
        ->and($jobs[0]->title)->toBe('Backend Engineer')
        ->and($jobs[0]->location)->toBe('Berlin, Germany')
        ->and($jobs[0]->department)->toBe('Engineering')
        ->and($this->psr->uris())->toContain('https://apply.workable.com/api/v1/widget/accounts/acme');
});

it('resolves the company name for a valid slug', function (): void {
    $this->psr->respondWhenJson('apply.workable.com/api/v1/widget/accounts/acme', [
        'name' => 'Acme',
        'jobs' => [],
    ]);

    $client = (new JobBoardClientFactory)->make(JobBoardProvider::Workable);

    expect($client->validateSlug('acme'))->toBe('Acme');
});

it('swallows a transport failure so one dead board cannot abort a sync', function (): void {
    $this->psr->throwNetworkError();

    $jobs = (new JobBoardClientFactory)
        ->make(JobBoardProvider::Workable)
        ->fetchJobsForCompany('acme');

    expect($jobs)->toBe([]);
});
