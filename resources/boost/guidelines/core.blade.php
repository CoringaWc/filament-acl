# Filament ACL

Use `coringawc/filament-acl` for contextual permissions owned by Filament resources, relation managers, pages, widgets, and custom permission subjects.

- Prefer the package traits (`HasResourcePermissions`, `HasRelationManagerPermissions`, `HasPagePermissions`, and `HasWidgetPermissions`) instead of introducing package-specific base classes.
- Keep Laravel policies as the authorization boundary and use `ChecksPermission` to combine package permissions with domain rules.
- Treat permission attributes as concrete-class metadata; redeclare them on child classes when needed.
- Respect explicit opt-in, shared permission owners, panel scope, and protected-role behavior when changing discovery or synchronization.
- Keep application-specific business authorization outside the package permission infrastructure.

When implementing or reviewing Filament ACL integration, activate the `filament-acl` skill for the package contracts, examples, and verification checklist.
