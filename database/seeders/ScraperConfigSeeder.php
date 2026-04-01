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
                    'job_list' => 'ul#jobs_list_container li',
                    'job_title' => 'a.hyphens-auto',
                    'job_location' => 'span.text-base span:last-child',
                    'job_department' => 'span.text-base span:first-child',
                ],
                'health_check_selector' => 'ul#jobs_list_container',
                'base_url_pattern' => 'https://{slug}.teamtailor.com/jobs',
            ]
        );

        ScraperConfig::firstOrCreate(
            ['provider' => 'factorial'],
            [
                'selectors' => [
                    'job_list' => 'li.job-offer-item',
                    'job_title' => 'div.factorial__headingFontFamily',
                    'job_department' => 'div.text-gray-350',
                    'job_url_attr' => 'data-job-postings-url',
                ],
                'health_check_selector' => 'li.job-offer-item',
                'base_url_pattern' => 'https://{slug}.factorialhr.com/',
            ]
        );
    }
}
