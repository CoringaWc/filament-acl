<?php

declare(strict_types=1);

use CoringaWc\FilamentAcl\Enums\PermissionEntityType;
use CoringaWc\FilamentAcl\Resources\Permissions\PermissionResource;
use CoringaWc\FilamentAcl\Support\Utils;
use CoringaWc\FilamentAcl\Tests\TestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Workbench\App\Filament\Pages\ContentInsightsPage;
use Workbench\App\Filament\Pages\Dashboard;
use Workbench\App\Filament\Resources\Categories\CategoryResource;
use Workbench\App\Filament\Resources\Categories\Pages\ListCategories;
use Workbench\App\Filament\Resources\Categories\Resources\Posts\PostResource as NestedCategoryPostResource;
use Workbench\App\Filament\Resources\Posts\Pages\CreatePost;
use Workbench\App\Filament\Resources\Posts\Pages\EditPost;
use Workbench\App\Filament\Resources\Posts\Pages\ListPosts;
use Workbench\App\Filament\Resources\Posts\PostResource;
use Workbench\App\Filament\Resources\Posts\RelationManagers\CategoriesRelationManager;
use Workbench\App\Filament\Resources\Posts\Resources\Categories\CategoryResource as NestedPostCategoryResource;
use Workbench\App\Filament\Resources\Users\Pages\EditUser;
use Workbench\App\Filament\Resources\Users\RelationManagers\PostsRelationManager;
use Workbench\App\Filament\Resources\Users\UserResource;
use Workbench\App\Filament\Widgets\PostsOverviewWidget;
use Workbench\App\Models\Category;
use Workbench\App\Models\Post;
use Workbench\App\Models\Role;
use Workbench\App\Models\User;
use Workbench\Database\Seeders\DatabaseSeeder;

