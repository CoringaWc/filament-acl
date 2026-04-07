<?php

declare(strict_types=1);

namespace Workbench\Database\Seeders;

use CoringaWc\FilamentAcl\Contracts\ResolvesPermissionSubject;
use CoringaWc\FilamentAcl\Enums\PermissionEntityType;
use CoringaWc\FilamentAcl\FilamentPermissionManager;
use CoringaWc\FilamentAcl\Resources\Permissions\PermissionResource;
use CoringaWc\FilamentAcl\Support\Utils;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Workbench\App\Filament\Pages\ContentInsightsPage;
use Workbench\App\Filament\Resources\Posts\PostResource;
use Workbench\App\Filament\Widgets\PostsOverviewWidget;
use Workbench\App\Models\Role;

class PermissionSeeder extends Seeder
{
    protected string $guardName = 'web';

    protected string $panelId = 'admin';

    public function run(
        FilamentPermissionManager $permissionManager,
        PermissionRegistrar $permissionRegistrar,
    ): void {
        $permissionRegistrar->forgetCachedPermissions();

        Artisan::call('filament-acl:sync', [
            '--panel' => [$this->panelId],
            '--with-protected-role' => true,
        ]);

        $allPermissions = Utils::scopePermissionQueryToPanel(
            Permission::query()->orderBy('name'),
            $this->panelId,
        )->get();

        Role::query()
            ->whereIn('name', ['moderator', 'posts_only'])
            ->delete();

        Role::factory()
            ->moderator()
            ->create()
            ->syncPermissions($allPermissions);

        Role::factory()
            ->postsOnly()
            ->create()
            ->syncPermissions(array_filter([
                $this->permissionKeyForOwner(
                    ability: 'viewAny',
                    ownerClass: PostResource::class,
                    entityType: PermissionEntityType::Resource,
                    permissionManager: $permissionManager,
                ),
                $this->permissionKeyForOwner(
                    ability: 'view',
                    ownerClass: PostResource::class,
                    entityType: PermissionEntityType::Resource,
                    permissionManager: $permissionManager,
                ),
                $this->permissionKeyForOwner(
                    ability: 'view',
                    ownerClass: ContentInsightsPage::class,
                    entityType: PermissionEntityType::Page,
                    permissionManager: $permissionManager,
                ),
                $this->permissionKeyForOwner(
                    ability: 'view',
                    ownerClass: PostsOverviewWidget::class,
                    entityType: PermissionEntityType::Widget,
                    permissionManager: $permissionManager,
                ),
                $this->permissionKeyForOwner(
                    ability: 'publish',
                    ownerClass: PostResource::class,
                    entityType: PermissionEntityType::Resource,
                    permissionManager: $permissionManager,
                ),
            ]));

        $permissionRegistrar->forgetCachedPermissions();
    }

    protected function permissionKeyForOwner(
        string $ability,
        string $ownerClass,
        PermissionEntityType $entityType,
        FilamentPermissionManager $permissionManager,
    ): ?Permission {
        $subject = app(ResolvesPermissionSubject::class)->resolve(
            entityClass: $ownerClass,
            entityType: $entityType,
            panelId: $this->panelId,
            registrationKey: $ownerClass === PermissionResource::class ? 'filament-acl-permissions' : null,
        );

        return Utils::scopePermissionQueryToPanel(
            Permission::query()->where('name', $permissionManager->defaultPermissionKeyBuilder($ability, $subject)),
            $this->panelId,
        )->first();
    }
}
