<?php

declare(strict_types=1);

namespace App\Application\Actions\Company;

use App\Application\Actions\JobPosting\SyncCompanyJobPostingsAction;
use App\Domain\Company\Company;
use App\Domain\User\User;
use Illuminate\Support\Facades\Log;

class LoadDemoCompaniesAction
{
    public function __construct(
        private readonly SyncCompanyJobPostingsAction $syncAction
    ) {}

    /** @var array<int, array{provider: string, slug: string, name: string}> */
    public const array DEMO_COMPANIES = [
        ['provider' => 'workable', 'slug' => 'laravel', 'name' => 'Laravel'],
        ['provider' => 'workable', 'slug' => 'gelato', 'name' => 'Gelato'],
        ['provider' => 'workable', 'slug' => 'patchstack', 'name' => 'Patchstack'],
        ['provider' => 'lever', 'slug' => 'scaleway', 'name' => 'Scaleway'],
        ['provider' => 'lever', 'slug' => 'coinspaid', 'name' => 'CoinsPaid'],
        ['provider' => 'teamtailor', 'slug' => 'weroad', 'name' => 'WeRoad'],
        ['provider' => 'factorial', 'slug' => 'shippypro', 'name' => 'ShippyPro'],
        ['provider' => 'ashby', 'slug' => 'jimdo.com', 'name' => 'Jimdo'],
        ['provider' => 'greenhouse', 'slug' => 'carta', 'name' => 'Carta'],
        ['provider' => 'greenhouse', 'slug' => 'scalapaysrl', 'name' => 'Scalapay'],
    ];

    public function execute(User $user): int
    {
        $subscribed = 0;

        foreach (self::DEMO_COMPANIES as $data) {
            $company = Company::query()->firstOrCreate(['provider' => $data['provider'], 'provider_slug' => $data['slug']], [
                'name' => $data['name'],
                'is_active' => true,
            ]);

            if ($user->subscribedCompanies()->where('company_id', $company->id)->exists()) {
                continue;
            }

            $user->subscribedCompanies()->attach($company->id, ['email_notifications' => true]);

            try {
                $newJobs = $this->syncAction->execute($company);

                if ($newJobs->isNotEmpty()) {
                    $pivotData = $newJobs->mapWithKeys(fn ($jp): array => [
                        $jp->id => ['status' => 'new'],
                    ])->toArray();

                    $user->jobPostingStatuses()->syncWithoutDetaching($pivotData);
                }
            } catch (\Throwable $e) {
                Log::warning("LoadDemoCompanies sync failed for {$company->name}", [
                    'error' => $e->getMessage(),
                ]);
            }

            $subscribed++;
        }

        return $subscribed;
    }
}
