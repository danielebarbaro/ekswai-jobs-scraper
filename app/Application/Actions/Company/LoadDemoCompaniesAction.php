<?php

declare(strict_types=1);

namespace App\Application\Actions\Company;

use App\Domain\Company\Company;
use App\Domain\User\User;

class LoadDemoCompaniesAction
{
    /** @var array<int, array{provider: string, slug: string, name: string}> */
    private const array DEMO_COMPANIES = [
        ['provider' => 'workable', 'slug' => 'laravel', 'name' => 'Laravel'],
        ['provider' => 'workable', 'slug' => 'gelato', 'name' => 'Gelato'],
        ['provider' => 'lever', 'slug' => 'scaleway', 'name' => 'Scaleway'],
        ['provider' => 'lever', 'slug' => 'coinspaid', 'name' => 'CoinsPaid'],
        ['provider' => 'teamtailor', 'slug' => 'weroad', 'name' => 'WeRoad'],
        ['provider' => 'factorial', 'slug' => 'shippypro', 'name' => 'ShippyPro'],
        ['provider' => 'ashby', 'slug' => 'jimdo.com', 'name' => 'Jimdo'],
        ['provider' => 'greenhouse', 'slug' => 'carta', 'name' => 'Carta'],
    ];

    public function execute(User $user): int
    {
        $subscribed = 0;

        foreach (self::DEMO_COMPANIES as $data) {
            $company = Company::firstOrCreate(
                ['provider' => $data['provider'], 'provider_slug' => $data['slug']],
                [
                    'name' => $data['name'],
                    'is_active' => true,
                ]
            );

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
