<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\ScraperConfig\ScraperConfig;
use Illuminate\Database\Seeder;

class ScraperConfigSeeder extends Seeder
{
    public function run(): void
    {
        ScraperConfig::firstOrCreate(
            ['provider' => 'teamtailor'],
            [
                'selectors' => [
                    'job_list' => 'ul[data-jobs-list] li a',
                    'job_title' => 'span.company-link-style',
                    'job_location' => 'div.mt-1 span',
                    'job_department' => 'div[data-department]',
                ],
                'health_check_selector' => 'ul[data-jobs-list]',
                'base_url_pattern' => 'https://{slug}.teamtailor.com/jobs',
            ]
        );

        ScraperConfig::firstOrCreate(
            ['provider' => 'factorial'],
            [
                'selectors' => [
                    'job_list' => 'li.job-offer-item',
                    'job_title' => 'div.factorial__headingFontFamily',
                    'job_url' => '[data-job-postings-url]',
                    'job_location' => 'h3',
                ],
                'health_check_selector' => 'li.job-offer-item',
                'base_url_pattern' => 'https://{slug}.factorialhr.com/',
            ]
        );
    }
}
