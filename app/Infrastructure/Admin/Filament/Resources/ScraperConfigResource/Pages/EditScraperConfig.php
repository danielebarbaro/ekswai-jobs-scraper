<?php

declare(strict_types=1);

namespace App\Infrastructure\Admin\Filament\Resources\ScraperConfigResource\Pages;

use App\Infrastructure\Admin\Filament\Resources\ScraperConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditScraperConfig extends EditRecord
{
    protected static string $resource = ScraperConfigResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
