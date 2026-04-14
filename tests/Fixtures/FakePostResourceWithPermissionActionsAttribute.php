<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Tests\Fixtures;

use CoringaWc\FilamentAcl\Attributes\PermissionActions;
use CoringaWc\FilamentAcl\Resources\Concerns\HasResourcePermissions;
use Filament\Resources\Resource;

#[PermissionActions(['view'])]
class FakePostResourceWithPermissionActionsAttribute extends Resource
{
    use HasResourcePermissions;

    protected static ?string $model = FakePost::class;

    public static function getPermissionSubject(): ?string
    {
        return 'BlogPostsAttribute';
    }

    /**
     * @return array<int, string>
     */
    public static function getPermissionCustomActions(): array
    {
        return ['publish'];
    }
}
