<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\User\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(ScraperConfigSeeder::class);

        // Admin user
        User::query()->firstOrCreate(['email' => 'me@plincode.tech'], [
            'name' => 'Daniele Barbaro',
            'password' => 'password',
            'email_verified_at' => now(),
            'is_admin' => true,
        ]);

        $this->call(DemoSeeder::class);
    }
}
