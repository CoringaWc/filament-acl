# Upgrading Filament ACL 1.x to 2.x

This major changes the required Spatie Laravel Permission version from `^7.0` to `^8.2`. It retains PHP `^8.3` and Filament `^5.0`. Laravel 12 is the package CI target. Do not treat Spatie's Laravel 13 support as proof that this package or your application was tested on Laravel 13. Use a tagged 2.x release for production upgrades.

The minimum is **8.2**, rather than 8.0: regression tests reproduced cross-team deletion with custom pivots in Spatie 8.0 for removeRole, syncRoles, revokePermissionTo and syncPermissions. The 8.2 upstream fixes preserve the other team's assignments. This is a data-isolation requirement, not a renamed API or new package feature.

## Before updating

Record installed versions with `composer show coringawc/filament-acl`, `composer show spatie/laravel-permission` and `composer show laravel/boost`. Save your Composer manifest/lock, permission configuration and an appropriate database backup. Run the existing application tests against an isolated testing database. Inventory custom Role/Permission models, implementations of Spatie contracts, trait overrides, listeners, guards, teams/custom pivots, and protected-role/panel configuration.

## Required dependency change

Set `coringawc/filament-acl` to `^2.0`. If your application directly requires Spatie, change that constraint to `^8.2` too. Then run:

```bash
composer update coringawc/filament-acl spatie/laravel-permission --with-all-dependencies
```

Review the lockfile before deployment. For unreleased development branches use an explicitly configured local/path repository; a production `^2.0` constraint resolves published releases.

## Custom model and contract signatures

The package models `CoringaWc\FilamentAcl\Models\Role` and `Permission` remain empty subclasses. Their class names, methods and aliases remain unchanged; no overrides are required when using these models directly. Spatie v8 widens `findByName()` and `findOrCreate()` from `string` to `BackedEnum|string` in BOTH contracts/models. Custom implementations or overrides that narrow the argument to string will fail PHP's compatibility checks. Update only such incompatible signatures, including any intermediate base class:

```php
use BackedEnum;
use Spatie\Permission\Contracts\Role as RoleContract;

public static function findByName(BackedEnum|string $name, ?string $guardName = null): RoleContract
{
    return parent::findByName($name, $guardName);
}

public static function findOrCreate(BackedEnum|string $name, ?string $guardName = null): RoleContract
{
    return parent::findOrCreate($name, $guardName);
}
```

For Permission overrides use `Spatie\Permission\Contracts\Permission` as the return contract. Preserve compatible existing return types and guard defaults. Independent contract implementations must normalize enum values before lookup/persistence. String names still work; string-backed enums are appropriate for named permissions/roles. Spatie v8 also normalizes enums passed to the model's `create(['name' => ...])`. This does not mean every Filament ACL owner/action/configuration API now accepts enums: its existing string contracts are unchanged.

## Gate callback compatibility

The missing-policy, non-strict fallback now passes the already normalized string ability to `Gate::before` callbacks, matching native Laravel Gate inspection. Public `string|UnitEnum` inputs still work, including backed and unbacked enums; no method signature or alias was renamed. If a custom callback assumed it would receive an enum only in this fallback, change it to accept the normalized string, as callbacks already do in the normal policy path. This also resolves a genuine pre-existing compatibility error exposed by the Laravel minimum matrix.

## Behavioral audit

Against Spatie 7.4.2 → 8.3.0:

