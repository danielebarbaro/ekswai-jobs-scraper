<?php

declare(strict_types=1);

namespace App\Infrastructure\Admin\Filament\Resources\UserResource\Pages;

use App\Infrastructure\Admin\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
