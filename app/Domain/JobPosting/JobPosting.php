<?php

declare(strict_types=1);

namespace App\Domain\JobPosting;

use App\Domain\Company\Company;
use App\Domain\Shared\BaseModel;
use App\Domain\User\User;
use Database\Factories\JobPostingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class JobPosting extends BaseModel
{
    /** @use HasFactory<JobPostingFactory> */
    use HasFactory;

    protected static function newFactory(): JobPostingFactory
    {
        return JobPostingFactory::new();
    }

    protected function casts(): array
    {
        return [
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'raw_payload' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function userStatuses(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('status')
            ->withTimestamps();
    }

    public function markAsSeen(): void
    {
        $this->update(['last_seen_at' => now()]);
    }

    public function scopeNew($query, string $since = '24 hours')
    {
        return $query->where('first_seen_at', '>=', now()->sub($since));
    }
}
