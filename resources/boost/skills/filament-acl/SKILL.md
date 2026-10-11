---
name: filament-acl
description: Configure, extend, test, or troubleshoot coringawc/filament-acl contextual permissions for Filament resources, relation managers, pages, widgets, policies, roles, and custom permissions. Use when a Laravel application configures or troubleshoots this package. For a major version migration, use filament-acl-upgrade.
---

# Filament ACL

## Supported dependencies

For Filament ACL 2.x, inspect the installed Composer versions: PHP ^8.3, Filament ^5.0, Spatie Laravel Permission ^8.2. The package matrix targets Laravel 12; do not infer Laravel 13 verification from upstream support. Package Role and Permission extend Spatie and inherit `findByName(BackedEnum|string $name, ?string $guardName = null)` and `findOrCreate(...)`. Keep custom overrides compatible with their upstream return contracts. Prefer string-backed enums for permission and role names.

## Use the package contract

- Register `FilamentAclPlugin` only on panels that should expose the permissions UI.
- Opt owners in with the matching trait: `HasResourcePermissions`, `HasRelationManagerPermissions`, `HasPagePermissions`, or `HasWidgetPermissions`.
- Keep policy methods native to Laravel. Add `ChecksPermission` and pass the optional permission action last.
- Prefer method or attribute overrides over required base classes or static configuration properties.
- Never introduce a runtime dependency on Filament Shield.

## Configure owners deliberately

- Use `getPermissionSubject()` or `#[PermissionSubject]` for a custom subject.
- Use `getPermissionActions()` or `#[PermissionActions]` to replace an owner's final action list.
- Use `getPermissionCustomActions()` or `#[CustomPermissionActions]` for additional actions.
- Use `getSharedPermissionOwner()` or `#[SharedPermissionOwner]` when multiple Filament owners must share one canonical permission set.
- Use `shouldRegisterPermissions()` or `#[RegisterPermissions(false)]` to exclude an owner from sync and the permissions UI.
- Attributes are read from the concrete class only. Redeclare metadata on subclasses.

## Preserve authorization boundaries

Use `ChecksPermission` inside policies, then evaluate application-domain rules after the package permission succeeds.

```php
public function update(
    mixed $user,
    Post $post,
    PermissionAction | string | null $permissionAction = null,
): Response {
    if ($response = $this->denyUnlessPermitted($user, 'update', $permissionAction)) {
        return $response;
    }

    return $user->canManagePost($post)
        ? Response::allow()
        : Response::deny();
}
```

For Filament custom actions, prefer the native authorization surface:

```php
Action::make('archive')
    ->authorize('archive', PostResource::class);
```

## Synchronize safely

- Review `config/filament-acl.php` before synchronizing, especially explicit opt-in and role/permission panel scope.
- Keep shared owners out of duplicate UI and sync rows.
- Keep the protected role hidden and protected according to configuration.
- Use `filament-acl:sync` for permission synchronization and `filament-acl:admin-user` for protected administrator assignment.
- Production commands are disabled by default; do not bypass that protection casually.

## Extend through public services

Override package behavior through container bindings for `ResolvesPermissionSubject`, `BuildsPermissionKey`, or `StoresPermissions`. Use `FilamentPermissionManager` and `Support\\Utils` only through their public contracts.

## Verify

- Test owner discovery, generated permission keys, policy decisions, panel scope, shared owners, and protected roles affected by the change.
- Run the consuming application's focused tests after synchronization-related changes.
- Confirm translated labels use the package translation keys and that custom permission labels are translation keys rather than hard-coded UI text.

## Cache and events

Preserve `syncPermissions()` and `syncRoles()` so Spatie retains guard validation, fluent return values, cache invalidation and configured events. In Spatie v8.2+, syncing permissions emits detach events for removed assignments when `permission.events_enabled` is true. Review listeners for idempotency and side effects. Use `permission:cache-reset` after a deployment; reload affected user relations and restart long-running workers through the application's deployment process. Do not replace Spatie operations with raw pivot writes.
