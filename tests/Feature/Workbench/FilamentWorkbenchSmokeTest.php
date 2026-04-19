<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Tests\Feature\Workbench;

use CoringaWc\FilamentAcl\Enums\PermissionEntityType;
use CoringaWc\FilamentAcl\Resources\Permissions\PermissionResource;
use CoringaWc\FilamentAcl\Support\Utils;
use CoringaWc\FilamentAcl\Tests\TestCase;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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

class FilamentWorkbenchSmokeTest extends TestCase
{
    public function test_it_can_render_a_filament_resource_list_page_in_the_workbench(): void
    {
        $actor = $this->createUser();
        $posts = Post::factory()->count(2)->create();

        $this->grantOwnerPermission($actor, 'viewAny', PostResource::class, PermissionEntityType::Resource);
        $this->actingAs($actor);

        Livewire::test(ListPosts::class)
            ->assertOk()
            ->assertSee($posts->pluck('title')->all()[0])
            ->assertSee($posts->pluck('title')->all()[1]);
    }

    public function test_it_can_render_a_filament_resource_create_page_in_the_workbench(): void
    {
        $actor = $this->createUser();
        User::factory()->create();

        $this->grantOwnerPermission($actor, 'viewAny', PostResource::class, PermissionEntityType::Resource);
        $this->grantOwnerPermission($actor, 'create', PostResource::class, PermissionEntityType::Resource);
        $this->actingAs($actor);

        Livewire::test(CreatePost::class)
            ->assertOk();
    }

    public function test_it_can_reach_a_registered_filament_resource_route_over_http(): void
    {
        $actor = $this->createUser();
        $post = Post::factory()->create([
            'title' => 'Workbench HTTP Post',
        ]);

        $this->grantOwnerPermission($actor, 'viewAny', PostResource::class, PermissionEntityType::Resource);
        $this->actingAs($actor);

        $this->get(PostResource::getUrl('index'))
            ->assertOk()
            ->assertSee($post->title);
    }

    public function test_it_can_render_a_category_resource_list_page_in_the_workbench(): void
    {
        $actor = $this->createUser();
        $categories = Category::factory()->count(2)->create();

        $this->grantOwnerPermission($actor, 'viewAny', CategoryResource::class, PermissionEntityType::Resource);
        $this->actingAs($actor);

        $this->get(CategoryResource::getUrl('index'))
            ->assertOk()
            ->assertSee($categories->pluck('name')->all()[0])
            ->assertSee($categories->pluck('name')->all()[1]);
    }

    public function test_it_can_render_a_filament_relation_manager_in_the_workbench(): void
    {
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
    }

    public function test_it_can_render_a_post_categories_relation_manager_in_the_workbench(): void
    {
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
    }

    public function test_it_can_reach_a_nested_post_categories_resource_route_over_http(): void
    {
        $actor = $this->createUser();
        $post = Post::factory()->create([
            'title' => 'Nested Parent Post',
        ]);

        $this->grantOwnerPermission($actor, 'viewAny', PostResource::class, PermissionEntityType::Resource);
        $this->grantOwnerPermission($actor, 'view', PostResource::class, PermissionEntityType::Resource);
        $this->grantOwnerPermission($actor, 'viewAny', NestedPostCategoryResource::class, PermissionEntityType::Resource);
        $this->grantOwnerPermission($actor, 'create', NestedPostCategoryResource::class, PermissionEntityType::Resource);
        $this->actingAs($actor);

        self::assertTrue(PostResource::canAccess());
        self::assertTrue(NestedPostCategoryResource::canAccess());
        self::assertTrue(NestedPostCategoryResource::canCreate());

        $this->get(NestedPostCategoryResource::getUrl('create', ['post' => $post]))
            ->assertOk()
            ->assertSee('Name');
    }

    public function test_it_can_reach_a_nested_category_posts_resource_route_over_http(): void
    {
        $actor = $this->createUser();
        $category = Category::factory()->create([
            'name' => 'Nested Category',
        ]);

        $this->grantOwnerPermission($actor, 'viewAny', CategoryResource::class, PermissionEntityType::Resource);
        $this->grantOwnerPermission($actor, 'view', CategoryResource::class, PermissionEntityType::Resource);
        $this->grantOwnerPermission($actor, 'viewAny', NestedCategoryPostResource::class, PermissionEntityType::Resource);
        $this->grantOwnerPermission($actor, 'create', NestedCategoryPostResource::class, PermissionEntityType::Resource);
        $this->actingAs($actor);

        self::assertTrue(CategoryResource::canAccess());
        self::assertTrue(NestedCategoryPostResource::canAccess());
        self::assertTrue(NestedCategoryPostResource::canCreate());

        $this->get(NestedCategoryPostResource::getUrl('create', ['category' => $category]))
            ->assertOk()
            ->assertSee('Autor');
    }

