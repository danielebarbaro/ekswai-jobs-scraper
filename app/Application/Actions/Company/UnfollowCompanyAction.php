<?php

declare(strict_types=1);

namespace App\Application\Actions\Company;

use App\Domain\Company\Company;
use App\Domain\User\User;

class UnfollowCompanyAction
{
    public function execute(User $user, Company $company): void
    {
        // Remove job_posting_user records for this user + this company's job postings
        $jobPostingIds = $company->jobPostings()->pluck('id');

        if ($jobPostingIds->isNotEmpty()) {
            $user->jobPostingStatuses()->detach($jobPostingIds);
        }

        // Remove subscription
        $user->subscribedCompanies()->detach($company->id);
    }
}
