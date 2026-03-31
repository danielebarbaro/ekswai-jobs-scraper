<?php

declare(strict_types=1);

namespace App\Infrastructure\Admin\Filament\Resources\JobPostingResource\Pages;

use App\Infrastructure\Admin\Filament\Resources\JobPostingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditJobPosting extends EditRecord
{
    protected static string $resource = JobPostingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
