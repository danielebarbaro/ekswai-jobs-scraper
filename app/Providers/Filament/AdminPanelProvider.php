<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->profile()
            ->colors([
                'primary' => Color::Rose,
            ])
            ->discoverResources(in: app_path('Infrastructure/Admin/Filament/Resources'), for: 'App\\Infrastructure\\Admin\\Filament\\Resources')
            ->discoverPages(in: app_path('Infrastructure/Admin/Filament/Pages'), for: 'App\\Infrastructure\\Admin\\Filament\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Infrastructure/Admin/Filament/Widgets'), for: 'App\\Infrastructure\\Admin\\Filament\\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->unsavedChangesAlerts()
            ->favicon(
                asset('favicon.ico')
            )
            ->sidebarFullyCollapsibleOnDesktop()
            ->maxContentWidth('full')
            ->brandLogoHeight('1.25rem')
            ->brandLogo(
                asset('images/logo.png')
            )
            ->darkModeBrandLogo(
                asset('images/logo.png')
            )
            ->navigationItems([
                NavigationItem::make('Profilo')
                    ->url(fn (): string => EditProfile::getUrl())
                    ->icon('heroicon-o-user-circle')
                    ->sort(2)
                    ->visible(fn (): bool => Auth::check()),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
