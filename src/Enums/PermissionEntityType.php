<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Enums;

enum PermissionEntityType: string
{
    case Resource = 'resource';
    case RelationManager = 'relation_manager';
    case Page = 'page';
    case Widget = 'widget';
    case Action = 'action';
    case CustomPermission = 'custom_permission';
}
