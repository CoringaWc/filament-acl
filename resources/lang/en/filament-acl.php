<?php

declare(strict_types=1);

return [
    'resources' => [
        'permissions' => [
            'navigation_label' => 'Permissions',
            'model_label' => 'Permission',
            'plural_model_label' => 'Permissions',
            'fields' => [
                'name' => 'Name',
                'guard_name' => 'Guard',
                'panel' => 'Panel',
                'permissions' => 'Permissions',
                'select_all' => 'Select all',
                'select_all_help' => 'Enable to select all permissions for this role.',
            ],
            'tabs' => [
                'resources' => 'Resources',
                'pages' => 'Pages',
                'widgets' => 'Widgets',
                'custom_permissions' => 'Custom Permissions',
            ],
            'columns' => [
                'name' => 'Name',
                'guard_name' => 'Guard',
                'panel' => 'Panel',
                'permissions_count' => 'Permissions',
                'updated_at' => 'Updated',
            ],
            'groups' => [
                'resources' => 'Resources',
                'ungrouped' => 'Other',
            ],
            'section_toggle' => [
                'select_all' => 'Select all',
                'deselect_all' => 'Deselect all',
            ],
            'section_description' => '1 permission|:count permissions',
            'navigation_group' => 'Access Control',
        ],
    ],

    'permission_labels' => [
        'view_any' => 'View Any',
        'view' => 'View',
        'create' => 'Create',
        'update' => 'Update',
        'delete' => 'Delete',
        'delete_any' => 'Delete Any',
        'force_delete' => 'Force Delete',
        'force_delete_any' => 'Force Delete Any',
        'restore' => 'Restore',
        'restore_any' => 'Restore Any',
        'replicate' => 'Replicate',
        'reorder' => 'Reorder',
        'associate' => 'Associate',
        'attach' => 'Attach',
        'detach' => 'Detach',
        'detach_any' => 'Detach Any',
        'dissociate' => 'Dissociate',
        'dissociate_any' => 'Dissociate Any',
    ],
];
