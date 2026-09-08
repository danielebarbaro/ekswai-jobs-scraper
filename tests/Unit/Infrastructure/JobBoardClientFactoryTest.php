<?php

declare(strict_types=1);

use App\Domain\Company\JobBoardProvider;
use App\Infrastructure\Services\Factorial\FactorialScraper;
use App\Infrastructure\Services\JobBoardClientFactory;
use App\Infrastructure\Services\Teamtailor\TeamtailorScraper;
use PlinCode\JobBoards\Ashby\AshbyClient;
use PlinCode\JobBoards\Contracts\JobBoardClient;
use PlinCode\JobBoards\Greenhouse\GreenhouseClient;
use PlinCode\JobBoards\Lever\LeverClient;
use PlinCode\JobBoards\Personio\PersonioClient;
use PlinCode\JobBoards\SmartRecruiters\SmartRecruitersClient;
use PlinCode\JobBoards\Workable\WorkableClient;

/**
 * The six API providers resolve to plin-code/job-boards-* package clients. The
 * two HTML boards stay in this app because no package covers them.
 *
 * @return array<string, class-string<JobBoardClient>>
 */
function expectedClientPerProvider(): array
{
    return [
        JobBoardProvider::Workable->value => WorkableClient::class,
        JobBoardProvider::Lever->value => LeverClient::class,
        JobBoardProvider::Ashby->value => AshbyClient::class,
        JobBoardProvider::Greenhouse->value => GreenhouseClient::class,
        JobBoardProvider::Personio->value => PersonioClient::class,
        JobBoardProvider::SmartRecruiters->value => SmartRecruitersClient::class,
        JobBoardProvider::Teamtailor->value => TeamtailorScraper::class,
        JobBoardProvider::Factorial->value => FactorialScraper::class,
    ];
}

dataset('providers', fn (): array => array_map(
    fn (string $value, string $class): array => [JobBoardProvider::from($value), $class],
    array_keys(expectedClientPerProvider()),
    expectedClientPerProvider(),
));

it('resolves the right client for each provider', function (JobBoardProvider $provider, string $expected): void {
    $client = (new JobBoardClientFactory)->make($provider);

    expect($client)->toBeInstanceOf($expected)
        ->and($client)->toBeInstanceOf(JobBoardClient::class);
})->with('providers');

it('maps every provider the enum declares', function (): void {
    expect(array_keys(expectedClientPerProvider()))
        ->toEqualCanonicalizing(array_column(JobBoardProvider::cases(), 'value'));
});
