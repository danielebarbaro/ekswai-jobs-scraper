<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Contracts;

use App\Application\DTOs\JobPostingDTO;
use Illuminate\Support\Collection;

interface JobBoardClient
{
    /**
     * Fetch all job postings for a given company slug.
     *
     * @return Collection<int, JobPostingDTO>
     */
    public function fetchJobsForCompany(string $slug): Collection;

    /**
     * Validate that a slug exists on the provider.
     * Returns the company name if valid, null otherwise.
     */
    public function validateSlug(string $slug): ?string;

    /**
     * Fetch a description for the company.
     * Returns null if not available.
     */
    public function fetchCompanyDescription(string $slug): ?string;
}
