<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Tests\Fixtures;

use CoringaWc\FilamentAcl\RelationManagers\Concerns\HasRelationManagerPermissions;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;

class FakePostsRelationManager extends RelationManager
{
    use HasRelationManagerPermissions;

    protected static string $relationship = 'posts';

    public static function getPermissionSubject(): ?string
    {
        return 'TenantPosts';
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return 'Fallback posts';
    }

    public static function getIcon(Model $ownerRecord, string $pageClass): string | \BackedEnum | null
    {
        return Heroicon::OutlinedDocumentText;
    }

    /**
     * @return array<int, string>
     */
    public static function getPermissionCustomActions(): array
    {
        return ['publish'];
    }
}
