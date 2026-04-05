<?php

declare(strict_types=1);

return [
    'resources' => [
        'posts' => [
            'fields' => [
                'author' => 'Author',
                'title' => 'Title',
                'status' => 'Status',
                'categories' => 'Categories',
                'content' => 'Content',
            ],
            'columns' => [
                'title' => 'Title',
                'author' => 'Author',
                'status' => 'Status',
                'categories' => 'Categories',
            ],
        ],
        'moderation_posts' => [
            'fields' => ['title' => 'Title', 'status' => 'Status'],
            'columns' => ['title' => 'Title', 'status' => 'Status'],
        ],
        'categories' => [
            'fields' => [
                'name' => 'Name',
                'description' => 'Description',
                'posts' => 'Posts',
            ],
            'columns' => [
                'name' => 'Name',
                'description' => 'Description',
                'posts_count' => 'Posts Count',
            ],
        ],
        'users' => [
            'fields' => [
                'name' => 'Name',
                'email' => 'Email',
                'roles' => 'Roles',
            ],
            'columns' => [
                'name' => 'Name',
                'email' => 'Email',
                'roles' => 'Roles',
            ],
        ],
    ],
];
