<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Support;

use CoringaWc\FilamentAcl\Enums\PermissionEntityType;

final readonly class PermissionOwnerRegistration
{
    /**
     * @param  class-string  $ownerClass
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public string $ownerClass,
        public PermissionEntityType $ownerType,
        public ?string $panelId = null,
        public ?string $registrationKey = null,
        public ?string $label = null,
        public ?string $sectionLabel = null,
        public ?string $relatedResource = null,
        public array $meta = [],
    ) {}

    public function uniqueKey(): string
    {
        return implode('|', [
            $this->ownerClass,
            $this->ownerType->value,
            $this->panelId ?? '',
            $this->registrationKey ?? '',
            $this->relatedResource ?? '',
        ]);
    }
}
