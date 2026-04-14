<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Support;

final class DefaultPermissionActionRegistry
{
    /**
     * @return array<int, string>
     */
    public function forResource(): array
    {
        /** @var array<int, string> $methods */
        $methods = config('filament-acl.policies.methods', [
            'viewAny',
            'view',
            'create',
            'update',
            'delete',
        ]);

        return array_values(array_unique($methods));
    }

    /**
     * @return array<int, string>
     */
    public function forRelationManager(): array
    {
        /** @var array<int, string> $methods */
        $methods = config('filament-acl.relation_managers.actions', [
            'viewAny',
            'view',
            'create',
            'update',
            'delete',
            'deleteAny',
            'forceDelete',
            'forceDeleteAny',
            'restore',
            'restoreAny',
            'replicate',
            'reorder',
            'associate',
            'attach',
            'detach',
            'detachAny',
            'dissociate',
            'dissociateAny',
        ]);

        return array_values(array_unique($methods));
    }

    /**
     * @return array<int, string>
     */
    public function forPage(): array
    {
        /** @var array<int, string> $methods */
        $methods = config('filament-acl.pages.actions', [
            'view',
        ]);

        return array_values(array_unique($methods));
    }

    /**
     * @return array<int, string>
     */
    public function forWidget(): array
    {
        /** @var array<int, string> $methods */
        $methods = config('filament-acl.widgets.actions', [
            'view',
        ]);

        return array_values(array_unique($methods));
    }
}
