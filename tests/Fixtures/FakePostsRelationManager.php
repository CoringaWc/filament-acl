<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Tests\Fixtures;

use CoringaWc\FilamentAcl\RelationManagers\Concerns\HasRelationManagerPermissions;
use Filament\Resources\RelationManagers\RelationManager;

class FakePostsRelationManager extends RelationManager
{
    use HasRelationManagerPermissions;

    protected static string $relationship = 'posts';

    public static function getPermissionSubject(): ?string
    {
        return 'TenantPosts';
    }

    /**
     * @return array<int, string>
     */
    public static function getPermissionCustomActions(): array
    {
        return ['publish'];
    }
}
