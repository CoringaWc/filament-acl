<?php

declare(strict_types=1);

use CoringaWc\FilamentAcl\Enums\PermissionEntityType;
use CoringaWc\FilamentAcl\Support\PermissionAction;
use CoringaWc\FilamentAcl\Support\PermissionGate;
use CoringaWc\FilamentAcl\Tests\TestCase;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Support\Facades\Gate;

test('it allows when policy is missing and strict mode is disabled', function (): void {
    /** @var TestCase $this */
    config()->set('filament-acl.plugin.strict_mode', false);

    $response = $this->appContainer()->make(PermissionGate::class)->inspect(
        user: new PermissionGateUser,
        ability: 'update',
        target: new PermissionGateModelWithoutPolicy,
        action: null,
    );

    expect($response->allowed())->toBeTrue();
});

test('it throws when policy is missing and strict mode is enabled', function (): void {
    /** @var TestCase $this */
    config()->set('filament-acl.plugin.strict_mode', true);

    expect(fn () => $this->appContainer()->make(PermissionGate::class)->inspect(
        user: new PermissionGateUser,
        ability: 'update',
        target: new PermissionGateModelWithoutPolicy,
        action: null,
    ))->toThrow(LogicException::class, 'Strict authorization mode is enabled, but no policy was found');
});

test('it allows when policy method is missing and strict mode is disabled', function (): void {
    /** @var TestCase $this */
    config()->set('filament-acl.plugin.strict_mode', false);
    Gate::policy(PermissionGateModelWithPartialPolicy::class, PermissionGatePartialPolicy::class);

    $response = $this->appContainer()->make(PermissionGate::class)->inspect(
        user: new PermissionGateUser,
        ability: 'delete',
        target: new PermissionGateModelWithPartialPolicy,
        action: null,
    );

    expect($response->allowed())->toBeTrue();
});

test('it throws when policy method is missing and strict mode is enabled', function (): void {
    /** @var TestCase $this */
    config()->set('filament-acl.plugin.strict_mode', true);
    Gate::policy(PermissionGateModelWithPartialPolicy::class, PermissionGatePartialPolicy::class);

    expect(fn () => $this->appContainer()->make(PermissionGate::class)->inspect(
        user: new PermissionGateUser,
        ability: 'delete',
        target: new PermissionGateModelWithPartialPolicy,
        action: null,
    ))->toThrow(LogicException::class, 'Strict authorization mode is enabled, but no [delete()] method was found');
});

test('it respects gate before callbacks in contextual flow', function (): void {
    /** @var TestCase $this */
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

    expect($response->allowed())->toBeTrue();
});

test('it passes permission action as an extra policy argument', function (): void {
    /** @var TestCase $this */
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

    expect($response->allowed())->toBeTrue();
});

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

class PermissionGateResource extends Filament\Resources\Resource
{
    public static function getPermissionSubject(): ?string
    {
        return 'PermissionGateSubject';

    }
}
