<?php

declare(strict_types=1);

return [
    'actions' => [
        'publish' => [
            'label' => 'Publish',
            'modal_heading' => 'Publish Post',
            'modal_description' => 'Are you sure you want to publish this post? It will be sent for review.',
        ],
        'archive' => [
            'label' => 'Archive',
            'modal_heading' => 'Archive Category',
            'modal_description' => 'Are you sure you want to archive this category? Its description will be cleared.',
        ],
    ],
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
    'seeds' => [
        'categories' => [
            'announcements' => [
                'name' => 'Announcements',
                'description' => 'Posts used to exercise the primary resource flow.',
            ],
            'moderation' => [
                'name' => 'Moderation',
                'description' => 'Categories used to test contextual moderation permissions.',
            ],
            'releases' => [
                'name' => 'Releases',
                'description' => 'Release-oriented records for nested-resource navigation.',
            ],
        ],
        'users' => [
            'admin' => ['name' => 'João Silva'],
            'moderator' => ['name' => 'Maria Santos'],
            'posts_only' => ['name' => 'Carlos Oliveira'],
        ],
        'posts' => [
            'draft' => [
                'title' => 'Draft Post',
                'content' => 'A draft post seeded for the Filament ACL workbench.',
            ],
            'locked' => [
                'title' => 'Locked Post',
                'content' => 'A locked post to exercise policy domain rules.',
            ],
            'moderation' => [
                'title' => 'Moderation Post',
                'content' => 'A post intended for the moderation resource.',
            ],
        ],
        'comments' => [
            'draft_1' => 'Draft comment from the seeder.',
            'draft_2' => 'Second draft comment to populate relation tables.',
            'locked_1' => 'Locked posts help exercise policy denials.',
            'moderation_1' => 'Moderation comments make the demo easier to inspect.',
        ],
    ],
];
