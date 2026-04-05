<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Resources\Posts\Resources\Categories;

use Workbench\App\Filament\Resources\Categories\CategoryResource as BaseCategoryResource;
use Workbench\App\Filament\Resources\Posts\PostResource as ParentPostResource;

class CategoryResource extends BaseCategoryResource
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $parentResource = ParentPostResource::class;

    /**
     * @return array<string, class-string>
     */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'view' => Pages\ViewCategory::route('/{record}'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}
