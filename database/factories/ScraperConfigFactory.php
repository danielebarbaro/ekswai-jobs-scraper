<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\ScraperConfig\ScraperConfig;
use Illuminate\Database\Eloquent\Factories\Factory;

class ScraperConfigFactory extends Factory
{
    protected $model = ScraperConfig::class;

    public function definition(): array
    {
        return [
            'provider' => 'teamtailor',
            'selectors' => [
                'job_list' => 'ul[data-jobs-list] li a',
                'job_title' => 'span.company-link-style',
                'job_location' => 'div.mt-1 span',
                'job_department' => 'div[data-department]',
            ],
            'health_check_selector' => 'ul[data-jobs-list]',
            'base_url_pattern' => 'https://{slug}.teamtailor.com/jobs',
            'retry_attempts' => 3,
            'retry_delay_seconds' => 30,
            'is_active' => true,
            'last_health_check_at' => null,
            'last_health_check_passed' => null,
        ];
    }

    public function factorial(): static
    {
        return $this->state(fn () => [
            'provider' => 'factorial',
            'selectors' => [
                'job_list' => 'li.job-offer-item',
                'job_title' => 'div.factorial__headingFontFamily',
                'job_url' => '[data-job-postings-url]',
                'job_location' => 'h3',
            ],
            'health_check_selector' => 'li.job-offer-item',
            'base_url_pattern' => 'https://{slug}.factorialhr.com/',
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'is_active' => false,
        ]);
    }
}
