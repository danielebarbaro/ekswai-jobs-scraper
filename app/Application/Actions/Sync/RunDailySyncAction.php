<?php

declare(strict_types=1);

namespace App\Application\Actions\Sync;

use App\Application\Actions\JobPosting\SyncCompanyJobPostingsAction;
use App\Application\Actions\Notification\NotifyUserOfNewJobsAction;
use App\Application\DTOs\JobPostingDTO;
use App\Application\Services\JobFilterService;
use App\Domain\Company\Company;
use App\Domain\JobPosting\JobPosting;
use App\Domain\User\User;
use App\Infrastructure\Services\Scraping\Exceptions\ScrapingFailedException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class RunDailySyncAction
{
    public function __construct(
        private readonly SyncCompanyJobPostingsAction $syncCompanyAction,
        private readonly NotifyUserOfNewJobsAction $notifyUserAction,
        private readonly JobFilterService $jobFilterService
    ) {}

    public function execute(): array
    {
        Log::info('Starting daily sync for all active companies with subscribers');

        $companies = Company::query()
            ->active()
            ->whereHas('subscribers')
            ->get();

        if ($companies->isEmpty()) {
            Log::info('No active companies with subscribers found for syncing');

            return [
                'companies_synced' => 0,
                'companies_failed' => 0,
                'new_jobs_found' => 0,
                'users_notified' => 0,
            ];
        }

        $stats = [
            'companies_synced' => 0,
            'companies_failed' => 0,
            'new_jobs_found' => 0,
            'users_notified' => 0,
        ];

        $jobsByUser = collect();
        $failuresByUser = collect();

        foreach ($companies as $company) {
            try {
                $newJobs = $this->syncCompanyAction->execute($company);

                $stats['companies_synced']++;
                $stats['new_jobs_found'] += $newJobs->count();

                if ($newJobs->isNotEmpty()) {
                    $notifiableUsers = $company->subscribers()
                        ->wherePivot('email_notifications', true)
                        ->get();

                    foreach ($notifiableUsers as $user) {
                        /** @var User $user */
                        $userId = $user->id;

                        $filter = $this->jobFilterService->getEffectiveFilter($user, $company);
                        $jobDtos = $newJobs->map(fn (JobPosting $job): JobPostingDTO => new JobPostingDTO(
                            externalId: $job->external_id,
                            title: $job->title,
                            location: $job->location,
                            url: $job->url,
                            department: $job->department,
                            rawPayload: $job->raw_payload ?? [],
                        ));
                        $filteredDtos = $this->jobFilterService->apply($jobDtos, $filter);

                        if ($filteredDtos->isEmpty()) {
                            continue;
                        }

                        $filteredExternalIds = $filteredDtos->map(fn (JobPostingDTO $dto): string => $dto->externalId)->all();
                        $filteredJobs = $newJobs->filter(
                            fn (JobPosting $job): bool => in_array($job->external_id, $filteredExternalIds, true)
                        )->values();

                        if (! $jobsByUser->has($userId)) {
                            $jobsByUser->put($userId, [
                                'user' => $user,
                                'jobs' => collect(),
                            ]);
                        }

                        $jobsByUser->get($userId)['jobs']->push([
                            'company' => $company,
                            'jobs' => $filteredJobs,
                        ]);
                    }
                }
            } catch (ScrapingFailedException $e) {
                $stats['companies_failed']++;

                Log::error('Failed to scrape company', [
                    'company_id' => $company->id,
                    'error' => $e->getMessage(),
                ]);

                $notifiableUsers = $company->subscribers()
                    ->wherePivot('email_notifications', true)
                    ->get();

                foreach ($notifiableUsers as $user) {
                    /** @var User $user */
                    $userId = $user->id;

                    if (! $failuresByUser->has($userId)) {
                        $failuresByUser->put($userId, [
                            'user' => $user,
                            'failures' => collect(),
                        ]);
                    }

                    $failuresByUser->get($userId)['failures']->push([
                        'company_name' => $company->name,
                    ]);
                }
            } catch (\Throwable $e) {
                Log::error('Failed to sync company', [
                    'company_id' => $company->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        foreach ($jobsByUser as $userId => $data) {
            try {
                /** @var User $user */
                $user = $data['user'];
                $failures = isset($failuresByUser[$userId])
                    ? $failuresByUser[$userId]['failures']
                    : new Collection;
                $this->notifyUserAction->execute($user, $data['jobs'], $failures);
                $stats['users_notified']++;
            } catch (\Throwable $e) {
                Log::error('Failed to notify user', [
                    'user_id' => $userId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Daily sync completed', $stats);

        return $stats;
    }
}
