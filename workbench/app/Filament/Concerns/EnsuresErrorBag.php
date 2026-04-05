<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Concerns;

use Illuminate\Support\MessageBag;

trait EnsuresErrorBag
{
    public function getErrorBag(): MessageBag
    {
        $errorBag = parent::getErrorBag();

        if ($errorBag instanceof MessageBag) {
            return $errorBag;
        }

        $this->setErrorBag([]);

        $resolvedErrorBag = parent::getErrorBag();

        return $resolvedErrorBag instanceof MessageBag
            ? $resolvedErrorBag
            : new MessageBag;
    }
}
