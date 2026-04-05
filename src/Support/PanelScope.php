<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Support;

/**
 * @phpstan-type PanelScopeArray array{
 *     column: string,
 *     on_roles: bool,
 *     on_permissions: bool,
 *     type: string,
 *     length: int,
 *     nullable: bool,
 *     default: ?string
 * }
 */
final readonly class PanelScope
{
    public function __construct(
        public string $column,
        public bool $onRoles,
        public bool $onPermissions,
        public string $type,
        public int $length,
        public bool $nullable,
        public ?string $default,
    ) {}

    /**
     * @param  array<string, mixed>  $configuration
     */
    public static function fromArray(array $configuration): self
    {
        return new self(
            column: (string) ($configuration['column'] ?? 'panel'),
            onRoles: (bool) ($configuration['on_roles'] ?? false),
            onPermissions: (bool) ($configuration['on_permissions'] ?? false),
            type: (string) ($configuration['type'] ?? 'string'),
            length: (int) ($configuration['length'] ?? 50),
            nullable: (bool) ($configuration['nullable'] ?? false),
            default: isset($configuration['default']) ? (string) $configuration['default'] : null,
        );
    }

    public function withRuntimeOverrides(?bool $onRoles = null, ?bool $onPermissions = null): self
    {
        return new self(
            column: $this->column,
            onRoles: $onRoles ?? $this->onRoles,
            onPermissions: $onPermissions ?? $this->onPermissions,
            type: $this->type,
            length: $this->length,
            nullable: $this->nullable,
            default: $this->default,
        );
    }

    /**
     * @return PanelScopeArray
     */
    public function toArray(): array
    {
        return [
            'column' => $this->column,
            'on_roles' => $this->onRoles,
            'on_permissions' => $this->onPermissions,
            'type' => $this->type,
            'length' => $this->length,
            'nullable' => $this->nullable,
            'default' => $this->default,
        ];
    }
}
