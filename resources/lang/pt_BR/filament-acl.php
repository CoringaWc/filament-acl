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
            'section_toggle' => [
                'select_all' => 'Selecionar Todos',
                'deselect_all' => 'Desmarcar Todos',
            ],
        ],
    ],

    'permission_labels' => [
        'view_any' => 'Ver todos',
        'view' => 'Visualizar',
        'create' => 'Criar',
        'update' => 'Atualizar',
        'delete' => 'Excluir',
        'delete_any' => 'Excluir em massa',
        'force_delete' => 'Excluir definitivamente',
        'force_delete_any' => 'Excluir definitivamente em massa',
        'restore' => 'Restaurar',
        'restore_any' => 'Restaurar em massa',
        'replicate' => 'Replicar',
        'reorder' => 'Reordenar',
        'associate' => 'Associar',
        'attach' => 'Anexar',
        'detach' => 'Desanexar',
        'detach_any' => 'Desanexar em massa',
        'dissociate' => 'Dissociar',
        'dissociate_any' => 'Dissociar em massa',
    ],
];
