<?php

declare(strict_types=1);

use App\Domain\Company\JobBoardProvider;
use App\Infrastructure\Services\Contracts\JobBoardClient;
use App\Infrastructure\Services\JobBoardClientFactory;
use App\Infrastructure\Services\Workable\WorkableHttpClient;

it('returns WorkableHttpClient for Workable provider', function () {
    $factory = new JobBoardClientFactory;

    $client = $factory->make(JobBoardProvider::Workable);

    expect($client)->toBeInstanceOf(WorkableHttpClient::class)
        ->and($client)->toBeInstanceOf(JobBoardClient::class);
});
