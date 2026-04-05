<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Tests\Fixtures;

use Illuminate\Foundation\Auth\User as Authenticatable;

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

    public function can($abilities, $arguments = []): bool
    {
        if (is_array($abilities)) {
            foreach ($abilities as $ability) {
                if (! in_array($ability, $this->permissions, true)) {
                    return false;
                }
            }

            return true;
        }

        return in_array((string) $abilities, $this->permissions, true);
    }
}
