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
            'actions' => [
                'view_any' => 'View Any',
                'view' => 'View',
                'create' => 'Create',
                'update' => 'Update',
                'delete' => 'Delete',
                'force_delete' => 'Force Delete',
                'restore' => 'Restore',
                'replicate' => 'Replicate',
                'reorder' => 'Reorder',
            ],
        ],
    ],
];
