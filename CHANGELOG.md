# Changelog

All notable changes to `filament-acl` will be documented in this file.

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
