<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Factorial;

use App\Application\DTOs\JobPostingDTO;
use App\Domain\Company\JobBoardProvider;
use App\Infrastructure\Services\Scraping\BaseHtmlScraper;
use Symfony\Component\DomCrawler\Crawler;

class FactorialScraper extends BaseHtmlScraper
{
    public function getProvider(): JobBoardProvider
    {
        return JobBoardProvider::Factorial;
    }

    protected function mapJobElement(Crawler $node): JobPostingDTO
    {
        $selectors = $this->getConfigSelectors();

        $jobUrl = $node->attr($selectors['job_url_attr'] ?? 'data-job-postings-url') ?? '';
        $externalId = $this->extractIdFromUrl($jobUrl);

        $titleSelector = $selectors['job_title'] ?? 'div.factorial__headingFontFamily';
        $title = $node->filter($titleSelector)->count() > 0
            ? trim($node->filter($titleSelector)->first()->text())
            : 'Untitled';

        $departmentSelector = $selectors['job_department'] ?? 'div.text-gray-350';
        $department = $node->filter($departmentSelector)->count() > 0
            ? trim($node->filter($departmentSelector)->first()->text())
            : null;

        $location = $this->extractLocation($node);

        return new JobPostingDTO(
            externalId: $externalId,
            title: $title,
            location: $location,
            url: $jobUrl,
            department: $department ?: null,
            rawPayload: [
                'source' => 'factorial',
                'contract_type' => $node->attr('data-contract-type'),
                'is_remote' => $node->attr('data-is-remote'),
                'location_id' => $node->attr('data-location-id'),
                'team_id' => $node->attr('data-team-id'),
            ],
        );
    }

    private function extractIdFromUrl(string $url): string
    {
        if (preg_match('#-(\d+)$#', parse_url($url, PHP_URL_PATH) ?? '', $matches)) {
            return $matches[1];
        }

        return md5($url);
    }

    private function extractLocation(Crawler $node): ?string
    {
        // The <li> is inside a <ul> which is inside a <div class="mb-12"> that also contains an <h3>
        try {
            $parent = $node->closest('div.mb-12');
            if ($parent !== null && $parent->count() > 0) {
                $heading = $parent->filter('h3');
                if ($heading->count() > 0) {
                    return trim($heading->first()->text()) ?: null;
                }
            }
        } catch (\Throwable) {
            // Fall through
        }

        return null;
    }
}
