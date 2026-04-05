<?php

declare(strict_types=1);

namespace Workbench\App\Providers\Filament;

use CoringaWc\FilamentAcl\FilamentAclPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Workbench\App\Filament\Livewire\Sidebar;
use Workbench\App\Filament\Livewire\Topbar;
use Workbench\App\Filament\Pages\ContentInsightsPage;
use Workbench\App\Filament\Pages\Dashboard;
use Workbench\App\Filament\Widgets\PostsOverviewWidget;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->sidebarLivewireComponent(Sidebar::class)
            ->topbarLivewireComponent(Topbar::class)
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(
                in: __DIR__ . '/../../Filament/Resources',
                for: 'Workbench\\App\\Filament\\Resources',
            )
            ->pages([
                Dashboard::class,
                ContentInsightsPage::class,
            ])
            ->widgets([
                PostsOverviewWidget::class,
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
            ])
            ->plugin(
                FilamentAclPlugin::make()
                    ->permissionsResource()
                    ->permissionsResourceNavigationLabel('Permissions')
                    ->permissionsResourceNavigationGroup('Access Control')
                    ->permissionsResourceNavigationSort(50),
            );
    }
}
