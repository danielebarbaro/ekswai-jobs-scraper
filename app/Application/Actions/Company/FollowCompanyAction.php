<?php

declare(strict_types=1);

namespace App\Application\Actions\Company;

use App\Application\Actions\JobPosting\SyncCompanyJobPostingsAction;
use App\Application\Services\JobBoardUrlParser;
use App\Domain\Company\Company;
use App\Domain\Company\JobBoardProvider;
use App\Domain\User\User;
use App\Infrastructure\Services\JobBoardClientFactory;
use Illuminate\Validation\ValidationException;

class FollowCompanyAction
{
    public function __construct(
        private readonly JobBoardClientFactory $clientFactory,
        private readonly SyncCompanyJobPostingsAction $syncAction,
        private readonly JobBoardUrlParser $urlParser,
    ) {}

    public function execute(User $user, string $input, ?JobBoardProvider $provider = null): Company
    {
        $input = strtolower(trim($input));

        if ($provider === null) {
            $parsed = $this->urlParser->parse($input);
            if ($parsed !== null) {
                $provider = $parsed['provider'];
                $input = $parsed['slug'];
            }
        }

        if ($provider === null) {
            throw ValidationException::withMessages([
                'slug' => ['Could not detect the provider. Please select one or paste the full job board URL.'],
            ]);
        }

        $slug = $input;

        $existingCompany = Company::query()->where('provider', $provider)
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

        $description = $client->fetchCompanyDescription($slug);

        $company = Company::query()->firstOrCreate(['provider' => $provider, 'provider_slug' => $slug], ['name' => $companyName, 'description' => $description, 'is_active' => true]);

        $user->subscribedCompanies()->attach($company->id, ['email_notifications' => true]);

        if ($company->jobPostings()->count() === 0) {
            $this->syncAction->execute($company);
        }

        $existingJobIds = $company->jobPostings()->pluck('id');

        if ($existingJobIds->isNotEmpty()) {
            $pivotData = $existingJobIds->mapWithKeys(fn ($id): array => [
                $id => ['status' => 'new'],
            ])->toArray();

            $user->jobPostingStatuses()->syncWithoutDetaching($pivotData);
        }

        return $company->fresh();
    }
}
