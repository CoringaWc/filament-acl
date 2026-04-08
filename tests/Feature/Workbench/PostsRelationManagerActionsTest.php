<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Tests\Feature\Workbench;

use CoringaWc\FilamentAcl\Enums\PermissionEntityType;
use CoringaWc\FilamentAcl\Tests\TestCase;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;
use Workbench\App\Filament\Resources\Users\Pages\EditUser;
use Workbench\App\Filament\Resources\Users\Pages\ViewUser;
use Workbench\App\Filament\Resources\Users\RelationManagers\PostsRelationManager;
use Workbench\App\Models\Post;
use Workbench\App\Models\Role;
use Workbench\App\Models\User;

class PostsRelationManagerActionsTest extends TestCase
{
    public function test_rm_shows_create_edit_delete_actions_with_all_rm_permissions_on_edit_page(): void
    {
        $actor = $this->createUser();
        $owner = User::factory()->create();
        $posts = Post::factory()->count(2)->for($owner)->create();

        foreach (['viewAny', 'view', 'create', 'update', 'delete'] as $ability) {
            $this->grantOwnerPermission($actor, $ability, PostsRelationManager::class, PermissionEntityType::RelationManager);
        }

        $this->actingAs($actor);

        Livewire::test(PostsRelationManager::class, [
            'ownerRecord' => $owner,
            'pageClass' => EditUser::class,
        ])
            ->assertOk()
            ->assertActionVisible(TestAction::make('create')->table())
            ->assertActionVisible(TestAction::make('edit')->table($posts->first()))
            ->assertActionVisible(TestAction::make('delete')->table($posts->first()));
    }

    public function test_rm_shows_actions_for_super_admin_via_gate_before(): void
    {
        $actor = $this->createUser();
        $owner = User::factory()->create();
        $posts = Post::factory()->count(2)->for($owner)->create();

        $role = Role::firstOrCreate(
            ['name' => config('filament-acl.roles.protected.name', 'super_admin'), 'guard_name' => 'web'],
        );
        $actor->assignRole($role);

        $this->actingAs($actor);

        Livewire::test(PostsRelationManager::class, [
            'ownerRecord' => $owner,
            'pageClass' => EditUser::class,
        ])
            ->assertOk()
            ->assertActionVisible(TestAction::make('create')->table())
            ->assertActionVisible(TestAction::make('edit')->table($posts->first()))
            ->assertActionVisible(TestAction::make('delete')->table($posts->first()));
    }

    public function test_rm_shows_actions_on_view_page_because_is_read_only_is_overridden(): void
    {
        $actor = $this->createUser();
        $owner = User::factory()->create();
        $posts = Post::factory()->count(2)->for($owner)->create();

        foreach (['viewAny', 'view', 'create', 'update', 'delete'] as $ability) {
            $this->grantOwnerPermission($actor, $ability, PostsRelationManager::class, PermissionEntityType::RelationManager);
        }

        $this->actingAs($actor);

        // PostsRelationManager overrides isReadOnly() to return false,
        // so actions should be visible even on ViewRecord pages
        Livewire::test(PostsRelationManager::class, [
            'ownerRecord' => $owner,
            'pageClass' => ViewUser::class,
        ])
            ->assertOk()
            ->assertActionVisible(TestAction::make('create')->table())
            ->assertActionVisible(TestAction::make('edit')->table($posts->first()))
            ->assertActionVisible(TestAction::make('delete')->table($posts->first()));
    }

    public function test_rm_hides_mutating_actions_when_user_only_has_view_permissions(): void
    {
        $actor = $this->createUser();
        $owner = User::factory()->create();
        $posts = Post::factory()->count(2)->for($owner)->create();

        // Only grant viewAny and view — NO create/update/delete
        foreach (['viewAny', 'view'] as $ability) {
            $this->grantOwnerPermission($actor, $ability, PostsRelationManager::class, PermissionEntityType::RelationManager);
        }

        $this->actingAs($actor);

        Livewire::test(PostsRelationManager::class, [
            'ownerRecord' => $owner,
            'pageClass' => ViewUser::class,
        ])
            ->assertOk()
            ->assertActionHidden(TestAction::make('create')->table())
            ->assertActionHidden(TestAction::make('edit')->table($posts->first()))
            ->assertActionHidden(TestAction::make('delete')->table($posts->first()));
    }

    public function test_rm_hides_mutating_actions_when_user_has_no_rm_permissions_at_all(): void
    {
        $actor = $this->createUser();
        $owner = User::factory()->create();
        $posts = Post::factory()->count(2)->for($owner)->create();

        // No RM permissions — simulate a posts_only user that only has Resource permissions
        $this->actingAs($actor);

        Livewire::test(PostsRelationManager::class, [
            'ownerRecord' => $owner,
            'pageClass' => ViewUser::class,
        ])
            ->assertOk()
            ->assertActionHidden(TestAction::make('create')->table())
            ->assertActionHidden(TestAction::make('edit')->table($posts->first()))
            ->assertActionHidden(TestAction::make('delete')->table($posts->first()));
    }

    public function test_rm_shows_actions_on_view_page_for_super_admin(): void
    {
        $actor = $this->createUser();
        $owner = User::factory()->create();
        $posts = Post::factory()->count(2)->for($owner)->create();

        $role = Role::firstOrCreate(
            ['name' => config('filament-acl.roles.protected.name', 'super_admin'), 'guard_name' => 'web'],
        );
        $actor->assignRole($role);

        $this->actingAs($actor);

        // super_admin bypasses via Gate::before(), and isReadOnly() returns false
        Livewire::test(PostsRelationManager::class, [
            'ownerRecord' => $owner,
            'pageClass' => ViewUser::class,
        ])
            ->assertOk()
            ->assertActionVisible(TestAction::make('create')->table())
            ->assertActionVisible(TestAction::make('edit')->table($posts->first()))
            ->assertActionVisible(TestAction::make('delete')->table($posts->first()));
    }
}
