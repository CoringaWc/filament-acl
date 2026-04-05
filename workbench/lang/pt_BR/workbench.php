<?php

declare(strict_types=1);

return [
    'resources' => [
        'categories' => [
            'model_label' => 'Categoria',
            'plural_model_label' => 'Categorias',
            'navigation_label' => 'Categorias',
            'navigation_group' => 'Blog',
            'fields' => [
                'name' => 'Nome',
                'description' => 'Descrição',
                'posts' => 'Posts',
            ],
            'columns' => [
                'name' => 'Nome',
                'description' => 'Descrição',
                'posts_count' => 'Qtd. Posts',
            ],
        ],
        'posts' => [
            'model_label' => 'Post',
            'plural_model_label' => 'Posts',
            'navigation_label' => 'Posts',
            'navigation_group' => 'Blog',
            'fields' => [
                'author' => 'Autor',
                'title' => 'Título',
                'status' => 'Status',
                'categories' => 'Categorias',
                'content' => 'Conteúdo',
            ],
            'columns' => [
                'title' => 'Título',
                'author' => 'Autor',
                'status' => 'Status',
                'categories' => 'Categorias',
            ],
        ],
        'users' => [
            'model_label' => 'Usuário',
            'plural_model_label' => 'Usuários',
            'navigation_label' => 'Usuários',
            'navigation_group' => 'Administração',
            'fields' => [
                'name' => 'Nome',
                'email' => 'E-mail',
                'roles' => 'Funções',
            ],
            'columns' => [
                'name' => 'Nome',
                'email' => 'E-mail',
                'roles' => 'Funções',
            ],
        ],
        'moderation_posts' => [
            'model_label' => 'Post (Moderação)',
            'plural_model_label' => 'Posts (Moderação)',
            'navigation_label' => 'Posts (Moderação)',
            'navigation_group' => 'Moderação',
            'fields' => [
                'title' => 'Título',
                'status' => 'Status',
            ],
            'columns' => [
                'title' => 'Título',
                'status' => 'Status',
            ],
        ],
    ],
    'relation_managers' => [
        'posts' => 'Posts',
        'categories' => 'Categorias',
    ],
];
