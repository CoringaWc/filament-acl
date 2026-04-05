<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Support;

use BackedEnum;
use CoringaWc\FilamentAcl\FilamentPermissionManager;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use LogicException;
use UnitEnum;

final class PermissionGate
{
    public function __construct(
        protected FilamentPermissionManager $manager,
        protected PermissionActionResolver $permissionActionResolver,
    ) {}

    /**
     * @param  array<int, mixed>  $arguments
     */
    public function inspect(
        mixed $user,
        string | UnitEnum $ability,
        string | Model $target,
        PermissionAction | string | null $action,
        array $arguments = [],
        bool $shouldCheckPolicyExistence = true,
    ): Response {
        $abilityValue = $this->normalizeAbility($ability);
        $gateArguments = $this->buildGateArguments($abilityValue, $target, $action, $arguments);

        if (! $shouldCheckPolicyExistence) {
            if (
                $this->manager->usesStrictMode()
                && (! Gate::forUser($user)->has($abilityValue))
                && (
                    blank($policyClass = Gate::getPolicyFor($target))
                    || (! method_exists($policyClass, $abilityValue))
                )
            ) {
                throw $this->strictModeException($abilityValue, $target, $policyClass);
            }

            return Gate::forUser($user)->inspect($ability, $gateArguments);
        }

        $policy = Gate::getPolicyFor($target);

        if (filled($policy) && method_exists($policy, $abilityValue)) {
            return Gate::forUser($user)->inspect($ability, $gateArguments);
        }

        if ($this->manager->usesStrictMode()) {
            $policyClass = match (true) {
                is_string($policy) => $policy,
                is_object($policy) => $policy::class,
                default => null,
            };

            throw $this->strictModeException($abilityValue, $target, $policyClass);
        }

        /** @var bool|Response|null $response */
        $response = invade(Gate::forUser($user))->callBeforeCallbacks( /** @phpstan-ignore-line */
            $user,
            $ability,
            $gateArguments,
        );

        if ($response === false) {
            return Response::deny();
        }

        if (! $response instanceof Response) {
            return Response::allow();
        }

        return $response;
    }

    /**
     * @param  array<int, mixed>  $arguments
     *
     * @throws AuthorizationException
     */
    public function authorize(
        mixed $user,
        string | UnitEnum $ability,
        string | Model $target,
        PermissionAction | string | null $action,
        array $arguments = [],
        bool $shouldCheckPolicyExistence = true,
    ): Response {
        return $this->inspect(
            user: $user,
            ability: $ability,
            target: $target,
            action: $action,
            arguments: $arguments,
            shouldCheckPolicyExistence: $shouldCheckPolicyExistence,
        )->authorize();
    }

    /**
     * @param  array<int, mixed>  $arguments
     * @return array<int, mixed>
     */
    protected function buildGateArguments(
        string $ability,
        string | Model $target,
        PermissionAction | string | null $action,
        array $arguments = [],
    ): array {
        $resolvedAction = $this->permissionActionResolver->resolve($ability, $action);

        return [
            $target,
            ...($resolvedAction === null ? [] : [$resolvedAction]),
            ...$arguments,
        ];
    }

    protected function normalizeAbility(string | UnitEnum $ability): string
    {
        return match (true) {
            $ability instanceof BackedEnum => $ability->value,
            $ability instanceof UnitEnum => $ability->name,
            default => $ability,
        };
    }

    protected function strictModeException(
        string $ability,
        string | Model $target,
        object | string | null $policyClass,
    ): LogicException {
        $policyClass = match (true) {
            is_string($policyClass) => $policyClass,
            is_object($policyClass) => $policyClass::class,
            default => null,
        };

        $modelName = is_string($target) ? $target : $target::class;

        if (blank($policyClass)) {
            return new LogicException("Strict authorization mode is enabled, but no policy was found for [{$modelName}].");
        }

        return new LogicException("Strict authorization mode is enabled, but no [{$ability}()] method was found on [{$policyClass}].");
    }
}
