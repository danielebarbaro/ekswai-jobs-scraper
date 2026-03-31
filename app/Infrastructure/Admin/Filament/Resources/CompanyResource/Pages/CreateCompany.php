<?php

declare(strict_types=1);

namespace App\Infrastructure\Admin\Filament\Resources\CompanyResource\Pages;

use App\Infrastructure\Admin\Filament\Resources\CompanyResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCompany extends CreateRecord
{
    protected static string $resource = CompanyResource::class;
}
