<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Contracts;

interface HasPermissionSubject
{
    public static function getPermissionSubject(): ?string;
}
