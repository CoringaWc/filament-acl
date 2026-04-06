# AGENTS.md

## Purpose

This repository contains the `coringawc/filament-acl` package.

The package solves contextual authorization for Filament v5 by treating the Filament owner as the permission subject:

- `Resource`
- `RelationManager`
- `Page`
- `Widget`
- free-form custom permissions

This package is intentionally generic. It may be inspired by ideas from `filament-shield`, but it must not depend on Shield at runtime.

## Architectural Rules

### Trait-First

Do not introduce required `BaseResource`, `BaseRelationManager`, `BasePage`, or `BaseWidget` classes unless absolutely necessary.

The preferred integration surface is:

- `HasResourcePermissions`
- `HasRelationManagerPermissions`
- `HasPagePermissions`
- `HasWidgetPermissions`

If a new feature can be implemented through traits, helper services, discovery, or configuration, prefer that.

### Automatic First, Override By Method

Defaults should work without extra boilerplate.

The following methods are optional overrides and should stay optional:

- `getPermissionSubject(): ?string`
- `shouldRegisterPermissions(): bool`
- `getSharedPermissionOwner(): ?string`
- `getPermissionCustomActions(): array`
- `getPermissionPanel(): ?string`

Do not add required static properties when a method override is enough.

### Keep Permission Naming Consistent

Use `Permission` and `Action` terminology in public APIs.

Avoid reintroducing public `Acl*` API names except where the package/plugin identity itself already uses `FilamentAcl`.

### No Shield Coupling

The package may copy or reinterpret good DX ideas from Shield, such as:

- install command ergonomics
- stub publishing
- resource-based role management UI

But it must remain independent:

- no `filament-shield` Composer dependency
- no runtime calls into Shield classes
- no config contract that assumes Shield is present

### Generic Package, App-Specific Decisions Stay Outside

Keep the package focused on contextual permission infrastructure.

Good package responsibilities:

- discovering opted-in Filament owners
- building subjects
- building permission keys
- syncing permissions
- panel scoping
- protected-role handling
- built-in role/permissions resource

Responsibilities that usually belong to the consuming app:

- domain-specific policy logic
- custom action business rules
- app-specific naming conventions when defaults are not enough
- extra role metadata

## Policy Contract

Policies remain native Laravel policies.

Package checks are opt-in through `ChecksPermission`.

Typical pattern:

```php
if ($response = $this->denyUnlessPermitted($user, 'update', $permissionAction)) {
    return $response;
}

// domain rules after permission passes
return Response::allow();
```

Keep the extra permission argument last in the signature:

- `viewAny(mixed $user, PermissionAction|string|null $permissionAction = null)`
- `update(mixed $user, Model $record, PermissionAction|string|null $permissionAction = null)`

Never make policies infer the owner from the request or route when the owner can be passed explicitly.

## Custom Action Contract

Do not create a custom Filament action class for the package unless there is no other safe path.

The intended usage is:

```php
auth()->user()?->can('archive', [$record, PostResource::class]);
```

and:

```php
->authorize('archive', PostResource::class)
```

Preserve this native Laravel and Filament style.

## Shared Owners

Shared permissions are a first-class feature.

When an owner returns another owner class from `getSharedPermissionOwner()`:

- it should inherit that owner's permissions
- it should usually disappear from package discovery and permission UI
- the shared owner becomes the canonical visible entry

Do not break this behavior by reintroducing duplicate visible tabs or duplicate sync rows unless the feature is explicitly about surfacing shared ownership better in the UI.

## Opt-In And Opt-Out

The package defaults to explicit opt-in through config:

- `filament-acl.integration.require_explicit_opt_in = true`

When an owner returns `shouldRegisterPermissions(): false`:

- it must not be synced
- it must not be displayed in the permission UI
- package-level permission checks should be skipped for that owner

This is essential. Do not regress it.

## Panel Scope

Panel scope is configurable separately for:

- roles
- permissions

Changes to panel-scope behavior must always be reflected consistently across:

- sync commands
- built-in resource queries
- hidden-role helpers
- permission lookup helpers
- protected-role assignment

Do not change one layer without checking the others.

## Protected Role

