<?php

declare(strict_types=1);

namespace App\Infrastructure\Admin\Filament\Resources\ScraperConfigResource\Pages;

use App\Infrastructure\Admin\Filament\Resources\ScraperConfigResource;
use Filament\Resources\Pages\CreateRecord;

class CreateScraperConfig extends CreateRecord
{
    protected static string $resource = ScraperConfigResource::class;
}
