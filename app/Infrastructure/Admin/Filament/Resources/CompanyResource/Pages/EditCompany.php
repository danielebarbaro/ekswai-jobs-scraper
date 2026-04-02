<?php

declare(strict_types=1);

namespace App\Infrastructure\Admin\Filament\Resources\CompanyResource\Pages;

use App\Infrastructure\Admin\Filament\Resources\CompanyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCompany extends EditRecord
{
    protected static string $resource = CompanyResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
