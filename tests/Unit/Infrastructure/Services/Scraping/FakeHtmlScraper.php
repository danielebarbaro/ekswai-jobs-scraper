<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\Scraping;

use App\Application\DTOs\JobPostingDTO;
use App\Domain\Company\JobBoardProvider;
use App\Infrastructure\Services\Scraping\BaseHtmlScraper;
use Symfony\Component\DomCrawler\Crawler;

class FakeHtmlScraper extends BaseHtmlScraper
{
    public function getProvider(): JobBoardProvider
    {
        return JobBoardProvider::Teamtailor;
    }

    protected function mapJobElement(Crawler $node): JobPostingDTO
    {
        return new JobPostingDTO(
            externalId: $node->attr('data-id') ?? 'unknown',
            title: $node->filter('.title')->count() ? $node->filter('.title')->text() : 'Untitled',
            location: $node->filter('.location')->count() ? $node->filter('.location')->text() : null,
            url: $node->filter('a')->count() ? $node->filter('a')->attr('href') ?? '' : '',
            department: null,
            rawPayload: [],
        );
    }
}
