<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Support;

use ReflectionClass;

/**
 * Reads PHP 8 Attributes from Filament owner classes (Resources, RelationManagers, Pages, Widgets).
 *
 * Provides a centralized way to resolve permission-related configuration
 * declared via Attributes instead of method overrides.
 */
final class PermissionAttributeReader
{
    /**
     * Read a single attribute instance from a class.
     *
     * @template T of object
     *
     * @param  class-string  $class
     * @param  class-string<T>  $attributeClass
     * @return T|null
     */
    public static function read(string $class, string $attributeClass): ?object
    {
        $attributes = (new ReflectionClass($class))->getAttributes($attributeClass);

        if ($attributes === []) {
            return null;
        }

        return $attributes[0]->newInstance();
    }

    /**
     * Check if a class has a specific attribute.
     *
     * @param  class-string  $class
     * @param  class-string  $attributeClass
     */
    public static function has(string $class, string $attributeClass): bool
    {
        return (new ReflectionClass($class))->getAttributes($attributeClass) !== [];
    }
}
