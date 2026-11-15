<?php

namespace App\Providers\Filament;

use App\Filament\Auth\Responses\LogoutResponse;
use Filament\Auth\Http\Responses\Contracts\LogoutResponse as LogoutResponseContract;use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use App\Filament\Widgets\DashboardAccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function register(): void
    {
        parent::register();

        $this->app->bind(LogoutResponseContract::class, LogoutResponse::class);
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->databaseNotifications()
            ->colors([
                'primary' => Color::Red,
            ])
            ->brandLogo(fn () => asset('images/' . (app()->getLocale() === 'ar' ? 'tawsili-brand-logo.svg' : 'tawsili-brand-logo-en.svg')))
            ->brandLogoHeight('2.5rem')
            // Root-relative so the scheme/host come from the page URL; asset() here is
            // evaluated when the panel boots, before TrustProxies runs, which can
            // emit http:// behind the TLS-terminating reverse proxy.
            ->favicon('/favicon.ico')
            ->renderHook(PanelsRenderHook::HEAD_END, fn () => \App\Support\FontAssets::inlineCss())
            ->renderHook(PanelsRenderHook::HEAD_END, fn () => view('components.ai-assistant-assets'))
            ->renderHook(PanelsRenderHook::BODY_END, fn () => view('components.ai-assistant'))
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                \App\Filament\Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                DashboardAccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
