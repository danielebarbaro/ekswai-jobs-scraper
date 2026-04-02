<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\JobFilter\JobFilter;
use App\Domain\User\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class JobFilterFactory extends Factory
{
    protected $model = JobFilter::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'company_id' => null,
            'title_include' => null,
            'title_exclude' => null,
            'country_ids' => null,
            'remote_only' => false,
            'department_include' => null,
        ];
    }

    public function global(): static
    {
        return $this->state(fn (): array => ['company_id' => null]);
    }

    public function forCompany(string $companyId): static
    {
        return $this->state(fn (): array => ['company_id' => $companyId]);
    }

    public function remoteOnly(): static
    {
        return $this->state(fn (): array => ['remote_only' => true]);
    }

    public function withTitleInclude(array $keywords): static
    {
        return $this->state(fn (): array => ['title_include' => $keywords]);
    }

    public function withTitleExclude(array $keywords): static
    {
        return $this->state(fn (): array => ['title_exclude' => $keywords]);
    }

    public function withDepartments(array $departments): static
    {
        return $this->state(fn (): array => ['department_include' => $departments]);
    }
}
