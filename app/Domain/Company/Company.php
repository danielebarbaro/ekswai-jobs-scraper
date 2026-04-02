<?php

declare(strict_types=1);

namespace App\Domain\Company;

use App\Domain\JobPosting\JobPosting;
use App\Domain\Shared\BaseModel;
use App\Domain\User\User;
use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property JobBoardProvider $provider
 * @property string $provider_slug
 * @property Carbon|null $last_synced_at
 */
class Company extends BaseModel
{
    /** @use HasFactory<CompanyFactory> */
    use HasFactory;

    protected static function newFactory(): CompanyFactory
    {
        return CompanyFactory::new();
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'provider' => JobBoardProvider::class,
            'last_synced_at' => 'datetime',
        ];
    }

    public function subscribers(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('email_notifications')
            ->withTimestamps();
    }

    public function jobPostings(): HasMany
    {
        return $this->hasMany(JobPosting::class);
    }

    public function activate(): void
    {
        $this->update(['is_active' => true]);
    }

    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    public function toggleActivation(): void
    {
        $this->update(['is_active' => ! $this->is_active]);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
