<?php

declare(strict_types=1);

namespace App\Infrastructure\Admin\Filament\Resources\JobPostingResource\Pages;

use App\Infrastructure\Admin\Filament\Resources\JobPostingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListJobPostings extends ListRecords
{
    protected static string $resource = JobPostingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
