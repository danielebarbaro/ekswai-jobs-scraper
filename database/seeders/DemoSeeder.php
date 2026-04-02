<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Application\Actions\JobPosting\SyncCompanyJobPostingsAction;
use App\Domain\Company\Company;
use App\Domain\User\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class DemoSeeder extends Seeder
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

    public function run(): void
    {
        $admin = User::where('email', 'me@plincode.tech')->firstOrFail();
        $syncAction = app(SyncCompanyJobPostingsAction::class);

        foreach (self::DEMO_COMPANIES as $data) {
            $company = Company::firstOrCreate(
                ['provider' => $data['provider'], 'provider_slug' => $data['slug']],
                [
                    'name' => $data['name'],
                    'is_active' => true,
                ]
            );

            $admin->subscribedCompanies()->syncWithoutDetaching([
                $company->id => ['email_notifications' => true],
            ]);

            try {
                $newJobs = $syncAction->execute($company);
                $this->command->info("Synced {$company->name}: {$newJobs->count()} jobs found.");
            } catch (\Throwable $e) {
                $this->command->error("Failed to sync {$company->name}: {$e->getMessage()}");
                Log::warning("DemoSeeder sync failed for {$company->name}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
