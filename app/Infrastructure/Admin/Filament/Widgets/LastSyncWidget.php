<?php

declare(strict_types=1);

namespace App\Infrastructure\Admin\Filament\Widgets;

use App\Domain\JobPosting\JobPosting;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class LastSyncWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int|array|null $columns = 2;

    #[\Override]
    protected function getStats(): array
    {
        $lastSync = JobPosting::query()->max('last_seen_at');
        $jobsSyncedToday = JobPosting::query()->where('last_seen_at', '>=', now()->startOfDay())->count();

        $lastSyncLabel = $lastSync
            ? now()->parse($lastSync)->diffForHumans()
            : 'Mai';

        return [
            Stat::make('Ultimo sync', $lastSyncLabel)
                ->description($lastSync ? now()->parse($lastSync)->format('d/m/Y H:i') : '')
                ->icon('heroicon-o-arrow-path')
                ->columnSpan(1),
            Stat::make('Job aggiornati oggi', (string) $jobsSyncedToday)
                ->icon('heroicon-o-check-circle')
                ->columnSpan(1),
        ];
    }
}
