<?php

declare(strict_types=1);

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

test('rm shows create edit delete actions with all rm permissions on edit page', function () {
    /** @var TestCase $this */
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
});
test('rm shows actions for super admin via gate before', function () {
    /** @var TestCase $this */
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
});
test('rm shows actions on view page because is read only is overridden', function () {
    /** @var TestCase $this */
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
});
test('rm hides mutating actions when user only has view permissions', function () {
    /** @var TestCase $this */
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
});
test('rm hides mutating actions when user has no rm permissions at all', function () {
    /** @var TestCase $this */
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
});
test('rm shows actions on view page for super admin', function () {
    /** @var TestCase $this */
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
});
