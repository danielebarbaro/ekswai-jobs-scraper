<?php

declare(strict_types=1);

namespace App\Infrastructure\Admin\Filament\Widgets;

use App\Domain\Company\Company;
use App\Domain\JobPosting\JobPosting;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    #[\Override]
    protected function getStats(): array
    {
        $totalCompanies = Company::query()->count();
        $activeCompanies = Company::active()->count();

        return [
            Stat::make('Aziende attive', $activeCompanies.'/'.$totalCompanies)
                ->description('su '.$totalCompanies.' totali')
                ->icon('heroicon-o-building-office'),
            Stat::make('Annunci totali', (string) JobPosting::query()->count())
                ->icon('heroicon-o-briefcase'),
            Stat::make('Nuovi oggi', (string) JobPosting::query()->where('first_seen_at', '>=', now()->startOfDay())->count())
                ->icon('heroicon-o-arrow-trending-up'),
            Stat::make('Nuovi questa settimana', (string) JobPosting::query()->where('first_seen_at', '>=', now()->startOfWeek())->count())
                ->icon('heroicon-o-calendar'),
        ];
    }
}
