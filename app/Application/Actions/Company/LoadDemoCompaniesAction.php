<?php

declare(strict_types=1);

namespace App\Application\Actions\Company;

use App\Domain\Company\Company;
use App\Domain\User\User;

class LoadDemoCompaniesAction
{
    /** @var array<int, array{provider: string, slug: string}> */
    private const array DEMO_COMPANIES = [
        ['provider' => 'workable', 'slug' => 'laravel'],
        ['provider' => 'workable', 'slug' => 'gelato'],
        ['provider' => 'lever', 'slug' => 'scaleway'],
        ['provider' => 'lever', 'slug' => 'coinspaid'],
        ['provider' => 'teamtailor', 'slug' => 'weroad'],
        ['provider' => 'factorial', 'slug' => 'shippypro'],
    ];

    public function execute(User $user): int
    {
        $subscribed = 0;

        foreach (self::DEMO_COMPANIES as $data) {
            $company = Company::where('provider', $data['provider'])
                ->where('provider_slug', $data['slug'])
                ->first();

            if (! $company) {
                continue;
            }

            if ($user->subscribedCompanies()->where('company_id', $company->id)->exists()) {
                continue;
            }

            $user->subscribedCompanies()->attach($company->id, ['email_notifications' => true]);

            $existingJobIds = $company->jobPostings()->pluck('id');

            if ($existingJobIds->isNotEmpty()) {
                $pivotData = $existingJobIds->mapWithKeys(fn ($id) => [
                    $id => ['status' => 'new'],
                ])->toArray();

                $user->jobPostingStatuses()->syncWithoutDetaching($pivotData);
            }

            $subscribed++;
        }

        return $subscribed;
    }
}