- Keep `syncPermissions()` and `syncRoles()`; do not rewrite them as direct pivot operations. Their public names and fluent returns remain. With `permission.events_enabled=true`, `syncPermissions()` in Spatie 8.2+ now invokes revocation for old assignments and emits `PermissionDetachedEvent`, then grants the desired permissions. Review listeners for duplicate side effects, including syncing an unchanged set. `syncRoles()` already used the corresponding detach/attach path in v7. Event class names have not changed from v7.
- `PermissionRegistrar::initializeCache()` in Spatie 8.3+ now drops loaded permission/role collections when cache configuration changes. Applications that switch cache/tenant context should test this path rather than relying on stale in-memory collections. On 8.0/8.1/8.2 explicitly clear the registrar cache/collections when changing context.
- Removing/syncing roles or direct permissions with teams and custom pivot classes now uses a team-scoped pivot query. Check custom pivot delete observers/touch behavior and cross-team isolation if your application uses this feature.
- Octane's operation-terminated handling uses the event's `sandbox()` accessor. Review custom Octane lifecycle hooks when applicable.
- Spatie fixes quoted pipe-separated role parsing. Check unusual role names if you use this format.

The upstream config and migration stubs do not change in that comparison. Existing installations do not need a schema rebuild merely for v7 → v8. Compare stubs for the precise versions you install; introduce an additive migration only if your custom schema needs one. Merge required config changes manually. Do not run `migrate:fresh`, force-republish config/migrations, or rerun installation with force as an upgrade procedure.

## Package maintainer tooling

The package development requirements now declare Larastan ^3.13, PHPStan ^2.3 and Pint ^1.32. These are development-only tools, not dependencies installed for consuming applications. The earlier broad constraints allowed Larastan 3.1/PHPStan 2.1 to report eight diagnostics in unchanged runtime/baseline code and six generic macro-return diagnostics in tests. They also allowed Pint 1.0, whose bundled fixer predates PHP 8.3 and the repository's formatting conventions; it reported existing style differences and exceeded the bounded final run. The tested floors align the tooling with the existing code, without relaxing rules, ignoring additional diagnostics or changing runtime API.

## Verification and deployment

Run focused and full application tests: inherited string/enum lookup, guards, role/direct permission sync, cache refresh, listeners, protected-role protection/bypass, scoped panels, shared owners and opted-out owners. Test teams/custom pivots and Octane separately if used. Preserve package command production protections. `filament-acl:sync` changes assignments when used with `--with-protected-role`; review expected changes and the configured panels before an authorized sync.

```bash
php artisan permission:cache-reset
```

Run this in the application's configured cache context after deployment. Ensure the cache store is available (and database cache tables exist if that store is used). Reload cached user `roles`/`permissions` relations and restart long-running workers/Octane through your normal deployment procedure. Verify permitted and denied users on each relevant panel.

## Laravel Boost

Both `filament-acl` and `filament-acl-upgrade` live under `resources/boost/skills/{name}/SKILL.md`. The latter includes this guide in `references/migration.md`; repository-only `/docs` and `/UPGRADING.md` are excluded from dist archives.

Run `php artisan boost:install`, select this direct dependency and the skills appropriate to your agent. Existing users may refresh with `php artisan boost:update`; inspect `php artisan help boost:update` and `php artisan help boost:install` first. Boost 2.4.0 offers `boost:update --discover` as an opt-in; Boost 2.10.3 discovers by default and also offers `--no-discover`. Other versions must be checked using their own help output. If the upgrade skill is missing, rerun `boost:install`, review package/skill selection and check the generated skill folder includes the reference. Do not blindly copy the current documentation's flags onto an older installation.

## Safe rollback

Retain the previous deployable application artifact and Composer lock. If verification fails, stop the rollout and restore the prior code/config/lock together, run `composer install` for that lock, reset permission cache and restart workers. Keep compatible widened custom signatures only if validated on the restored dependency. A dependency rollback does not undo permission assignments or listener side effects: restore/reconcile those from the reviewed backup/change log. Reverse only an explicitly reviewed additive migration when its down path is safe; never rebuild the database. Recheck allowed and denied users before returning traffic.

## Sources

- [Official Spatie v8 upgrade guide](https://github.com/spatie/laravel-permission/blob/8.3.0/docs/upgrading.md)
- [Spatie 7.4.2 → 8.3.0 source comparison](https://github.com/spatie/laravel-permission/compare/7.4.2...8.3.0)
- [Laravel Boost third-party package skills](https://laravel.com/framework/docs/boost#third-party-package-skills)
