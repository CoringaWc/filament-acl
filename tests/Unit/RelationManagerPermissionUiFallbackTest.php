<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Tests\Unit;

use CoringaWc\FilamentAcl\Resources\Permissions\PermissionResource;
use CoringaWc\FilamentAcl\Support\PermissionOwnerDiscovery;
use CoringaWc\FilamentAcl\Tests\Fixtures\FakePost;
use CoringaWc\FilamentAcl\Tests\Fixtures\FakePostsRelationManager;
use CoringaWc\FilamentAcl\Tests\TestCase;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use ReflectionMethod;

class RelationManagerPermissionUiFallbackTest extends TestCase
{
    public function test_resolve_relation_manager_label_prioritizes_get_title_over_default_relationship_title(): void
    {
        $discovery = app(PermissionOwnerDiscovery::class);
        $method = new ReflectionMethod(PermissionOwnerDiscovery::class, 'resolveRelationManagerLabel');

        $label = $method->invoke($discovery, FakePostsRelationManager::class, new FakePost, 'FakePage');

        self::assertSame('Fallback posts', $label);
    }

    public function test_resolve_relation_manager_label_falls_back_to_relationship_title_without_owner_context(): void
    {
        $discovery = app(PermissionOwnerDiscovery::class);
        $method = new ReflectionMethod(PermissionOwnerDiscovery::class, 'resolveRelationManagerLabel');

        $label = $method->invoke($discovery, FakePostsRelationManager::class, null, null);

        self::assertSame(FakePostsRelationManager::getRelationshipTitle(), $label);
    }

    public function test_resolve_relation_manager_icon_uses_relation_manager_icon(): void
    {
        $discovery = app(PermissionOwnerDiscovery::class);
        $method = new ReflectionMethod(PermissionOwnerDiscovery::class, 'resolveRelationManagerIcon');

        $icon = $method->invoke($discovery, FakePostsRelationManager::class, new FakePost, 'FakePage');

        self::assertSame(Heroicon::OutlinedDocumentText, $icon);
    }

    public function test_build_relation_manager_tab_sets_label_badge_and_icon(): void
    {
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

        self::assertInstanceOf(Tab::class, $tab);
        self::assertSame('Posts', $tab->getLabel());
        self::assertSame(2, $tab->getBadge());
        self::assertSame(Heroicon::OutlinedDocumentText, $tab->getIcon());
    }

    public function test_relation_manager_permission_actions_include_filament_inherent_actions_and_custom_actions(): void
    {
        self::assertSame([
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
    }
}
