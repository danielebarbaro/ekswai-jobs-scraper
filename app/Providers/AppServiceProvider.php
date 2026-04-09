<?php

namespace App\Providers;

use App\Domain\JobFilter\JobFilter;
use App\Domain\JobFilter\JobFilterPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Diagnostics\DiagnosingHealth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Nightwatch\Nightwatch;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    #[\Override]
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

        RateLimiter::for('emails', fn () => Limit::perSecond(1));

        Event::listen(static function (DiagnosingHealth $event): void {
            Nightwatch::dontSample();
        });
    }
}
