<?php

declare(strict_types=1);

use CoringaWc\FilamentAcl\Policies\RolePolicy;
use Spatie\Permission\Models\Permission;
use Workbench\App\Models\Role;

return [

    /*
    |--------------------------------------------------------------------------
    | Permission Models (workbench override)
    |--------------------------------------------------------------------------
    */
    'models' => [
        'permission' => Permission::class,
        'role' => Role::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Permission Key Formatting
    |--------------------------------------------------------------------------
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
    */
    'plugin' => [
        'strict_mode' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Built-in Permissions Resource
    |--------------------------------------------------------------------------
    */
    'resources' => [
        'permissions' => [
            'enabled' => true,
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
            'sections' => [
                'group_by_navigation_group' => false,
                'group_by_cluster' => false,
                'collapsed' => false,
                'persist_collapsed' => true,
            ],
            'inner_tabs' => [
                'vertical' => true,
                'contained' => true,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Protected Role
    |--------------------------------------------------------------------------
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
    */
    'commands' => [
        'prohibit_in_production' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Strategy
    |--------------------------------------------------------------------------
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
    */
    'discovery' => [
        'panels' => [],
        'paths' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Policy Generation / Registration
    |--------------------------------------------------------------------------
    */
    'policies' => [
        'path' => app_path('Policies'),
        'generate' => true,
        'merge' => true,
        'register_role_policy' => true,
        'role_policy' => RolePolicy::class,
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
    */
    'stubs' => [
        'path' => base_path('stubs/filament-acl'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Subject Overrides
    |--------------------------------------------------------------------------
    */
    'subject_overrides' => [
        //
    ],

    /*
    |--------------------------------------------------------------------------
    | Relation Managers
    |--------------------------------------------------------------------------
    */
    'relation_managers' => [
        'delegate_to_related_resource_by_default' => false,
        'actions' => [
            'viewAny',
            'view',
            'create',
            'update',
            'delete',
            // 'associate',
            // 'attach',
            // 'detach',
            // 'detachAny',
            // 'dissociate',
            // 'dissociateAny',
        ],
        'exclude' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Pages and Widgets
    |--------------------------------------------------------------------------
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
    | Custom Permissions (workbench override)
    |--------------------------------------------------------------------------
    */
    'custom_permissions' => [
        'content.export' => 'workbench::workbench.custom_permissions.export',
        [
            'name' => 'content.publish',
            'label' => 'workbench::workbench.custom_permissions.publish',
            'panels' => ['admin'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Integration Style
    |--------------------------------------------------------------------------
    */
    'integration' => [
        'require_explicit_opt_in' => true,
    ],
];
