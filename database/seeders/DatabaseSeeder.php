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
        $user = User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => 'password',
                'email_verified_at' => now(),
            ]
        );

        $company = Company::firstOrCreate(
            ['workable_account_slug' => 'laravel'],
            [
                'name' => 'Laravel',
                'is_active' => true,
            ]
        );

        $user->subscribedCompanies()->syncWithoutDetaching([
            $company->id => ['email_notifications' => true],
        ]);
    }
}
