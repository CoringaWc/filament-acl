<?php

declare(strict_types=1);

namespace Workbench\App\Providers;

use CoringaWc\FilamentAcl\Policies\RolePolicy;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Workbench\App\Models\Category;
use Workbench\App\Models\Post;
use Workbench\App\Models\Role;
use Workbench\App\Models\User;
use Workbench\App\Policies\CategoryPolicy;
use Workbench\App\Policies\PostPolicy;
use Workbench\App\Policies\UserPolicy;

class WorkbenchServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        App::setLocale(config('app.locale'));

        View::prependNamespace('filament-panels', __DIR__ . '/../../resources/views/vendor/filament-panels');
        View::addNamespace('workbench', __DIR__ . '/../../resources/views');

        Gate::policy(Category::class, CategoryPolicy::class);
        Gate::policy(Post::class, PostPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(User::class, UserPolicy::class);
    }
}
