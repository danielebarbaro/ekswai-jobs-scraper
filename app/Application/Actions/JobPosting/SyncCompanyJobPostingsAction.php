<?php

declare(strict_types=1);

namespace App\Application\Actions\JobPosting;

use App\Application\DTOs\WorkableJobDTO;
use App\Domain\Company\Company;
use App\Domain\JobPosting\JobPosting;
use App\Infrastructure\Services\Workable\WorkableHttpClient;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncCompanyJobPostingsAction
{
    public function __construct(
        private readonly WorkableHttpClient $workableClient
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
            'workable_slug' => $company->workable_account_slug,
        ]);

        $workableJobs = $this->workableClient->fetchJobsForCompany($company->workable_account_slug);

        if ($workableJobs->isEmpty()) {
            Log::info('No jobs found from Workable API', [
                'company_id' => $company->id,
            ]);

            return collect();
        }

        $newJobs = collect();
        $subscriberIds = $company->subscribers()->pluck('users.id')->toArray();

        DB::transaction(function () use ($company, $workableJobs, &$newJobs, $subscriberIds) {
            foreach ($workableJobs as $workableJob) {
                /** @var WorkableJobDTO $workableJob */
                $existing = $company->jobPostings()
                    ->where('external_id', $workableJob->externalId)
                    ->first();

                if ($existing) {
                    $existing->update(['last_seen_at' => now()]);
                    continue;
                }

                $jobPosting = $company->jobPostings()->create([
                    'external_id' => $workableJob->externalId,
                    'title' => $workableJob->title,
                    'location' => $workableJob->location,
                    'url' => $workableJob->url,
                    'department' => $workableJob->department,
                    'first_seen_at' => now(),
                    'last_seen_at' => now(),
                    'raw_payload' => $workableJob->rawPayload,
                ]);

                // Create job_posting_user records for all subscribers
                $pivotData = [];
                foreach ($subscriberIds as $userId) {
                    $pivotData[$userId] = ['status' => 'new'];
                }
                $jobPosting->userStatuses()->attach($pivotData);

                $newJobs->push($jobPosting);
            }
        });

        Log::info('Job sync completed', [
            'company_id' => $company->id,
            'total_jobs_from_api' => $workableJobs->count(),
            'new_jobs_found' => $newJobs->count(),
        ]);

        return $newJobs;
    }
}
