<?php

declare(strict_types=1);

namespace App\Infrastructure\Admin\Filament\Resources\JobPostingResource\Pages;

use App\Infrastructure\Admin\Filament\Resources\JobPostingResource;
use Filament\Resources\Pages\ListRecords;

class ListJobPostings extends ListRecords
{
    protected static string $resource = JobPostingResource::class;

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [];
    }
}
