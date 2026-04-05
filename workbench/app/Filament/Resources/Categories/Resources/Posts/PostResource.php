<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Resources\Categories\Resources\Posts;

use Workbench\App\Filament\Resources\Categories\CategoryResource as ParentCategoryResource;
use Workbench\App\Filament\Resources\Posts\PostResource as BasePostResource;

class PostResource extends BasePostResource
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $parentResource = ParentCategoryResource::class;

    /**
     * @return array<string, class-string>
     */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPosts::route('/'),
            'create' => Pages\CreatePost::route('/create'),
            'view' => Pages\ViewPost::route('/{record}'),
            'edit' => Pages\EditPost::route('/{record}/edit'),
        ];
    }
}
