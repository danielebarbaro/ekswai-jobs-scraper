<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Company\Company;
use App\Domain\User\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(ScraperConfigSeeder::class);

        // Admin user
        $admin = User::firstOrCreate(
            ['email' => 'me@plincode.tech'],
            [
                'name' => 'Daniele Barbaro',
                'password' => 'password',
                'email_verified_at' => now(),
                'is_admin' => true,
            ]
        );

        // Default company
        $company = Company::firstOrCreate(
            ['provider' => 'workable', 'provider_slug' => 'laravel'],
            [
                'name' => 'Laravel',
                'is_active' => true,
            ]
        );

        $admin->subscribedCompanies()->syncWithoutDetaching([
            $company->id => ['email_notifications' => true],
        ]);
    }
}
