<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Enums;

enum SubjectResolutionStrategy: string
{
    case Basename = 'basename';
    case Fqcn = 'fqcn';
    case Custom = 'custom';
}
