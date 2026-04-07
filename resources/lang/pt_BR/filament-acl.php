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
                'select_all' => 'Marcar todos',
                'select_all_help' => 'Ative para selecionar todas as permissões desta função.',
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
                'select_all' => 'Marcar todos',
                'deselect_all' => 'Desmarcar todos',
            ],
            'section_description' => '1 permissão|:count permissões',
            'navigation_group' => 'Controle de Acesso',
        ],
    ],

    'permission_labels' => [
        'view_any' => 'Listar',
        'view' => 'Visualizar',
        'create' => 'Criar',
        'update' => 'Editar',
        'delete' => 'Excluir',
        'delete_any' => 'Excluir em lote',
        'force_delete' => 'Excluir definitivamente',
        'force_delete_any' => 'Excluir definitivamente em lote',
        'restore' => 'Restaurar',
        'restore_any' => 'Restaurar em lote',
        'replicate' => 'Replicar',
        'reorder' => 'Reordenar',
        'associate' => 'Associar',
        'attach' => 'Anexar',
        'detach' => 'Desanexar',
        'detach_any' => 'Desanexar em lote',
        'dissociate' => 'Desassociar',
        'dissociate_any' => 'Desassociar em lote',
    ],
];
