<?php

namespace App\Providers\Filament;

use App\Http\Middleware\ForcerLangueArabe;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('/')
            ->login()
            ->brandName('مدرسة تحفيظ القرآن الكريم')
            ->colors([
                'primary' => Color::hex('#0d6efd'),
                'gray'    => Color::hex('#6c757d'),
            ])
            // Police adaptée à l'arabe
            ->font('Noto Kufi Arabic')
            ->favicon(asset('favicon.ico'))
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->darkMode()
            ->navigationGroups([
                'شؤون الطلبة',      // Scolarité
                'الحفظ والمتابعة',   // Hifd et suivi
                'التقارير',          // Rapports
                'الإدارة',           // Administration
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->pages([Pages\Dashboard::class])
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
                ForcerLangueArabe::class,   // ← RTL
            ])
            ->authMiddleware([Authenticate::class])
            ->sidebarCollapsibleOnDesktop()
            ->maxContentWidth('full');
    }
}
