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
                'job_list' => 'ul#jobs_list_container li',
                'job_title' => 'a.hyphens-auto',
                'job_location' => 'span.text-base span:last-child',
                'job_department' => 'span.text-base span:first-child',
            ],
            'health_check_selector' => 'ul#jobs_list_container',
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
        return $this->state(fn (): array => [
            'provider' => 'factorial',
            'selectors' => [
                'job_list' => 'li.job-offer-item',
                'job_title' => 'div.factorial__headingFontFamily',
                'job_department' => 'div.text-gray-350',
                'job_url_attr' => 'data-job-postings-url',
            ],
            'health_check_selector' => 'li.job-offer-item',
            'base_url_pattern' => 'https://{slug}.factorialhr.com/',
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => [
            'is_active' => false,
        ]);
    }
}
