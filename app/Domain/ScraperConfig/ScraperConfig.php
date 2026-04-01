<?php

declare(strict_types=1);

namespace App\Domain\ScraperConfig;

use App\Domain\Company\JobBoardProvider;
use App\Domain\Shared\BaseModel;
use Database\Factories\ScraperConfigFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property JobBoardProvider $provider
 * @property array<string, string> $selectors
 * @property string $health_check_selector
 * @property string $base_url_pattern
 * @property int $retry_attempts
 * @property int $retry_delay_seconds
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $last_health_check_at
 * @property bool|null $last_health_check_passed
 */
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
