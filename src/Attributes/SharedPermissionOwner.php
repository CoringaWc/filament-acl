<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Attributes;

use Attribute;

/**
 * Declare that a Filament owner shares the permissions of another owner class.
 *
 * Usage:
 *   #[SharedPermissionOwner(PostResource::class)]
 *   class NestedPostResource extends Resource { use HasResourcePermissions; }
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class SharedPermissionOwner
{
    /**
     * @param  class-string  $ownerClass
     */
    public function __construct(
        public readonly string $ownerClass,
    ) {}
}
