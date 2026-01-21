<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Althinect\FilamentSpatieRolesPermissions\FilamentSpatieRolesPermissionsPlugin;
use App\Filament\Widgets\AlquileresChart;
use App\Filament\Widgets\AlquilerWidgetStats;
use App\Filament\Widgets\UltimosAlquileres;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class DisfracesPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('disfraces')
            ->path('disfraces')
            ->login()
            ->brandName('Panel')
            ->navigationGroups([
                NavigationGroup::make('Administración')->collapsed()->icon('heroicon-o-cog-6-tooth'),
                NavigationGroup::make('Inventario')->collapsed()->icon('heroicon-o-archive-box'),
                NavigationGroup::make('Operaciones')->collapsed()->icon('heroicon-o-clipboard-document-list'),
                NavigationGroup::make('Reportes')->collapsed()->icon('heroicon-o-chart-bar'),
                NavigationGroup::make('Roles y Permisos')->icon('heroicon-o-lock-closed'),
            ])
            ->sidebarCollapsibleOnDesktop()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([\App\Filament\Pages\Dashboard::class])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                //Widgets\AccountWidget::class,
                //Widgets\FilamentInfoWidget::class,
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
            ->plugin(FilamentSpatieRolesPermissionsPlugin::make())
            ->authMiddleware([Authenticate::class]);
    }
}
