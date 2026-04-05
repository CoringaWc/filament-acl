<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Contracts;

interface HasPermissionActions
{
    /**
     * @return array<int, string>
     */
    public static function getPermissionCustomActions(): array;
}
