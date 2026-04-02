<?php

namespace App\Providers;

use App\Domain\JobFilter\JobFilter;
use App\Domain\JobFilter\JobFilterPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(JobFilter::class, JobFilterPolicy::class);

        Password::defaults(fn () => Password::min(8)
            ->mixedCase()
            ->numbers()
            ->symbols()
            ->uncompromised()
        );

        RateLimiter::for('emails', function () {
            return Limit::perSecond(1);
        });
    }
}
