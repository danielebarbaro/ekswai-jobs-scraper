<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Application\Actions\JobPosting\SyncCompanyJobPostingsAction;
use App\Application\Services\DefaultCompanyList;
use App\Domain\Company\Company;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class DefaultCompaniesSeeder extends Seeder
{
    public function run(): void
    {
        $syncAction = app(SyncCompanyJobPostingsAction::class);

        foreach (DefaultCompanyList::all() as $data) {
            // Companies are seeded without subscribers: users pick who to follow.
            $company = Company::query()->firstOrCreate(['provider' => $data['provider'], 'provider_slug' => $data['slug']], [
                'name' => $data['name'],
                'is_active' => true,
            ]);

            try {
                $newJobs = $syncAction->execute($company);
                $this->command->info("Synced {$company->name}: {$newJobs->count()} jobs found.");
            } catch (\Throwable $e) {
                $this->command->error("Failed to sync {$company->name}: {$e->getMessage()}");
                Log::warning("DefaultCompaniesSeeder sync failed for {$company->name}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
