<?php

declare(strict_types=1);

namespace App\Application\Actions\Sync;

use App\Application\Actions\JobPosting\SyncCompanyJobPostingsAction;
use App\Application\Actions\Notification\NotifyUserOfNewJobsAction;
use App\Domain\Company\Company;
use App\Domain\User\User;
use Illuminate\Support\Facades\Log;

class RunDailySyncAction
{
    public function __construct(
        private readonly SyncCompanyJobPostingsAction $syncCompanyAction,
        private readonly NotifyUserOfNewJobsAction $notifyUserAction
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
                'new_jobs_found' => 0,
                'users_notified' => 0,
            ];
        }

        $stats = [
            'companies_synced' => 0,
            'new_jobs_found' => 0,
            'users_notified' => 0,
        ];

        $jobsByUser = collect();

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

                        if (! $jobsByUser->has($userId)) {
                            $jobsByUser->put($userId, [
                                'user' => $user,
                                'jobs' => collect(),
                            ]);
                        }

                        $jobsByUser->get($userId)['jobs']->push([
                            'company' => $company,
                            'jobs' => $newJobs,
                        ]);
                    }
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
                $this->notifyUserAction->execute($user, $data['jobs']);
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