test('it can render a filament resource list page in the workbench', function () {
    /** @var TestCase $this */
    $actor = $this->createUser();
    $posts = Post::factory()->count(2)->create();

    $this->grantOwnerPermission($actor, 'viewAny', PostResource::class, PermissionEntityType::Resource);
    $this->actingAs($actor);

    Livewire::test(ListPosts::class)
        ->assertOk()
        ->assertSee($posts->pluck('title')->all()[0])
        ->assertSee($posts->pluck('title')->all()[1]);
});
test('it can render a filament resource create page in the workbench', function () {
    /** @var TestCase $this */
    $actor = $this->createUser();
    User::factory()->create();

    $this->grantOwnerPermission($actor, 'viewAny', PostResource::class, PermissionEntityType::Resource);
    $this->grantOwnerPermission($actor, 'create', PostResource::class, PermissionEntityType::Resource);
    $this->actingAs($actor);

    Livewire::test(CreatePost::class)
        ->assertOk();
});
test('it can reach a registered filament resource route over http', function () {
    /** @var TestCase $this */
    $actor = $this->createUser();
    $post = Post::factory()->create([
        'title' => 'Workbench HTTP Post',
    ]);

    $this->grantOwnerPermission($actor, 'viewAny', PostResource::class, PermissionEntityType::Resource);
    $this->actingAs($actor);

    $this->get(PostResource::getUrl('index'))
        ->assertOk()
        ->assertSee($post->title);
});
test('it can render a category resource list page in the workbench', function () {
    /** @var TestCase $this */
    $actor = $this->createUser();
    $categories = Category::factory()->count(2)->create();

    $this->grantOwnerPermission($actor, 'viewAny', CategoryResource::class, PermissionEntityType::Resource);
    $this->actingAs($actor);

    $this->get(CategoryResource::getUrl('index'))
        ->assertOk()
        ->assertSee($categories->pluck('name')->all()[0])
        ->assertSee($categories->pluck('name')->all()[1]);
});
test('it can render a filament relation manager in the workbench', function () {
    /** @var TestCase $this */
    $actor = $this->createUser();
    $owner = User::factory()->create();
    $posts = Post::factory()->count(2)->for($owner)->create();

    $this->grantOwnerPermission($actor, 'viewAny', PostsRelationManager::class, PermissionEntityType::RelationManager);
    $this->actingAs($actor);

    Livewire::test(PostsRelationManager::class, [
        'ownerRecord' => $owner,
        'pageClass' => EditUser::class,
    ])
        ->assertOk()
        ->assertSee($posts->pluck('title')->all()[0])
        ->assertSee($posts->pluck('title')->all()[1]);
});
test('it can render a post categories relation manager in the workbench', function () {
    /** @var TestCase $this */
    $actor = $this->createUser();
    $post = Post::factory()->create();
    $categories = Category::factory()->count(2)->create();
    $post->categories()->attach($categories->modelKeys());

    $this->grantOwnerPermission($actor, 'viewAny', NestedPostCategoryResource::class, PermissionEntityType::Resource);
    $this->actingAs($actor);

    Livewire::test(CategoriesRelationManager::class, [
        'ownerRecord' => $post,
        'pageClass' => EditPost::class,
    ])
        ->assertOk()
        ->assertSee($categories->pluck('name')->all()[0])
        ->assertSee($categories->pluck('name')->all()[1]);
});
test('it can reach a nested post categories resource route over http', function () {
    /** @var TestCase $this */
    $actor = $this->createUser();
    $post = Post::factory()->create([
        'title' => 'Nested Parent Post',
    ]);

    $this->grantOwnerPermission($actor, 'viewAny', PostResource::class, PermissionEntityType::Resource);
    $this->grantOwnerPermission($actor, 'view', PostResource::class, PermissionEntityType::Resource);
    $this->grantOwnerPermission($actor, 'viewAny', NestedPostCategoryResource::class, PermissionEntityType::Resource);
    $this->grantOwnerPermission($actor, 'create', NestedPostCategoryResource::class, PermissionEntityType::Resource);
    $this->actingAs($actor);

    $this->assertTrue(PostResource::canAccess());
    $this->assertTrue(NestedPostCategoryResource::canAccess());
    $this->assertTrue(NestedPostCategoryResource::canCreate());

    $this->get(NestedPostCategoryResource::getUrl('create', ['post' => $post]))
        ->assertOk()
        ->assertSee('Name');
});
test('it can reach a nested category posts resource route over http', function () {
    /** @var TestCase $this */
    $actor = $this->createUser();
    $category = Category::factory()->create([
        'name' => 'Nested Category',
    ]);

    $this->grantOwnerPermission($actor, 'viewAny', CategoryResource::class, PermissionEntityType::Resource);
    $this->grantOwnerPermission($actor, 'view', CategoryResource::class, PermissionEntityType::Resource);
    $this->grantOwnerPermission($actor, 'viewAny', NestedCategoryPostResource::class, PermissionEntityType::Resource);
    $this->grantOwnerPermission($actor, 'create', NestedCategoryPostResource::class, PermissionEntityType::Resource);
    $this->actingAs($actor);

    $this->assertTrue(CategoryResource::canAccess());
    $this->assertTrue(NestedCategoryPostResource::canAccess());
    $this->assertTrue(NestedCategoryPostResource::canCreate());

    $this->get(NestedCategoryPostResource::getUrl('create', ['category' => $category]))
        ->assertOk()
        ->assertSee('Autor');
});
test('it can publish filament assets into the workbench public directory', function () {
    /** @var TestCase $this */
    Artisan::call('filament:assets');

    $this->assertFileExists(public_path('css/filament/filament/app.css'));
    $this->assertFileExists(public_path('fonts/filament/filament/inter/index.css'));
});
test('it can seed a default admin user for the workbench login', function () {
    /** @var TestCase $this */
    Artisan::call('db:seed', [
        '--class' => DatabaseSeeder::class,
        '--no-interaction' => true,
    ]);

    $user = User::query()->where('email', 'admin@filament-acl.test')->first();

    $this->assertNotNull($user);
    $this->assertTrue(Hash::check('password', $user->getAuthPassword()));
    $this->assertTrue(Auth::attempt([
        'email' => 'admin@filament-acl.test',
        'password' => 'password',
    ]));
});
test('it can render the plugin permissions resource when enabled on the panel', function () {
    /** @var TestCase $this */
    Artisan::call('db:seed', [
        '--class' => DatabaseSeeder::class,
        '--no-interaction' => true,
    ]);

    $this->actingAs(
        User::query()->where('email', 'admin@filament-acl.test')->firstOrFail(),
    );

    $this->get(PermissionResource::getUrl('create', configuration: 'filament-acl-permissions'))
        ->assertOk()
        ->assertSee('Usuário')
        ->assertSee('Insights de Conteúdo')
        ->assertSee('Resumo de Posts')
        ->assertSee(__('workbench::workbench.custom_permissions.export'))
        ->assertDontSee(Utils::getProtectedRoleName());
});
test('it can render the plugin permissions edit page without checkbox errors', function () {
    /** @var TestCase $this */
    Artisan::call('db:seed', [
        '--class' => DatabaseSeeder::class,
        '--no-interaction' => true,
    ]);

    $this->actingAs(
        User::query()->where('email', 'admin@filament-acl.test')->firstOrFail(),
    );

    $moderatorRole = Role::query()
        ->where('name', 'moderator')
        ->firstOrFail();

    $this->get(PermissionResource::getUrl('edit', [
        'record' => $moderatorRole->getKey(),
    ], configuration: 'filament-acl-permissions'))
        ->assertOk()
        ->assertSee('Posts')
        ->assertSee(__('filament-acl::filament-acl.permission_labels.view_any'))
        ->assertSee('Insights de Conteúdo')
        ->assertSee('Resumo de Posts')
        ->assertDontSee(Utils::getProtectedRoleName());
});
test('it marks the master toggle as selected when all visible permissions are assigned', function () {
    /** @var TestCase $this */
    Artisan::call('db:seed', [
        '--class' => DatabaseSeeder::class,
        '--no-interaction' => true,
    ]);

    $moderatorRole = Role::query()
        ->where('name', 'moderator')
        ->firstOrFail();

    $state = PermissionResource::fillPermissionGroupState(
        $moderatorRole->permissions()
            ->allRelatedIds()
            ->all(),
    );

    expect($state['select_all'])->toBeTrue();
});
test('it keeps the master toggle unselected when only part of the visible permissions are assigned', function () {
    /** @var TestCase $this */
    Artisan::call('db:seed', [
        '--class' => DatabaseSeeder::class,
        '--no-interaction' => true,
    ]);

    $postsOnlyRole = Role::query()
        ->where('name', 'posts_only')
        ->firstOrFail();

    $state = PermissionResource::fillPermissionGroupState(
        $postsOnlyRole->permissions()
            ->allRelatedIds()
            ->all(),
    );

    expect($state['select_all'])->toBeFalse();
});
test('it hides the protected role from the user edit form', function () {
    /** @var TestCase $this */
    Artisan::call('db:seed', [
        '--class' => DatabaseSeeder::class,
        '--no-interaction' => true,
    ]);

    $this->actingAs(
        User::query()->where('email', 'admin@filament-acl.test')->firstOrFail(),
    );

    $viewer = User::query()
        ->where('email', 'posts@filament-acl.test')
        ->firstOrFail();

    $this->get(UserResource::getUrl('edit', [
        'record' => $viewer->getKey(),
    ]))
        ->assertOk()
        ->assertSee(__('workbench::workbench.roles.moderator'))
        ->assertDontSee(Utils::getProtectedRoleName());
});
test('custom action publish is visible only for draft posts', function () {
    /** @var TestCase $this */
    $actor = $this->createUser();
    $draftPost = Post::factory()->create(['status' => 'draft']);
    $lockedPost = Post::factory()->create(['status' => 'locked']);

    $this->grantOwnerPermission($actor, 'viewAny', PostResource::class, PermissionEntityType::Resource);
    $this->grantOwnerPermission($actor, 'publish', PostResource::class, PermissionEntityType::Resource);
    $this->actingAs($actor);

    /** @var Testable<ListPosts> $postsPage */
    $postsPage = Livewire::test(ListPosts::class);

    $postsPage->assertOk();
    $postsPage->assertTableActionVisible('publish', $draftPost);
    $postsPage->assertTableActionHidden('publish', $lockedPost);
});
test('custom action publish is authorized only with permission', function () {
    /** @var TestCase $this */
    $actor = $this->createUser();
    $draftPost = Post::factory()->create(['status' => 'draft']);

    $this->grantOwnerPermission($actor, 'viewAny', PostResource::class, PermissionEntityType::Resource);
    $this->actingAs($actor);

    // Without 'publish' permission, the action should not be authorized
    $this->assertFalse($actor->can('publish', [$draftPost, PostResource::class]));

    // Grant publish permission
    $this->grantOwnerPermission($actor, 'publish', PostResource::class, PermissionEntityType::Resource);

    // Now it should be authorized
    $this->assertTrue($actor->can('publish', [$draftPost, PostResource::class]));
});
test('custom action archive is visible only for categories with description', function () {
    /** @var TestCase $this */
    $actor = $this->createUser();
    $categoryWithDesc = Category::factory()->create(['description' => 'Some description']);
    $categoryNoDesc = Category::factory()->create(['description' => null]);

    $this->grantOwnerPermission($actor, 'viewAny', CategoryResource::class, PermissionEntityType::Resource);
    $this->grantOwnerPermission($actor, 'archive', CategoryResource::class, PermissionEntityType::Resource);
    $this->actingAs($actor);

    /** @var Testable<ListCategories> $categoriesPage */
    $categoriesPage = Livewire::test(ListCategories::class);

    $categoriesPage->assertOk();
    $categoriesPage->assertTableActionVisible('archive', $categoryWithDesc);
    $categoriesPage->assertTableActionHidden('archive', $categoryNoDesc);
});
test('custom action archive is authorized only with permission', function () {
    /** @var TestCase $this */
    $actor = $this->createUser();
    $category = Category::factory()->create(['description' => 'Some description']);

    $this->grantOwnerPermission($actor, 'viewAny', CategoryResource::class, PermissionEntityType::Resource);
    $this->actingAs($actor);

    // Without 'archive' permission
    $this->assertFalse($actor->can('archive', [$category, CategoryResource::class]));

    // Grant archive permission
    $this->grantOwnerPermission($actor, 'archive', CategoryResource::class, PermissionEntityType::Resource);

    // Now authorized
    $this->assertTrue($actor->can('archive', [$category, CategoryResource::class]));
});
test('custom permissions appear in the permission resource ui', function () {
    /** @var TestCase $this */
    Artisan::call('db:seed', [
        '--class' => DatabaseSeeder::class,
        '--no-interaction' => true,
    ]);

    $this->actingAs(
        User::query()->where('email', 'admin@filament-acl.test')->firstOrFail(),
    );

    $this->get(PermissionResource::getUrl('create', configuration: 'filament-acl-permissions'))
        ->assertOk()
        ->assertSee(__('filament-acl::filament-acl.permission_labels.publish'))
        ->assertSee(__('filament-acl::filament-acl.permission_labels.archive'));
});
test('it can render the page and widget examples with permissions', function () {
    /** @var TestCase $this */
    $actor = $this->createUser();

    $this->grantOwnerPermission($actor, 'view', ContentInsightsPage::class, PermissionEntityType::Page);
    $this->grantOwnerPermission($actor, 'view', PostsOverviewWidget::class, PermissionEntityType::Widget);
    $this->actingAs($actor);

    $this->get(ContentInsightsPage::getUrl())
        ->assertOk()
        ->assertSee('Insights de Conteúdo');

    $this->get(Dashboard::getUrl())
        ->assertOk()
        ->assertSee('Total de posts');
});
