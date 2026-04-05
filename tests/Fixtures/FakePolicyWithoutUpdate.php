<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Tests\Fixtures;

class FakePolicyWithoutUpdate
{
    public function viewAny(FakeUser $user, string $model): bool
    {
        return true;
    }
}
