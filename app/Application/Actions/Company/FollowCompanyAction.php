<?php

declare(strict_types=1);

namespace App\Application\Actions\Company;

use App\Application\Actions\JobPosting\SyncCompanyJobPostingsAction;
use App\Domain\Company\Company;
use App\Domain\User\User;
use App\Infrastructure\Services\Workable\WorkableHttpClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class FollowCompanyAction
{
    public function __construct(
        private readonly SyncCompanyJobPostingsAction $syncAction
    ) {}

    public function execute(User $user, string $slug): Company
    {
        $slug = strtolower(trim($slug));

        // Check if user already follows a company with this slug
        $existingCompany = Company::where('workable_account_slug', $slug)->first();
        if ($existingCompany && $user->subscribedCompanies()->where('company_id', $existingCompany->id)->exists()) {
            throw ValidationException::withMessages([
                'slug' => ['You already follow this company.'],
            ]);
        }

        // Validate slug against Workable API
        $response = Http::timeout(15)->get("https://apply.workable.com/api/v1/widget/accounts/{$slug}");

        if (! $response->successful() || ! isset($response->json()['name'])) {
            throw ValidationException::withMessages([
                'slug' => ['This company does not exist on Workable.'],
            ]);
        }

        $companyName = $response->json()['name'];

        // Find or create company
        $company = Company::firstOrCreate(
            ['workable_account_slug' => $slug],
            ['name' => $companyName, 'is_active' => true]
        );

        // Create subscription
        $user->subscribedCompanies()->attach($company->id, ['email_notifications' => true]);

        // Sync jobs for this company (creates job_posting_user records for new subscriber)
        $this->syncAction->execute($company);

        // Also create job_posting_user records for any existing job postings
        // that were already synced before this user subscribed
        $existingJobIds = $company->jobPostings()
            ->whereDoesntHave('userStatuses', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->pluck('id');

        if ($existingJobIds->isNotEmpty()) {
            $pivotData = $existingJobIds->mapWithKeys(fn ($id) => [
                $id => ['status' => 'new'],
            ])->toArray();

            $user->jobPostingStatuses()->attach($pivotData);
        }

        return $company->fresh();
    }
}
