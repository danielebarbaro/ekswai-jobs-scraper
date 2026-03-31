<?php

declare(strict_types=1);

namespace App\Domain\Shared;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

abstract class BaseModel extends Model
{
    use HasUuids;

    protected $guarded = [];
}
