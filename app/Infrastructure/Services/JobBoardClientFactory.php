<?php

declare(strict_types=1);

namespace App\Infrastructure\Services;

use App\Domain\Company\JobBoardProvider;
use App\Infrastructure\Services\Contracts\JobBoardClient;
use App\Infrastructure\Services\Lever\LeverHttpClient;
use App\Infrastructure\Services\Workable\WorkableHttpClient;

class JobBoardClientFactory
{
    public function make(JobBoardProvider $provider): JobBoardClient
    {
        return match ($provider) {
            JobBoardProvider::Workable => app(WorkableHttpClient::class),
            JobBoardProvider::Lever => app(LeverHttpClient::class),
        };
    }
}