The protected role is intentionally special.

Expected behavior:

- hidden from package UI when configured
- optionally bypasses package-level checks via `Gate::before()`
- protected from edit/delete by `RolePolicy`
- assignable through the admin-user command

Do not expose the protected role in normal selects or package tables unless the feature explicitly requires it and is configurable.

## Built-In Permissions Resource

The built-in resource manages roles and their assigned permissions.

Important expectations:

- opt-in owners only
- respects shared owners
- respects hidden protected role
- supports resource, relation manager, page, widget, and custom-permission tabs
- supports managing another panel through `getManagedPermissionPanel()` and plugin configuration

When improving the UI, prefer staying close to the ergonomics used in the `siasgfacil-filament` project:

- grouped, navigable permission sections
- nested-resource awareness
- page/widget/custom-permission visibility
- no duplicated owners when permissions are shared

## Commands

Current commands:

- `filament-acl:install`
- `filament-acl:sync`
- `filament-acl:admin-user`

Production safety matters. Commands are prohibited in production by default.

If you change command behavior, keep these goals:

- no destructive silent overwrites
- detect existing config and migration files
- ask before replacing when interactive
- work with UUID, ULID, string, and integer user keys

## Utilities

`Support\\Utils` is intentionally public-facing.

Before adding a new helper, ask:

- is this reused by multiple package subsystems?
- is this likely useful to consuming applications?

If yes, `Utils` is a good home.

If a helper is purely local to one class, keep it local.

## Translations

The package uses `filament-acl::filament-acl.*` keys for all user-facing labels.

Ability label resolution follows this chain:

1. `permission_labels.{camelCase}` (e.g., `permission_labels.viewAny`)
2. `permission_labels.{snake_case}` (e.g., `permission_labels.view_any`)
3. `Str::headline($ability)` fallback

When adding new abilities, add both camelCase and snake_case keys to `resources/lang/{locale}/filament-acl.php` under `permission_labels`.

Section toggle labels live under `resources.permissions.edit.tabs.section_toggle`.

## Subject Resolution Strategy

`SubjectResolutionStrategy` is a backed enum at `CoringaWc\FilamentAcl\Enums\SubjectResolutionStrategy`.

Values:

- `Basename` — derive subject from class basename minus suffix
- `Fqcn` — use the fully qualified class name
- `Custom` — defer to a registered callback

This enum is not yet integrated into the config. When implementing the integration, update `FluentSubjectResolver` to read the strategy from config and switch behavior accordingly.

## Workbench

The workbench is not throwaway.

It is a real package test harness and should stay healthy.

Current workbench goals:

- locale `pt_BR`
- faker locale `pt_BR`
- real Filament panel
- nested resources
- relation managers
- page example
- widget example (non-lazy for HTTP test compatibility)
- built-in permissions resource enabled
- seeded roles, users, and permissions

Infrastructure notes:

- `testbench.yaml` uses `:memory:` SQLite for tests; `workbench/.env` overrides to file-based SQLite and file session for `serve`
- `docker-compose.yml` sets `PHP_CLI_SERVER_WORKERS=4` to handle concurrent Livewire requests
- `testbench.yaml` provider names must use single quotes with single backslashes (YAML single-quote strings are literal)
- `TestCase::setUp()` registers `DataStore` as singleton to work around Filament's `bind()` registration

If you change runtime behavior, add or update workbench coverage rather than relying only on isolated unit tests.

## Testing Standards

Before finalizing changes:

1. Run focused PHPUnit coverage for the affected area.
2. Run the full PHPUnit suite.
3. Run PHPStan.
4. Run Pint.

Preferred commands in this repository:

```bash
docker compose exec php vendor/bin/phpunit --testdox
docker compose exec php vendor/bin/phpstan analyse --memory-limit=1G
docker compose exec php vendor/bin/pint --dirty
```

When touching workbench behavior, make sure smoke tests continue to pass.

## Documentation Expectations

Keep the README accurate.

Whenever you change public API, review these README sections:

- installation
- plugin registration
- trait usage
- policy usage
- commands
- permission resource
- panel scoping
- workbench usage

If you add new behavior that future agents need in order to work safely, update this file too.
