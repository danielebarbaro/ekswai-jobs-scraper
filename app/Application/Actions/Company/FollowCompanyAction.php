<?php

declare(strict_types=1);

namespace App\Application\Actions\Company;

use App\Application\Actions\JobPosting\SyncCompanyJobPostingsAction;
use App\Domain\Company\Company;
use App\Domain\Company\JobBoardProvider;
use App\Domain\User\User;
use App\Infrastructure\Services\JobBoardClientFactory;
use Illuminate\Validation\ValidationException;

class FollowCompanyAction
{
    public function __construct(
        private readonly JobBoardClientFactory $clientFactory,
        private readonly SyncCompanyJobPostingsAction $syncAction
    ) {}

    public function execute(User $user, string $slug, JobBoardProvider $provider = JobBoardProvider::Workable): Company
    {
        $slug = strtolower(trim($slug));

        $existingCompany = Company::where('provider', $provider)
            ->where('provider_slug', $slug)
            ->first();

        if ($existingCompany && $user->subscribedCompanies()->where('company_id', $existingCompany->id)->exists()) {
            throw ValidationException::withMessages([
                'slug' => ['You already follow this company.'],
            ]);
        }

        $client = $this->clientFactory->make($provider);
        $companyName = $client->validateSlug($slug);

        if ($companyName === null) {
            throw ValidationException::withMessages([
                'slug' => ['This company does not exist on '.$provider->value.'.'],
            ]);
        }

        $company = Company::firstOrCreate(
            ['provider' => $provider, 'provider_slug' => $slug],
            ['name' => $companyName, 'is_active' => true]
        );

        $user->subscribedCompanies()->attach($company->id, ['email_notifications' => true]);

        if ($company->jobPostings()->count() === 0) {
            $this->syncAction->execute($company);
        }

        $existingJobIds = $company->jobPostings()->pluck('id');

        if ($existingJobIds->isNotEmpty()) {
            $pivotData = $existingJobIds->mapWithKeys(fn ($id) => [
                $id => ['status' => 'new'],
            ])->toArray();

            $user->jobPostingStatuses()->syncWithoutDetaching($pivotData);
        }

        return $company->fresh();
    }
}
