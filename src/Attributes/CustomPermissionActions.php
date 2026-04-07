<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Attributes;

use Attribute;

/**
 * Define additional custom permission actions for a Filament owner.
 *
 * Usage:
 *   #[CustomPermissionActions(['archive', 'export'])]
 *   class PostResource extends Resource { use HasResourcePermissions; }
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class CustomPermissionActions
{
    /**
     * @param  array<int, string>  $actions
     */
    public function __construct(
        public readonly array $actions,
    ) {}
}