    public function test_it_can_publish_filament_assets_into_the_workbench_public_directory(): void
    {
        Artisan::call('filament:assets');

        self::assertFileExists(public_path('css/filament/filament/app.css'));
        self::assertFileExists(public_path('fonts/filament/filament/inter/index.css'));
    }

    public function test_it_can_seed_a_default_admin_user_for_the_workbench_login(): void
    {
        Artisan::call('db:seed', [
            '--class' => DatabaseSeeder::class,
            '--no-interaction' => true,
        ]);

        $user = User::query()->where('email', 'admin@filament-acl.test')->first();

        self::assertNotNull($user);
        self::assertTrue(Hash::check('password', $user->getAuthPassword()));
        self::assertTrue(Auth::attempt([
            'email' => 'admin@filament-acl.test',
            'password' => 'password',
        ]));
    }

    public function test_it_can_render_the_plugin_permissions_resource_when_enabled_on_the_panel(): void
    {
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
    }

    public function test_it_can_render_the_plugin_permissions_edit_page_without_checkbox_errors(): void
    {
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
    }

    public function test_it_hides_the_protected_role_from_the_user_edit_form(): void
    {
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
    }

    public function test_custom_action_publish_is_visible_only_for_draft_posts(): void
    {
        $actor = $this->createUser();
        $draftPost = Post::factory()->create(['status' => 'draft']);
        $lockedPost = Post::factory()->create(['status' => 'locked']);

        $this->grantOwnerPermission($actor, 'viewAny', PostResource::class, PermissionEntityType::Resource);
        $this->grantOwnerPermission($actor, 'publish', PostResource::class, PermissionEntityType::Resource);
        $this->actingAs($actor);

        Livewire::test(ListPosts::class)
            ->assertOk()
            ->assertActionVisible(TestAction::make('publish')->table($draftPost))
            ->assertActionHidden(TestAction::make('publish')->table($lockedPost));
    }

    public function test_custom_action_publish_is_authorized_only_with_permission(): void
    {
        $actor = $this->createUser();
        $draftPost = Post::factory()->create(['status' => 'draft']);

        $this->grantOwnerPermission($actor, 'viewAny', PostResource::class, PermissionEntityType::Resource);
        $this->actingAs($actor);

        // Without 'publish' permission, the action should not be authorized
        self::assertFalse($actor->can('publish', [$draftPost, PostResource::class]));

        // Grant publish permission
        $this->grantOwnerPermission($actor, 'publish', PostResource::class, PermissionEntityType::Resource);

        // Now it should be authorized
        self::assertTrue($actor->can('publish', [$draftPost, PostResource::class]));
    }

    public function test_custom_action_archive_is_visible_only_for_categories_with_description(): void
    {
        $actor = $this->createUser();
        $categoryWithDesc = Category::factory()->create(['description' => 'Some description']);
        $categoryNoDesc = Category::factory()->create(['description' => null]);

        $this->grantOwnerPermission($actor, 'viewAny', CategoryResource::class, PermissionEntityType::Resource);
        $this->grantOwnerPermission($actor, 'archive', CategoryResource::class, PermissionEntityType::Resource);
        $this->actingAs($actor);

        Livewire::test(ListCategories::class)
            ->assertOk()
            ->assertActionVisible(TestAction::make('archive')->table($categoryWithDesc))
            ->assertActionHidden(TestAction::make('archive')->table($categoryNoDesc));
    }

    public function test_custom_action_archive_is_authorized_only_with_permission(): void
    {
        $actor = $this->createUser();
        $category = Category::factory()->create(['description' => 'Some description']);

        $this->grantOwnerPermission($actor, 'viewAny', CategoryResource::class, PermissionEntityType::Resource);
        $this->actingAs($actor);

        // Without 'archive' permission
        self::assertFalse($actor->can('archive', [$category, CategoryResource::class]));

        // Grant archive permission
        $this->grantOwnerPermission($actor, 'archive', CategoryResource::class, PermissionEntityType::Resource);

        // Now authorized
        self::assertTrue($actor->can('archive', [$category, CategoryResource::class]));
    }

    public function test_custom_permissions_appear_in_the_permission_resource_ui(): void
    {
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
    }

    public function test_it_can_render_the_page_and_widget_examples_with_permissions(): void
    {
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
    }
}
