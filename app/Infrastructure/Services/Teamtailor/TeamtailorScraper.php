<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Teamtailor;

use App\Application\DTOs\JobPostingDTO;
use App\Domain\Company\JobBoardProvider;
use App\Infrastructure\Services\Scraping\BaseHtmlScraper;
use Symfony\Component\DomCrawler\Crawler;

class TeamtailorScraper extends BaseHtmlScraper
{
    public function getProvider(): JobBoardProvider
    {
        return JobBoardProvider::Teamtailor;
    }

    protected function mapJobElement(Crawler $node): JobPostingDTO
    {
        $selectors = $this->getConfigSelectors();

        $titleEl = $node->filter($selectors['job_title'] ?? 'a');
        $title = $titleEl->count() > 0 ? trim($titleEl->first()->text()) : 'Untitled';

        $href = $titleEl->count() > 0 ? ($titleEl->first()->attr('href') ?? '') : '';
        $url = str_starts_with($href, 'http') ? $href : '';

        $externalId = $this->extractIdFromUrl($href);

        $locationEl = $node->filter($selectors['job_location'] ?? 'span');
        $location = $locationEl->count() > 0 ? trim($locationEl->first()->text()) : null;

        $departmentEl = $node->filter($selectors['job_department'] ?? 'span');
        $department = $departmentEl->count() > 0 ? trim($departmentEl->first()->text()) : null;

        return new JobPostingDTO(
            externalId: $externalId,
            title: $title,
            location: $location ?: null,
            url: $url,
            department: $department ?: null,
            rawPayload: [
                'source' => 'teamtailor',
                'href' => $href,
            ],
        );
    }

    private function extractIdFromUrl(string $url): string
    {
        if (preg_match('#/jobs/(\d+)#', $url, $matches)) {
            return $matches[1];
        }

        return md5($url);
    }
}
