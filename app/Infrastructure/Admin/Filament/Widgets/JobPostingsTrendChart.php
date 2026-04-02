<?php

declare(strict_types=1);

namespace App\Infrastructure\Admin\Filament\Widgets;

use App\Domain\JobPosting\JobPosting;
use Carbon\CarbonPeriod;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class JobPostingsTrendChart extends ChartWidget
{
    protected ?string $heading = 'Nuovi annunci (ultimi 30 giorni)';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '300px';

    protected function getType(): string
    {
        return 'line';
    }

    #[\Override]
    protected function getData(): array
    {
        $start = now()->subDays(29)->startOfDay();
        $end = now()->endOfDay();

        $counts = JobPosting::query()->where('first_seen_at', '>=', $start)
            ->selectRaw('DATE(first_seen_at) as date, COUNT(*) as count')
            ->groupByRaw('DATE(first_seen_at)')
            ->pluck('count', 'date')
            ->toArray();

        $labels = [];
        $data = [];

        foreach (CarbonPeriod::create($start, $end) as $date) {
            /** @var Carbon $date */
            $key = $date->format('Y-m-d');
            $labels[] = $date->format('d/m');
            $data[] = $counts[$key] ?? 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Nuovi annunci',
                    'data' => $data,
                    'borderColor' => '#e11d48',
                    'backgroundColor' => 'rgba(225, 29, 72, 0.1)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $labels,
        ];
    }
}
