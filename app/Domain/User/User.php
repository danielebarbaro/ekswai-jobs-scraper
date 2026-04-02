<?php

declare(strict_types=1);

namespace App\Domain\User;

use App\Domain\Company\Company;
use App\Domain\JobFilter\JobFilter;
use App\Domain\JobPosting\JobPosting;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable implements CanResetPassword, FilamentUser, HasName
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use HasUuids;
    use Notifiable;
    use TwoFactorAuthenticatable;

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }

    protected $guarded = [];

    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    #[\Override]
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_admin;
    }

    public function getFilamentName(): string
    {
        return $this->username ?? 'John Doe';
    }

    public function subscribedCompanies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class)
            ->withPivot('email_notifications')
            ->withTimestamps();
    }

    public function jobPostingStatuses(): BelongsToMany
    {
        return $this->belongsToMany(JobPosting::class)
            ->withPivot('status')
            ->withTimestamps();
    }

    public function jobFilters(): HasMany
    {
        return $this->hasMany(JobFilter::class);
    }

    public function globalJobFilter(): HasOne
    {
        return $this->hasOne(JobFilter::class)->whereNull('company_id');
    }
}
