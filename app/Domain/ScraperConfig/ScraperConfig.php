<?php

declare(strict_types=1);

namespace App\Domain\ScraperConfig;

use App\Domain\Company\JobBoardProvider;
use App\Domain\Shared\BaseModel;
use Database\Factories\ScraperConfigFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ScraperConfig extends BaseModel
{
    /** @use HasFactory<ScraperConfigFactory> */
    use HasFactory;

    protected $table = 'scraper_configs';

    protected static function newFactory(): ScraperConfigFactory
    {
        return ScraperConfigFactory::new();
    }

    protected function casts(): array
    {
        return [
            'provider' => JobBoardProvider::class,
            'selectors' => 'array',
            'is_active' => 'boolean',
            'last_health_check_at' => 'datetime',
            'last_health_check_passed' => 'boolean',
        ];
    }
}
