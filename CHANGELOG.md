# Changelog

All notable changes to `filament-acl` will be documented in this file.

## v1.1.7 - 2026-06-18

### Fixed

- memoized permission option loading per owner within the request lifecycle, removing repeated `permissions` lookups when rendering the permission create/edit forms

**Full Changelog**: https://github.com/CoringaWc/filament-acl/compare/v1.1.6...v1.1.7

## v1.1.6 - 2026-06-06

### Fixed

- regenerated the PHPStan baseline and aligned a badge assertion to keep CI green under dependency drift (fresh installs without a committed `composer.lock`); republishes the v1.1.5 memoization fix under a clean, immutable tag

**Full Changelog**: https://github.com/CoringaWc/filament-acl/compare/v1.1.5...v1.1.6

## v1.1.5 - 2026-06-06

### Fixed

- memoized `Utils::userHasProtectedRoleForPanel()` per request with a `WeakMap` keyed by the authenticated user, eliminating an N+1 of identical role-existence and schema-introspection queries triggered by the `Gate::before()` hook on every authorization call (e.g. once per table row/action)

**Full Changelog**: https://github.com/CoringaWc/filament-acl/compare/v1.1.4...v1.1.5

## v1.1.4 - 2026-04-19

**Full Changelog**: https://github.com/CoringaWc/filament-acl/compare/v1.1.3...v1.1.4

## v1.1.3 - 2026-04-17

### Fixed

- set the workbench container `DB_DATABASE` to the persistent Testbench sqlite path so HTTP requests no longer fall back to the in-memory `testing` connection

**Full Changelog**: https://github.com/CoringaWc/filament-acl/compare/v1.1.2...v1.1.3

## v1.1.2 - 2026-04-15

### Changed

- refactored `PermissionResource` form and table builders into smaller protected methods for safer reuse and extension

### Fixed

- moved workbench npm, composer, and playwright caches into workspace-local `.docker/` directories so the php container no longer crashes on root-owned `/tmp/.npm` cache files

**Full Changelog**: https://github.com/CoringaWc/filament-acl/compare/v1.1.1...v1.1.2

## v1.1.1 - 2026-04-14

### Fixed

- Resolves widget permission labels from instantiated widget headings, including Htmlable headings, instead of falling back to class names in the permissions UI

**Full Changelog**: https://github.com/CoringaWc/filament-acl/compare/v1.1.0...v1.1.1

## v1.1.0 - 2026-04-14

### Highlights

- add #[PermissionActions([...])] as a declarative equivalent to getPermissionActions()
- support the new attribute across resources, relation managers, pages, and widgets
- add unit coverage and documentation for the new attribute

## v1.0.4 - 2026-04-14

**Full Changelog**: https://github.com/CoringaWc/filament-acl/compare/v1.0.3...v1.0.4

## v1.0.3 - 2026-04-14

**Full Changelog**: https://github.com/CoringaWc/filament-acl/compare/v1.0.2...v1.0.3

## v1.0.2 - 2026-04-14

**Full Changelog**: https://github.com/CoringaWc/filament-acl/compare/v1.0.1...v1.0.2

## v1.0.1 - 2026-04-10

**Full Changelog**: https://github.com/CoringaWc/filament-acl/compare/v1.0.0...v1.0.1

## v1.0.0 - 2026-04-08

**Full Changelog**: https://github.com/CoringaWc/filament-acl/commits/v1.0.0

## Unreleased

### Breaking Changes

- Removed `callbacks` config section (`resolve_permission_subject_using`, `build_permission_key_using`); use `FilamentPermission` facade or `FilamentPermissionManager` directly
- Removed `subject_resolver`, `permission_key_builder`, `permission_store` config keys; override via container binding in your service provider

### Added

- `relation_managers.actions` config key for customizing default RM permission actions
- `relation_managers.exclude` config key for excluding RM classes from sync/UI discovery
- `inner_tabs.contained` config key and `innerTabsContained()` plugin method

### Fixed

- RM-specific actions (associate, attach, detach, detachAny, dissociate, dissociateAny) now included in default config

## 1.0.0 - 202X-XX-XX

- initial release
