<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Pages;

use BackedEnum;
use CoringaWc\FilamentAcl\Pages\Concerns\HasPagePermissions;
use Filament\Pages\Page;
use UnitEnum;
use Workbench\App\Filament\Concerns\EnsuresValidationErrorBag;
use Workbench\App\Models\Category;
use Workbench\App\Models\Post;
use Workbench\App\Models\User;

class ContentInsightsPage extends Page
{
    use EnsuresValidationErrorBag;
    use HasPagePermissions;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static string | UnitEnum | null $navigationGroup = 'Conteúdo';

    protected static ?string $navigationLabel = 'Insights de Conteúdo';

    protected string $view = 'workbench::filament.pages.content-insights-page';

    /**
     * @return array<string, int>
     */
    protected function getViewData(): array
    {
        return [
            'postCount' => Post::query()->count(),
            'categoryCount' => Category::query()->count(),
            'authorCount' => User::query()->count(),
        ];
    }
}
