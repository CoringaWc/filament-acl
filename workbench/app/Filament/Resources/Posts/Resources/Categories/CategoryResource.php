<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Resources\Posts\Resources\Categories;

use CoringaWc\FilamentAcl\Attributes\CustomPermissionActions;
use CoringaWc\FilamentAcl\Attributes\PermissionPanel;
use CoringaWc\FilamentAcl\Attributes\PermissionSubject;
use CoringaWc\FilamentAcl\Attributes\RegisterPermissions;
use CoringaWc\FilamentAcl\Attributes\SharedPermissionOwner;
use Workbench\App\Filament\Resources\Categories\CategoryResource as BaseCategoryResource;
use Workbench\App\Filament\Resources\Posts\PostResource as ParentPostResource;

#[SharedPermissionOwner(BaseCategoryResource::class)]
#[PermissionSubject('nested-category')]
#[CustomPermissionActions(['audit'])]
#[RegisterPermissions(true)]
#[PermissionPanel('admin')]
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
