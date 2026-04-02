<?php

declare(strict_types=1);

namespace App\Domain\JobFilter;

use App\Domain\Company\Company;
use App\Domain\Shared\BaseModel;
use App\Domain\User\User;
use Database\Factories\JobFilterFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $user_id
 * @property string|null $company_id
 * @property array<string>|null $title_include
 * @property array<string>|null $title_exclude
 * @property array<string>|null $country_ids
 * @property bool $remote_only
 * @property array<string>|null $department_include
 */
class JobFilter extends BaseModel
{
    /** @use HasFactory<JobFilterFactory> */
    use HasFactory;

    protected static function newFactory(): JobFilterFactory
    {
        return JobFilterFactory::new();
    }

    protected function casts(): array
    {
        return [
            'title_include' => 'array',
            'title_exclude' => 'array',
            'country_ids' => 'array',
            'remote_only' => 'boolean',
            'department_include' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function scopeGlobal(Builder $query): Builder
    {
        return $query->whereNull('company_id');
    }

    public function scopeForCompany(Builder $query, string $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    public function isGlobal(): bool
    {
        return $this->company_id === null;
    }
}
