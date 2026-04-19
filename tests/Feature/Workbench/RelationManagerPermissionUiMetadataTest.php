<?php

declare(strict_types=1);

use CoringaWc\FilamentAcl\Resources\Permissions\PermissionResource;
use CoringaWc\FilamentAcl\Support\PermissionOwnerDiscovery;
use CoringaWc\FilamentAcl\Support\PermissionOwnerRegistration;
use CoringaWc\FilamentAcl\Tests\TestCase;
use Filament\Facades\Filament;
use Filament\Panel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Artisan;
use Workbench\App\Filament\Resources\Users\RelationManagers\PostsRelationManager;
use Workbench\App\Filament\Resources\Users\UserResource;

test('discovered relation manager uses relationship title and icon metadata', function () {
    /** @var TestCase $this */
    $discovery = app(PermissionOwnerDiscovery::class);
    $panel = Filament::getCurrentPanel();

    assert($panel instanceof Panel);

    $resourceRegistration = collect($discovery->discoverResources($panel))
        ->first(fn (PermissionOwnerRegistration $registration): bool => $registration->ownerClass === UserResource::class);

    $this->assertInstanceOf(PermissionOwnerRegistration::class, $resourceRegistration);

    $relationManagerRegistration = collect($discovery->discoverRelationManagers($panel, $resourceRegistration))
        ->first(fn (PermissionOwnerRegistration $registration): bool => $registration->ownerClass === PostsRelationManager::class);

    $this->assertInstanceOf(PermissionOwnerRegistration::class, $relationManagerRegistration);
    $this->assertSame(__('workbench::workbench.relation_managers.posts'), $relationManagerRegistration->label);
    $this->assertSame(Heroicon::OutlinedDocumentText, $relationManagerRegistration->meta['icon'] ?? null);
});
test('permission resource relation manager nodes include translated label icon and permissions', function () {
    /** @var TestCase $this */
    Artisan::call('filament-acl:sync', [
        '--panel' => ['admin'],
    ]);

    $discovery = app(PermissionOwnerDiscovery::class);
    $panel = Filament::getCurrentPanel();

    assert($panel instanceof Panel);

    $resourceRegistration = collect($discovery->discoverResources($panel))
        ->first(fn (PermissionOwnerRegistration $registration): bool => $registration->ownerClass === UserResource::class);

    $this->assertInstanceOf(PermissionOwnerRegistration::class, $resourceRegistration);

    $nodes = callGetRelationManagerNodes($resourceRegistration);
    $postsNode = collect($nodes)
        ->first(fn (array $node): bool => $node['owner_class'] === PostsRelationManager::class);

    $this->assertIsArray($postsNode);
    $this->assertSame(__('workbench::workbench.relation_managers.posts'), $postsNode['label']);
    $this->assertSame(Heroicon::OutlinedDocumentText, $postsNode['icon']);
    $this->assertCount(count(PostsRelationManager::getPermissionActions()), $postsNode['options']);
});
/**
 * @return array<int, array<string, mixed>>
 */
function callGetRelationManagerNodes(PermissionOwnerRegistration $resourceRegistration): array
{
    $method = new ReflectionMethod(PermissionResource::class, 'getRelationManagerNodes');

    return $method->invoke(null, $resourceRegistration);

}
