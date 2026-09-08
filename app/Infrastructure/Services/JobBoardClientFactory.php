<?php

declare(strict_types=1);

namespace App\Infrastructure\Services;

use App\Domain\Company\JobBoardProvider;
use App\Infrastructure\Services\Factorial\FactorialScraper;
use App\Infrastructure\Services\Teamtailor\TeamtailorScraper;
use PlinCode\JobBoards\Ashby\AshbyClient;
use PlinCode\JobBoards\Contracts\JobBoardClient;
use PlinCode\JobBoards\Greenhouse\GreenhouseClient;
use PlinCode\JobBoards\Lever\LeverClient;
use PlinCode\JobBoards\Personio\PersonioClient;
use PlinCode\JobBoards\SmartRecruiters\SmartRecruitersClient;
use PlinCode\JobBoards\Workable\WorkableClient;

class JobBoardClientFactory
{
    public function make(JobBoardProvider $provider): JobBoardClient
    {
        return match ($provider) {
            JobBoardProvider::Workable => app(WorkableClient::class),
            JobBoardProvider::Lever => app(LeverClient::class),
            JobBoardProvider::Teamtailor => app(TeamtailorScraper::class),
            JobBoardProvider::Factorial => app(FactorialScraper::class),
            JobBoardProvider::Ashby => app(AshbyClient::class),
            JobBoardProvider::Greenhouse => app(GreenhouseClient::class),
            JobBoardProvider::Personio => app(PersonioClient::class),
            JobBoardProvider::SmartRecruiters => app(SmartRecruitersClient::class),
        };
    }
}
