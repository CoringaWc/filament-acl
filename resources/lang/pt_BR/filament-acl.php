<?php

declare(strict_types=1);

return [
    'resources' => [
        'permissions' => [
            'navigation_label' => 'Permissões',
            'model_label' => 'Permissão',
            'plural_model_label' => 'Permissões',
            'fields' => [
                'name' => 'Nome',
                'guard_name' => 'Guard',
                'panel' => 'Painel',
                'permissions' => 'Permissões',
            ],
            'tabs' => [
                'resources' => 'Recursos',
                'pages' => 'Páginas',
                'widgets' => 'Widgets',
                'custom_permissions' => 'Permissões customizadas',
            ],
            'columns' => [
                'name' => 'Nome',
                'guard_name' => 'Guard',
                'panel' => 'Painel',
                'permissions_count' => 'Permissões',
                'updated_at' => 'Atualizado',
            ],
            'groups' => [
                'resources' => 'Recursos',
                'ungrouped' => 'Outros',
            ],
        ],
    ],
];
