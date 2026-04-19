<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Tests\Feature;

use CoringaWc\FilamentAcl\Enums\PermissionEntityType;
use CoringaWc\FilamentAcl\Support\PermissionAction;
use CoringaWc\FilamentAcl\Support\PermissionGate;
use CoringaWc\FilamentAcl\Tests\TestCase;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Support\Facades\Gate;
use LogicException;

class PermissionGateTest extends TestCase
{
    public function test_it_allows_when_policy_is_missing_and_strict_mode_is_disabled(): void
    {
        config()->set('filament-acl.plugin.strict_mode', false);

        $response = $this->appContainer()->make(PermissionGate::class)->inspect(
            user: new PermissionGateUser,
            ability: 'update',
            target: new PermissionGateModelWithoutPolicy,
            action: null,
        );

        self::assertTrue($response->allowed());
    }

    public function test_it_throws_when_policy_is_missing_and_strict_mode_is_enabled(): void
    {
        config()->set('filament-acl.plugin.strict_mode', true);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Strict authorization mode is enabled, but no policy was found');

        $this->appContainer()->make(PermissionGate::class)->inspect(
            user: new PermissionGateUser,
            ability: 'update',
            target: new PermissionGateModelWithoutPolicy,
            action: null,
        );
    }

    public function test_it_allows_when_policy_method_is_missing_and_strict_mode_is_disabled(): void
    {
        config()->set('filament-acl.plugin.strict_mode', false);
        Gate::policy(PermissionGateModelWithPartialPolicy::class, PermissionGatePartialPolicy::class);

        $response = $this->appContainer()->make(PermissionGate::class)->inspect(
            user: new PermissionGateUser,
            ability: 'delete',
            target: new PermissionGateModelWithPartialPolicy,
            action: null,
        );

        self::assertTrue($response->allowed());
    }

    public function test_it_throws_when_policy_method_is_missing_and_strict_mode_is_enabled(): void
    {
        config()->set('filament-acl.plugin.strict_mode', true);
        Gate::policy(PermissionGateModelWithPartialPolicy::class, PermissionGatePartialPolicy::class);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Strict authorization mode is enabled, but no [delete()] method was found');

        $this->appContainer()->make(PermissionGate::class)->inspect(
            user: new PermissionGateUser,
            ability: 'delete',
            target: new PermissionGateModelWithPartialPolicy,
            action: null,
        );
    }

    public function test_it_respects_gate_before_callbacks_in_contextual_flow(): void
    {
        config()->set('filament-acl.plugin.strict_mode', false);
        Gate::before(static function (PermissionGateUser $user, string $ability): ?bool {
            if ($ability === 'update') {
                return true;
            }

            return null;
        });

        $response = $this->appContainer()->make(PermissionGate::class)->inspect(
            user: new PermissionGateUser,
            ability: 'update',
            target: new PermissionGateModelWithoutPolicy,
            action: PermissionAction::fromOwnerClass(
                ownerClass: PermissionGateResource::class,
                ownerType: PermissionEntityType::Resource,
                subject: 'GatePosts',
                permissionAction: 'update',
            ),
        );

        self::assertTrue($response->allowed());
    }

    public function test_it_passes_permission_action_as_an_extra_policy_argument(): void
    {
        Gate::policy(PermissionGateModelWithPolicy::class, PermissionGatePolicy::class);

        $response = $this->appContainer()->make(PermissionGate::class)->inspect(
            user: new PermissionGateUser,
            ability: 'update',
            target: new PermissionGateModelWithPolicy,
            action: PermissionAction::fromOwnerClass(
                ownerClass: PermissionGateResource::class,
                ownerType: PermissionEntityType::Resource,
                subject: 'PermissionGateSubject',
                permissionAction: 'update',
            ),
        );

        self::assertTrue($response->allowed());
    }
}

class PermissionGateUser implements AuthorizableContract
{
    use Authorizable;
}

class PermissionGateModelWithoutPolicy extends Model {}

class PermissionGateModelWithPartialPolicy extends Model {}

class PermissionGateModelWithPolicy extends Model {}

class PermissionGatePartialPolicy
{
    public function update(PermissionGateUser $user, PermissionGateModelWithPartialPolicy $record): Response
    {
        return Response::allow();
    }
}

class PermissionGatePolicy
{
    public function update(
        PermissionGateUser $user,
        PermissionGateModelWithPolicy $record,
        PermissionAction | string | null $permissionAction = null,
    ): Response {
        if (! $permissionAction instanceof PermissionAction) {
            return Response::deny();
        }

        return $permissionAction->subject === 'PermissionGateSubject'
            ? Response::allow()
            : Response::deny();
    }
}

class PermissionGateResource extends \Filament\Resources\Resource
{
    public static function getPermissionSubject(): ?string
    {
        return 'PermissionGateSubject';
    }
}
