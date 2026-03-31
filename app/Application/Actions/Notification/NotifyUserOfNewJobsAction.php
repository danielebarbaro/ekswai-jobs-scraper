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

        Log::info('Queueing new jobs notification to user', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'companies_count' => $jobsByCompany->count(),
            'total_new_jobs' => $totalJobs,
        ]);

        try {
            // Queue the email instead of sending synchronously
            Mail::to($user->email)
                ->queue(
                    (new NewJobsFoundMail($user, $jobsByCompany))
                        ->onQueue('emails')
                );

            Log::info('New jobs notification queued successfully', [
                'user_id' => $user->id,
                'queue' => 'emails',
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to queue new jobs notification', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
