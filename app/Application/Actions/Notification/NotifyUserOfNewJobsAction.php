<?php

declare(strict_types=1);

namespace App\Application\Actions\Notification;

use App\Domain\User\User;
use App\Infrastructure\Mail\NewJobsFoundMail;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotifyUserOfNewJobsAction
{
    /**
     * Send email notification to user about new job postings.
     *
     * @param  Collection  $jobsByCompany  Collection of ['company' => Company, 'jobs' => Collection<JobPosting>]
     */
    public function execute(User $user, Collection $jobsByCompany): void
    {
        if ($jobsByCompany->isEmpty()) {
            Log::info('No new jobs to notify user about', [
                'user_id' => $user->id,
            ]);

            return;
        }

        $totalJobs = $jobsByCompany->sum(fn ($item) => $item['jobs']->count());

        Log::info('Sending new jobs notification to user', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'companies_count' => $jobsByCompany->count(),
            'total_new_jobs' => $totalJobs,
        ]);

        try {
            Mail::to($user->email)->send(new NewJobsFoundMail($user, $jobsByCompany));

            Log::info('New jobs notification sent successfully', [
                'user_id' => $user->id,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to send new jobs notification', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
