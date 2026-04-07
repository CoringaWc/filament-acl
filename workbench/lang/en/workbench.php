<?php

declare(strict_types=1);

return [
    'resources' => [
        'categories' => [
            'model_label' => 'Category',
            'plural_model_label' => 'Categories',
            'navigation_label' => 'Categories',
            'navigation_group' => 'Blog',
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
        'posts' => [
            'model_label' => 'Post',
            'plural_model_label' => 'Posts',
            'navigation_label' => 'Posts',
            'navigation_group' => 'Blog',
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
        'users' => [
            'model_label' => 'User',
            'plural_model_label' => 'Users',
            'navigation_label' => 'Users',
            'navigation_group' => 'Administration',
            'fields' => [
                'name' => 'Name',
                'email' => 'Email',
                'roles' => 'Roles',
                'click_to_copy' => 'Click to copy',
                'email_copied' => 'Email address copied',
            ],
            'columns' => [
                'name' => 'Name',
                'email' => 'Email',
                'roles' => 'Roles',
            ],
        ],
        'moderation_posts' => [
            'model_label' => 'Moderation Post',
            'plural_model_label' => 'Moderation Posts',
            'navigation_label' => 'Moderation Posts',
            'navigation_group' => 'Moderation',
            'fields' => [
                'title' => 'Title',
                'status' => 'Status',
            ],
            'columns' => [
                'title' => 'Title',
                'status' => 'Status',
            ],
        ],
    ],
    'roles' => [
        'super_admin' => 'Super Admin',
        'moderator' => 'Moderator',
        'posts_only' => 'Posts Only',
    ],
    'post_statuses' => [
        'draft' => 'Draft',
        'review' => 'In Review',
        'locked' => 'Locked',
    ],
    'relation_managers' => [
        'posts' => 'Posts',
        'categories' => 'Categories',
    ],
];
