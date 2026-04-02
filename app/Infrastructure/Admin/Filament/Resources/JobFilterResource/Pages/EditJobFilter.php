<?php

declare(strict_types=1);

namespace App\Infrastructure\Admin\Filament\Resources\JobFilterResource\Pages;

use App\Infrastructure\Admin\Filament\Resources\JobFilterResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditJobFilter extends EditRecord
{
    protected static string $resource = JobFilterResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
