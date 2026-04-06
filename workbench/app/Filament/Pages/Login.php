<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Pages;

use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Facades\Filament;

class Login extends BaseLogin
{
    public function mount(): void
    {
        if (Filament::auth()->check()) {
            redirect()->intended(Filament::getUrl());
        }

        $this->form->fill([
            'email' => 'admin@filament-acl.test',
            'password' => 'password',
        ]);
    }
}
