<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Concerns;

use Illuminate\Contracts\View\View;
use Illuminate\Support\MessageBag;

trait EnsuresValidationErrorBag
{
    protected ?MessageBag $workbenchErrorBag = null;

    public function getErrorBag(): MessageBag
    {
        return $this->workbenchErrorBag ??= new MessageBag;
    }

    public function setErrorBag($bag): MessageBag
    {
        $resolvedErrorBag = $bag instanceof MessageBag ? $bag : new MessageBag($bag);

        $this->workbenchErrorBag = $resolvedErrorBag;
        parent::setErrorBag($resolvedErrorBag);

        return $resolvedErrorBag;
    }

    public function render(): View
    {
        $this->resetErrorBag();

        return parent::render();
    }
}
