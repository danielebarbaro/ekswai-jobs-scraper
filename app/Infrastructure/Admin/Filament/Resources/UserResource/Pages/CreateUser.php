<?php

declare(strict_types=1);

namespace App\Infrastructure\Admin\Filament\Resources\UserResource\Pages;

use App\Infrastructure\Admin\Filament\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
}
