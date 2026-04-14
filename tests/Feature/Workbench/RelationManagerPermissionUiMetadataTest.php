<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Tests\Feature\Workbench;

use CoringaWc\FilamentAcl\Resources\Permissions\PermissionResource;
use CoringaWc\FilamentAcl\Support\PermissionOwnerDiscovery;
use CoringaWc\FilamentAcl\Support\PermissionOwnerRegistration;
use CoringaWc\FilamentAcl\Tests\TestCase;
use Filament\Facades\Filament;
use Filament\Panel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Artisan;
use ReflectionMethod;
use Workbench\App\Filament\Resources\Users\RelationManagers\PostsRelationManager;
use Workbench\App\Filament\Resources\Users\UserResource;

class RelationManagerPermissionUiMetadataTest extends TestCase
{
    public function test_discovered_relation_manager_uses_relationship_title_and_icon_metadata(): void
    {
        $discovery = app(PermissionOwnerDiscovery::class);
        $panel = Filament::getCurrentPanel();

        assert($panel instanceof Panel);

        $resourceRegistration = collect($discovery->discoverResources($panel))
            ->first(fn (PermissionOwnerRegistration $registration): bool => $registration->ownerClass === UserResource::class);

        self::assertInstanceOf(PermissionOwnerRegistration::class, $resourceRegistration);

        $relationManagerRegistration = collect($discovery->discoverRelationManagers($panel, $resourceRegistration))
            ->first(fn (PermissionOwnerRegistration $registration): bool => $registration->ownerClass === PostsRelationManager::class);

        self::assertInstanceOf(PermissionOwnerRegistration::class, $relationManagerRegistration);
        self::assertSame(__('workbench::workbench.relation_managers.posts'), $relationManagerRegistration->label);
        self::assertSame(Heroicon::OutlinedDocumentText, $relationManagerRegistration->meta['icon'] ?? null);
    }

    public function test_permission_resource_relation_manager_nodes_include_translated_label_icon_and_permissions(): void
    {
        Artisan::call('filament-acl:sync', [
            '--panel' => ['admin'],
        ]);

        $discovery = app(PermissionOwnerDiscovery::class);
        $panel = Filament::getCurrentPanel();

        assert($panel instanceof Panel);

        $resourceRegistration = collect($discovery->discoverResources($panel))
            ->first(fn (PermissionOwnerRegistration $registration): bool => $registration->ownerClass === UserResource::class);

        self::assertInstanceOf(PermissionOwnerRegistration::class, $resourceRegistration);

        $nodes = $this->callGetRelationManagerNodes($resourceRegistration);
        $postsNode = collect($nodes)
            ->first(fn (array $node): bool => $node['owner_class'] === PostsRelationManager::class);

        self::assertIsArray($postsNode);
        self::assertSame(__('workbench::workbench.relation_managers.posts'), $postsNode['label']);
        self::assertSame(Heroicon::OutlinedDocumentText, $postsNode['icon']);
        self::assertCount(count(PostsRelationManager::getPermissionActions()), $postsNode['options']);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function callGetRelationManagerNodes(PermissionOwnerRegistration $resourceRegistration): array
    {
        $method = new ReflectionMethod(PermissionResource::class, 'getRelationManagerNodes');

        return $method->invoke(null, $resourceRegistration);
    }
}
