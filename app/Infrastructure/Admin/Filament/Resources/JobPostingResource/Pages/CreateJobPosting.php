<?php

declare(strict_types=1);

namespace App\Infrastructure\Admin\Filament\Resources\JobPostingResource\Pages;

use App\Infrastructure\Admin\Filament\Resources\JobPostingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateJobPosting extends CreateRecord
{
    protected static string $resource = JobPostingResource::class;
}
