<?php

declare(strict_types=1);

namespace App\Infrastructure\Admin\Filament\Resources\JobPostingResource\Pages;

use App\Infrastructure\Admin\Filament\Resources\JobPostingResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewJobPosting extends ViewRecord
{
    protected static string $resource = JobPostingResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
