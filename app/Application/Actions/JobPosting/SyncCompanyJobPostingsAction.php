<?php

declare(strict_types=1);

namespace App\Application\Actions\JobPosting;

use App\Domain\Company\Company;
use App\Domain\JobPosting\JobPosting;
use App\Infrastructure\Services\JobBoardClientFactory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncCompanyJobPostingsAction
{
    public function __construct(
        private readonly JobBoardClientFactory $clientFactory
    ) {}

    /**
     * Sync job postings for a company and return newly found jobs.
     *
     * @return Collection<int, JobPosting>
     */
    public function execute(Company $company): Collection
    {
        Log::info('Starting job sync for company', [
            'company_id' => $company->id,
            'company_name' => $company->name,
            'provider' => $company->provider->value,
            'provider_slug' => $company->provider_slug,
        ]);

        $client = $this->clientFactory->make($company->provider);
        $jobs = $client->fetchJobsForCompany($company->provider_slug);

        if ($jobs->isEmpty()) {
            Log::info('No jobs found from API', [
                'company_id' => $company->id,
            ]);

            return collect();
        }

        $newJobs = collect();
        $subscriberIds = $company->subscribers()->pluck('users.id')->toArray();

        DB::transaction(function () use ($company, $jobs, &$newJobs, $subscriberIds): void {
            foreach ($jobs as $jobDTO) {
                $existing = $company->jobPostings()
                    ->where('external_id', $jobDTO->externalId)
                    ->first();

                if ($existing) {
                    $existing->update(['last_seen_at' => now()]);

                    continue;
                }

                $jobPosting = $company->jobPostings()->create([
                    'external_id' => $jobDTO->externalId,
                    'title' => $jobDTO->title,
                    'location' => $jobDTO->location,
                    'url' => $jobDTO->url,
                    'department' => $jobDTO->department,
                    'first_seen_at' => now(),
                    'last_seen_at' => now(),
                    'raw_payload' => $jobDTO->rawPayload,
                ]);

                $pivotData = [];
                foreach ($subscriberIds as $userId) {
                    $pivotData[$userId] = ['status' => 'new'];
                }
                $jobPosting->userStatuses()->attach($pivotData);

                $newJobs->push($jobPosting);
            }
        });

        $company->update(['last_synced_at' => now()]);

        Log::info('Job sync completed', [
            'company_id' => $company->id,
            'total_jobs_from_api' => $jobs->count(),
            'new_jobs_found' => $newJobs->count(),
        ]);

        return $newJobs;
    }
}
