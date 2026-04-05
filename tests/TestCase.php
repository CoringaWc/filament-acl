<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Tests;

use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use CoringaWc\FilamentAcl\Contracts\ResolvesPermissionSubject;
use CoringaWc\FilamentAcl\Enums\PermissionEntityType;
use CoringaWc\FilamentAcl\FilamentPermissionManager;
use CoringaWc\FilamentAcl\FilamentPermissionServiceProvider;
use Filament\Actions\ActionsServiceProvider;
use Filament\Facades\Filament;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Infolists\InfolistsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\Schemas\SchemasServiceProvider;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Filament\Widgets\WidgetsServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\ViewErrorBag;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\Concerns\WithLaravelMigrations;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\Permission\Contracts\Permission as PermissionContract;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionServiceProvider;
use Workbench\App\Models\Role;
use Workbench\App\Models\User;
use Workbench\App\Providers\Filament\AdminPanelProvider;
use Workbench\App\Providers\WorkbenchServiceProvider;

abstract class TestCase extends Orchestra
{
    use LazilyRefreshDatabase;
    use WithLaravelMigrations;
    use WithWorkbench;

    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            static fn (string $modelName): string => 'Workbench\\Database\\Factories\\' . class_basename($modelName) . 'Factory',
        );

        $this->app['session.store']->start();
        $this->app['view']->share('errors', new ViewErrorBag);

        Filament::setCurrentPanel('admin');
        Filament::bootCurrentPanel();
    }

    /**
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        /** @var array<int, class-string> $providers */
        $providers = [
            ActionsServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            BladeIconsServiceProvider::class,
            FilamentServiceProvider::class,
            FormsServiceProvider::class,
            InfolistsServiceProvider::class,
            LivewireServiceProvider::class,
            NotificationsServiceProvider::class,
            PermissionServiceProvider::class,
            SchemasServiceProvider::class,
            SupportServiceProvider::class,
            TablesServiceProvider::class,
            WidgetsServiceProvider::class,
            WorkbenchServiceProvider::class,
            AdminPanelProvider::class,
            FilamentPermissionServiceProvider::class,
        ];

        return array_values(array_filter(
            $providers,
            static fn (string $provider): bool => class_exists($provider),
        ));
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('app.key', 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=');
        $app['config']->set('app.cipher', 'AES-256-CBC');
        $app['config']->set('app.locale', 'pt_BR');
        $app['config']->set('app.fallback_locale', 'en');
        $app['config']->set('app.faker_locale', 'pt_BR');
        $app['config']->set('auth.providers.users.model', User::class);
        $app['config']->set('session.driver', 'array');
        $app['config']->set('filament-acl.plugin.strict_mode', false);
        $app['config']->set('filament-acl.database.panel_scope.on_roles', false);
        $app['config']->set('filament-acl.database.panel_scope.on_permissions', false);
        $app['config']->set('filament-acl.custom_permissions', [
            'content.export' => 'Exportar conteúdo',
            [
                'name' => 'content.publish',
                'label' => 'Publicar conteúdo',
                'panels' => ['admin'],
            ],
        ]);
        $app['config']->set('permission.table_names', [
            'roles' => 'roles',
            'permissions' => 'permissions',
            'model_has_permissions' => 'model_has_permissions',
            'model_has_roles' => 'model_has_roles',
            'role_has_permissions' => 'role_has_permissions',
        ]);
        $app['config']->set('permission.column_names', [
            'model_morph_key' => 'model_id',
            'team_foreign_key' => 'team_id',
            'role_pivot_key' => 'role_id',
            'permission_pivot_key' => 'permission_id',
        ]);
        $app['config']->set('permission.models', [
            'permission' => Permission::class,
            'role' => Role::class,
        ]);
        $app['config']->set('filament-acl.models.permission', Permission::class);
        $app['config']->set('filament-acl.models.role', Role::class);
        $app['config']->set('permission.teams', false);
        $app['config']->set('permission.testing', false);
        $app['config']->set('permission.cache.store', 'array');
        $app['config']->set('permission.cache.key', 'spatie.permission.cache');
    }

    protected function permissionKeyForOwner(
        string $ability,
        string $ownerClass,
        PermissionEntityType $entityType,
        ?string $registrationKey = null,
        ?string $panelId = 'admin',
    ): string {
        $subject = $this->app->make(ResolvesPermissionSubject::class)->resolve(
            entityClass: $ownerClass,
            entityType: $entityType,
            panelId: $panelId,
            registrationKey: $registrationKey,
        );

        return $this->app->make(FilamentPermissionManager::class)
            ->defaultPermissionKeyBuilder($ability, $subject);
    }

    protected function grantOwnerPermission(
        User $user,
        string $ability,
        string $ownerClass,
        PermissionEntityType $entityType,
        ?string $registrationKey = null,
        ?string $panelId = 'admin',
    ): PermissionContract {
        $permission = Permission::findOrCreate(
            $this->permissionKeyForOwner($ability, $ownerClass, $entityType, $registrationKey, $panelId),
            'web',
        );

        $user->givePermissionTo($permission);

        return $permission;
    }
}
