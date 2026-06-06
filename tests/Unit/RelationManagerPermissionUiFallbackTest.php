<?php

declare(strict_types=1);

use CoringaWc\FilamentAcl\Resources\Permissions\PermissionResource;
use CoringaWc\FilamentAcl\Support\PermissionOwnerDiscovery;
use CoringaWc\FilamentAcl\Tests\Fixtures\FakePost;
use CoringaWc\FilamentAcl\Tests\Fixtures\FakePostsRelationManager;
use CoringaWc\FilamentAcl\Tests\TestCase;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;

test('resolve relation manager label prioritizes get title over default relationship title', function () {
    /** @var TestCase $this */
    $discovery = app(PermissionOwnerDiscovery::class);
    $method = new ReflectionMethod(PermissionOwnerDiscovery::class, 'resolveRelationManagerLabel');

    $label = $method->invoke($discovery, FakePostsRelationManager::class, new FakePost, 'FakePage');

    $this->assertSame('Fallback posts', $label);
});
test('resolve relation manager label falls back to relationship title without owner context', function () {
    /** @var TestCase $this */
    $discovery = app(PermissionOwnerDiscovery::class);
    $method = new ReflectionMethod(PermissionOwnerDiscovery::class, 'resolveRelationManagerLabel');

    $label = $method->invoke($discovery, FakePostsRelationManager::class, null, null);

    $this->assertSame(FakePostsRelationManager::getRelationshipTitle(), $label);
});
test('resolve relation manager icon uses relation manager icon', function () {
    /** @var TestCase $this */
    $discovery = app(PermissionOwnerDiscovery::class);
    $method = new ReflectionMethod(PermissionOwnerDiscovery::class, 'resolveRelationManagerIcon');

    $icon = $method->invoke($discovery, FakePostsRelationManager::class, new FakePost, 'FakePage');

    $this->assertSame(Heroicon::OutlinedDocumentText, $icon);
});
test('build relation manager tab sets label badge and icon', function () {
    /** @var TestCase $this */
    $method = new ReflectionMethod(PermissionResource::class, 'buildRelationManagerTab');

    $tab = $method->invoke(null, [
        'label' => 'Posts',
        'icon' => Heroicon::OutlinedDocumentText,
        'state_path' => 'data.relation_managers.posts',
        'options' => [
            'viewAny' => 'View any',
            'create' => 'Create',
        ],
    ]);

    $this->assertInstanceOf(Tab::class, $tab);
    $this->assertSame('Posts', $tab->getLabel());
    // Filament normalizes badge values to strings in newer releases while older
    // versions keep the integer, so compare loosely to stay green across the
    // prefer-lowest and prefer-stable dependency matrices.
    $this->assertEquals(2, $tab->getBadge());
    $this->assertSame(Heroicon::OutlinedDocumentText, $tab->getIcon());
});
test('relation manager permission actions include filament inherent actions and custom actions', function () {
    /** @var TestCase $this */
    $this->assertSame([
        'viewAny',
        'view',
        'create',
        'update',
        'delete',
        'deleteAny',
        'forceDelete',
        'forceDeleteAny',
        'restore',
        'restoreAny',
        'replicate',
        'reorder',
        'associate',
        'attach',
        'detach',
        'detachAny',
        'dissociate',
        'dissociateAny',
        'publish',
    ], FakePostsRelationManager::getPermissionActions());
});
