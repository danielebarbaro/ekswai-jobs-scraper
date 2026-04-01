<?php

declare(strict_types=1);

namespace App\Infrastructure\Admin\Filament\Resources\ScraperConfigResource\Pages;

use App\Infrastructure\Admin\Filament\Resources\ScraperConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListScraperConfigs extends ListRecords
{
    protected static string $resource = ScraperConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
