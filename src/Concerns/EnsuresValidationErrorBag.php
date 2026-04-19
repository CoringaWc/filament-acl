<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Concerns;

use Illuminate\Contracts\View\View;
use Illuminate\Support\MessageBag;

trait EnsuresValidationErrorBag
{
    protected ?MessageBag $filamentAclErrorBag = null;

    public function getErrorBag(): MessageBag
    {
        return $this->filamentAclErrorBag ??= new MessageBag;
    }

    /**
     * @param  MessageBag|array<string, mixed>  $bag
     */
    public function setErrorBag($bag): MessageBag
    {
        $resolvedErrorBag = $bag instanceof MessageBag ? $bag : new MessageBag($bag);

        $this->filamentAclErrorBag = $resolvedErrorBag;
        parent::setErrorBag($resolvedErrorBag);

        return $resolvedErrorBag;
    }

    public function render(): View
    {
        $this->resetErrorBag();

        return parent::render();
    }
}
