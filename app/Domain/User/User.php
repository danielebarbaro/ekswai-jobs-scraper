<?php

declare(strict_types=1);

namespace App\Domain\User;

use App\Domain\Company\Company;
use App\Domain\JobPosting\JobPosting;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable implements FilamentUser
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
}
