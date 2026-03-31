<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Company\Company;
use App\Domain\JobPosting\JobPosting;
use Illuminate\Database\Eloquent\Factories\Factory;

class JobPostingFactory extends Factory
{
    protected $model = JobPosting::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'external_id' => $this->faker->unique()->uuid(),
            'title' => $this->faker->jobTitle(),
            'location' => $this->faker->city(),
            'url' => $this->faker->url(),
            'department' => $this->faker->randomElement(['Engineering', 'Sales', 'Marketing', 'Product', 'Design']),
            'first_seen_at' => now(),
            'last_seen_at' => now(),
            'raw_payload' => [
                'id' => $this->faker->uuid(),
                'title' => $this->faker->jobTitle(),
                'location' => ['city' => $this->faker->city()],
            ],
        ];
    }

    public function new(): static
    {
        return $this->state(fn (array $attributes) => [
            'first_seen_at' => now()->subHours(2),
            'last_seen_at' => now(),
        ]);
    }

    public function old(): static
    {
        return $this->state(fn (array $attributes) => [
            'first_seen_at' => now()->subDays(10),
            'last_seen_at' => now()->subDays(5),
        ]);
    }
}
