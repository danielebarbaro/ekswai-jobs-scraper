<?php

declare(strict_types=1);

use App\Domain\Company\JobBoardProvider;
use App\Infrastructure\Services\Contracts\JobBoardClient;
use App\Infrastructure\Services\Factorial\FactorialScraper;
use App\Infrastructure\Services\JobBoardClientFactory;
use App\Infrastructure\Services\Lever\LeverHttpClient;
use App\Infrastructure\Services\Teamtailor\TeamtailorScraper;
use App\Infrastructure\Services\Workable\WorkableHttpClient;

it('returns WorkableHttpClient for Workable provider', function () {
    $factory = new JobBoardClientFactory;

    $client = $factory->make(JobBoardProvider::Workable);

    expect($client)->toBeInstanceOf(WorkableHttpClient::class)
        ->and($client)->toBeInstanceOf(JobBoardClient::class);
});

it('returns LeverHttpClient for Lever provider', function () {
    $factory = new JobBoardClientFactory;

    $client = $factory->make(JobBoardProvider::Lever);

    expect($client)->toBeInstanceOf(LeverHttpClient::class)
        ->and($client)->toBeInstanceOf(JobBoardClient::class);
});

it('returns TeamtailorScraper for Teamtailor provider', function () {
    $factory = new JobBoardClientFactory;

    $client = $factory->make(JobBoardProvider::Teamtailor);

    expect($client)->toBeInstanceOf(TeamtailorScraper::class)
        ->and($client)->toBeInstanceOf(JobBoardClient::class);
});

it('returns FactorialScraper for Factorial provider', function () {
    $factory = new JobBoardClientFactory;

    $client = $factory->make(JobBoardProvider::Factorial);

    expect($client)->toBeInstanceOf(FactorialScraper::class)
        ->and($client)->toBeInstanceOf(JobBoardClient::class);
});
