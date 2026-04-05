<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Widgets;

use CoringaWc\FilamentAcl\Widgets\Concerns\HasWidgetPermissions;
use Filament\Widgets\Widget;
use Workbench\App\Filament\Concerns\EnsuresValidationErrorBag;
use Workbench\App\Models\Category;
use Workbench\App\Models\Post;

class PostsOverviewWidget extends Widget
{
    use EnsuresValidationErrorBag;
    use HasWidgetPermissions;

    protected static ?int $sort = 1;

    protected string $view = 'workbench::filament.widgets.posts-overview-widget';

    public static function getHeading(): string
    {
        return 'Resumo de Posts';
    }

    /**
     * @return array<string, int>
     */
    protected function getViewData(): array
    {
        return [
            'postCount' => Post::query()->count(),
            'draftCount' => Post::query()->where('status', 'draft')->count(),
            'categoryCount' => Category::query()->count(),
        ];
    }
}
