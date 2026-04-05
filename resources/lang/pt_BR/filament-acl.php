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
            'actions' => [
                'view_any'     => 'Ver todos',
                'view'         => 'Visualizar',
                'create'       => 'Criar',
                'update'       => 'Atualizar',
                'delete'       => 'Excluir',
                'force_delete' => 'Excluir definitivamente',
                'restore'      => 'Restaurar',
                'replicate'    => 'Replicar',
                'reorder'      => 'Reordenar',
            ],
        ],
    ],
];
