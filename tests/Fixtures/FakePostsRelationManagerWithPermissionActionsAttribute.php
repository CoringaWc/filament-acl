<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Tests\Fixtures;

use CoringaWc\FilamentAcl\Attributes\PermissionActions;
use CoringaWc\FilamentAcl\RelationManagers\Concerns\HasRelationManagerPermissions;
use Filament\Resources\RelationManagers\RelationManager;

#[PermissionActions(['view'])]
class FakePostsRelationManagerWithPermissionActionsAttribute extends RelationManager
{
    use HasRelationManagerPermissions;

    protected static string $relationship = 'posts';

    public static function getPermissionSubject(): ?string
    {
        return 'TenantPostsAttribute';
    }

    /**
     * @return array<int, string>
     */
    public static function getPermissionCustomActions(): array
    {
        return ['publish'];
    }
}
