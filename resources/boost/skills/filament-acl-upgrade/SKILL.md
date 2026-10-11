---
name: filament-acl-upgrade
description: Upgrade coringawc/filament-acl from 1.x with Spatie Laravel Permission v7 to 2.x with Spatie v8. Use for dependency upgrades, custom Role or Permission contract mismatches, sync event changes, migration planning, cache checks, and rollback preparation.
---

# Filament ACL upgrade

Read [the migration guide](references/migration.md) before editing. It is bundled with this skill, including in Composer dist archives. Inspect the installed versions and application-owned custom models, contracts, trait overrides, listeners, guards, teams and panel configuration.

1. Establish baseline tests and backups of composer.json, composer.lock, configuration and the affected database. Use an isolated testing database.
2. Change only the required Composer constraints and incompatible custom signatures. Keep classes, aliases, public method names, policy arguments and existing panel/role behavior. Do not invent mandatory model overrides: the package's empty subclasses inherit the upstream signatures.
3. Compare the exact old/new Spatie source, config and migration stubs. Incorporate only required changes through additive migrations and configuration edits. Avoid reinstalling the package over an existing integration.
4. Retain syncPermissions/syncRoles, guard checks, cache handling and events. Audit detach-event listeners now invoked by syncPermissions in v8, and test teams/custom pivots if the application uses them.
5. Run focused and complete application tests for strings/enums, guards, synchronization, protected roles, panel scoping, shared owners and workbench/UI behavior. Record exact tested versions; do not claim Laravel 13 verification based on upstream support.
6. Refresh Boost with boost:install or boost:update after checking the installed command help. Do not assume --discover exists or has identical defaults in every version. Verify both package skills and this reference are installed.
7. Report changes, checks, limitations, cache/deployment steps and the rollback plan. Do not publish releases, push or alter other applications without user authorization.
