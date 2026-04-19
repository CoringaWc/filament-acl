<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Tests\Fixtures;

use BackedEnum;
use Illuminate\Foundation\Auth\User as Authenticatable;
use UnitEnum;

class FakeUser extends Authenticatable
{
    /**
     * @param  array<int, string>  $permissions
     */
    public function __construct(
        protected array $permissions = [],
    ) {
        parent::__construct();
    }

    /**
     * @param  iterable<mixed>|string|UnitEnum  $abilities
     * @param  array<int, mixed>  $arguments
     */
    public function can($abilities, $arguments = []): bool
    {
        if (is_iterable($abilities)) {
            foreach ($abilities as $ability) {
                if (! in_array($this->normalizeAbility($ability), $this->permissions, true)) {
                    return false;
                }
            }

            return true;
        }

        return in_array($this->normalizeAbility($abilities), $this->permissions, true);
    }

    private function normalizeAbility(mixed $ability): string
    {
        if ($ability instanceof BackedEnum) {
            return (string) $ability->value;
        }

        if ($ability instanceof UnitEnum) {
            return $ability->name;
        }

        return (string) $ability;
    }
}
