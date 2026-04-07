<?php

declare(strict_types=1);

use CoringaWc\FilamentAcl\Policies\RolePolicy;
use CoringaWc\FilamentAcl\Support\ConfiguredPermissionSubjectResolver;
use CoringaWc\FilamentAcl\Support\DefaultPermissionKeyBuilder;
use CoringaWc\FilamentAcl\Support\SpatiePermissionStore;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return [
    /*
    |--------------------------------------------------------------------------
    | Permission Models
    |--------------------------------------------------------------------------
    |
    | These are the concrete Spatie-compatible models used by the package to
    | persist roles and permissions. Override them when your application uses
    | custom Role/Permission models.
    |
    */
    'models' => [
        'permission' => Permission::class,
        'role' => Role::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Core Runtime Services
    |--------------------------------------------------------------------------
    |
    | The package resolves permission subjects, builds permission keys, and
    | persists permissions through swappable services. Most projects can keep
    | the defaults. Override them only when you need custom subject naming,
    | a different permission-key format, or a custom storage backend.
    |
    */
    'subject_resolver' => ConfiguredPermissionSubjectResolver::class,
    'permission_key_builder' => DefaultPermissionKeyBuilder::class,
    'permission_store' => SpatiePermissionStore::class,

    /*
    |--------------------------------------------------------------------------
    | Permission Key Formatting
    |--------------------------------------------------------------------------
    |
    | Controls how a permission such as "viewAny" + "PostCategories" becomes a
    | persisted permission key like "ViewAny:PostCategories".
    |
    | - separator: string between ability and subject.
    | - ability_case: normalization applied to the ability.
    | - subject_case: normalization applied to the resolved subject.
    | - allow_fallback_subjects: reserved flag for future hard-fail behavior
    |   when a subject cannot be resolved explicitly.
    |
    */
    'permissions' => [
        'separator' => ':',
        'ability_case' => 'studly',
        'subject_case' => 'preserve',
        'allow_fallback_subjects' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Plugin Defaults
    |--------------------------------------------------------------------------
    |
    | Global defaults used when a panel registers FilamentAclPlugin without
    | overriding the corresponding fluent methods.
    |
    */
    'plugin' => [
        'strict_mode' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Built-in Permissions Resource
    |--------------------------------------------------------------------------
    |
    | The package ships with a reusable Role/Permissions resource. It is
    | disabled by default and can be enabled per panel via the plugin. These
    | values are only defaults; the panel can override them fluently.
    |
    | - enabled: whether the package should register the resource by default.
    | - slug: custom route slug for the resource.
    | - navigation_*: standard Filament navigation customizations.
    | - model_label / plural_model_label: labels shown in the UI.
    | - managed_panel: when the resource lives in one panel but edits the
    |   permissions of another panel, set the target panel ID here.
    | - cluster: optional Filament cluster that should contain the resource.
    | - tabs.*: fine-grained control over which permission groups appear in the
    |   form UI. Disabling a tab hides it; it does not remove permissions that
    |   already exist in storage.
    |
    */
    'resources' => [
        'permissions' => [
            'enabled' => false,
            'slug' => null,
            'navigation_label' => null,
            'navigation_icon' => 'heroicon-o-lock-closed',
            'navigation_group' => null,
            'navigation_sort' => null,
            'model_label' => null,
            'plural_model_label' => null,
            'managed_panel' => null,
            'cluster' => null,
            'tabs' => [
                'resources' => true,
                'pages' => true,
                'widgets' => true,
                'custom_permissions' => true,
            ],
            'actions' => [
                'viewAny',
                'create',
                'update',
                'delete',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Protected Role
    |--------------------------------------------------------------------------
    |
    | The protected role plays the same role as a super-admin role in many
    | applications:
    |
    | - name: the persisted role name.
    | - hidden: hides the role from package UI components such as selects and
    |   the built-in permissions resource.
    | - bypass_gate: when true, users assigned this role bypass package-level
    |   permission checks through Gate::before().
    |
    */
    'roles' => [
        'protected' => [
            'name' => 'super_admin',
            'hidden' => true,
            'bypass_gate' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Command Safety
    |--------------------------------------------------------------------------
    |
    | Prevents destructive or privileged package commands from running in
    | production unless the application explicitly turns this protection off.
    |
    */
    'commands' => [
        'prohibit_in_production' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Strategy
    |--------------------------------------------------------------------------
    |
    | Defines how the published permission tables should be generated.
    |
    | panel_scope:
    | - column: the roles/permissions column used to scope data by panel.
    | - on_roles: whether roles are isolated per panel.
    | - on_permissions: whether permissions are isolated per panel.
    | - type / length / nullable / default: migration defaults for that column.
    |
    | model_morph_key:
    | - type: force the morph key type used in pivot tables. Keep null to let
    |   the install command infer it from the application's authenticatable
    |   model (uuid, ulid, string, unsignedBigInteger).
    | - length: fallback string length when the inferred type is string-like.
    |
    */
    'database' => [
        'panel_scope' => [
            'column' => 'panel',
            'on_roles' => false,
            'on_permissions' => false,
            'type' => 'string',
            'length' => 50,
            'nullable' => false,
            'default' => 'global',
        ],
        'model_morph_key' => [
            'type' => null,
            'length' => 36,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Discovery Hints
    |--------------------------------------------------------------------------
    |
    | Reserved for future filesystem-based discovery strategies. The package is
    | currently trait-first and explicit by design, but these entries are kept
    | available for advanced projects that want to centralize discovery rules.
    |
    */
    'discovery' => [
        'panels' => [],
        'paths' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Policy Generation / Registration
    |--------------------------------------------------------------------------
    |
    | Controls the built-in policy tooling:
    |
    | - path: where generated policies should be written.
    | - generate: whether generator commands should emit policy files.
    | - merge: whether generated methods should be merged into existing files.
    | - register_role_policy: whether the package should auto-register the role
    |   policy when no policy is already registered for the role model.
    | - role_policy: default policy class for role protection rules.
    | - methods / single_parameter_methods: generation defaults for policy stubs.
    |
    */
    'policies' => [
        'path' => app_path('Policies'),
        'generate' => true,
        'merge' => true,
        'register_role_policy' => true,
        'role_policy' => RolePolicy::class,
        /*
        |----------------------------------------------------------------------
        | Default Resource Permission Actions
        |----------------------------------------------------------------------
        |
        | Actions listed here are generated for every resource that uses the
        | HasResourcePermissions trait (unless the resource overrides
        | getPermissionActions()). Trim this list to remove rarely-used
        | permissions like forceDelete, restore, replicate, or reorder.
        |
        | Resources can add extra actions via getPermissionCustomActions()
        | without needing to override the full list.
        |
        | Full list available:
        | viewAny, view, create, update, delete, deleteAny,
        | forceDelete, forceDeleteAny, restore, restoreAny,
        | replicate, reorder
        |
        */
        'methods' => [
            'viewAny',
            'view',
            'create',
            'update',
            'delete',
        ],
        'single_parameter_methods' => [
            'viewAny',
            'create',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Stubs
    |--------------------------------------------------------------------------
    |
    | Published stubs are resolved from this path. Applications can publish the
    | package stubs and then customize policy/resource generation without
    | forking the package.
    |
    */
    'stubs' => [
        'path' => base_path('stubs/filament-acl'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Subject Overrides
    |--------------------------------------------------------------------------
    |
    | Map owner classes to explicit subjects when the automatic resolver is not
    | enough. This is a hard override that wins after an owner-specific
    | getPermissionSubject() method but before the generic fallback subject.
    |
    */
    'subject_overrides' => [
        //
    ],

    /*
    |--------------------------------------------------------------------------
    | Runtime Callbacks
    |--------------------------------------------------------------------------
    |
    | Advanced extension points to override subject resolution or permission-key
    | building globally without replacing the underlying service classes.
    |
    */
    'callbacks' => [
        'resolve_permission_subject_using' => null,
        'build_permission_key_using' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Relation Managers
    |--------------------------------------------------------------------------
    |
    | When true, relation managers that define a related resource may delegate
    | authorization to that related resource by default. This is disabled by
    | default so each relation manager can decide explicitly whether it wants
    | its own permissions or shared permissions.
    |
    */
    'relation_managers' => [
        'delegate_to_related_resource_by_default' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Pages and Widgets
    |--------------------------------------------------------------------------
    |
    | These defaults are used when a page/widget opts into the package but does
    | not override its own action list.
    |
    | - actions: default abilities generated/synchronized for that owner type.
    | - exclude: classes that should be ignored by sync/UI discovery even if
    |   they use the package traits.
    |
    */
    'pages' => [
        'actions' => ['view'],
        'exclude' => [],
    ],

    'widgets' => [
        'actions' => ['view'],
        'exclude' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Permissions
    |--------------------------------------------------------------------------
    |
    | Register free-form permissions that are not owned by a Filament resource,
    | page, or widget.
    |
    | Supported formats:
    | - 'content.export' => 'Export content'
    | - ['name' => 'content.publish', 'label' => 'Publish content']
    | - ['name' => 'content.publish', 'label' => 'Publish content', 'panels' => ['admin']]
    | - 'content.archive' (label will be generated automatically)
    |
    */
    'custom_permissions' => [
        //
    ],

    /*
    |--------------------------------------------------------------------------
    | Integration Style
    |--------------------------------------------------------------------------
    |
    | When explicit opt-in is enabled, only classes that implement the package
    | contract through its traits/helpers participate in permission generation,
    | syncing, and UI discovery. This keeps the package generic and avoids
    | pulling unrelated Filament classes into the ACL graph automatically.
    |
    */
    'integration' => [
        'require_explicit_opt_in' => true,
    ],
];
