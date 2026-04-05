<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Commands;

use CoringaWc\FilamentAcl\Support\Utils;
use Filament\Facades\Filament;
use Illuminate\Console\Command;
use Illuminate\Console\Prohibitable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Traits\HasRoles;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'filament-acl:admin-user', description: 'Create or promote a protected Filament ACL admin user')]
class AdminUserCommand extends Command
{
    use Prohibitable;

    /** @var string */
    protected $signature = 'filament-acl:admin-user
        {--user= : ID of the user to promote}
        {--email= : Email of the user to promote or create}
        {--name= : Name to use when creating a new user}
        {--password= : Password to use when creating a new user}
        {--panel= : Panel ID used to resolve the auth guard}
        {--no-permission-sync : Do not sync all permissions to the protected role}';

    public function handle(): int
    {
        if ($this->isProhibited()) {
            return self::FAILURE;
        }

        $panelId = $this->resolvePanelId();
        $userModel = Utils::getUserModel($panelId);
        $user = $this->resolveUser($userModel);

        if (! $user instanceof Authenticatable) {
            $this->components->error(sprintf(
                'User model [%s] must implement %s.',
                $userModel,
                Authenticatable::class,
            ));

            return self::FAILURE;
        }

        if (! in_array(HasRoles::class, class_uses_recursive($userModel), true)) {
            $this->components->error(sprintf(
                'User model [%s] must use the HasRoles trait from spatie/laravel-permission.',
                $userModel,
            ));

            return self::FAILURE;
        }

        $protectedRole = Utils::createProtectedRole($panelId);

        if (! (bool) $this->option('no-permission-sync')) {
            $protectedRole->syncPermissions(Utils::getAllPermissionIds($panelId));
        }

        $user->syncRoles([$protectedRole]);

        $loginUrl = null;

        if (($panelId !== null) && in_array($panelId, array_keys(Filament::getPanels()), true)) {
            $loginUrl = Filament::getPanel($panelId)->getLoginUrl();
        } elseif (Filament::getDefaultPanel()) {
            $loginUrl = Filament::getDefaultPanel()->getLoginUrl();
        }

        $message = sprintf(
            'User [%s] now has the protected role [%s].',
            (string) ($user->getAttribute('email') ?? $user->getKey()),
            Utils::getProtectedRoleName(),
        );

        if (filled($loginUrl)) {
            $message .= sprintf(' Login URL: %s', $loginUrl);
        }

        $this->components->info($message);

        return self::SUCCESS;
    }

    protected function resolvePanelId(): ?string
    {
        $panelId = $this->option('panel');

        if (is_string($panelId) && filled($panelId)) {
            return $panelId;
        }

        $panelIds = array_keys(Filament::getPanels());

        if ($panelIds === []) {
            return null;
        }

        if ((count($panelIds) === 1) || (! $this->input->isInteractive())) {
            return $panelIds[0];
        }

        /** @var string $selectedPanel */
        $selectedPanel = $this->choice('Which panel should be used?', $panelIds, $panelIds[0]);

        return $selectedPanel;
    }

    /**
     * @param  class-string<Model>  $userModel
     */
    protected function resolveUser(string $userModel): Model
    {
        $userId = $this->option('user');

        if (filled($userId)) {
            /** @var Model $user */
            $user = $userModel::query()->findOrFail($userId);

            return $user;
        }

        $email = (string) ($this->option('email') ?? '');

        if (filled($email)) {
            /** @var Model|null $existingUser */
            $existingUser = $userModel::query()->where('email', $email)->first();

            return $existingUser ?? $this->createUser($userModel, $email);
        }

        $count = $userModel::query()->count();

        if ($count === 0) {
            return $this->createUser($userModel);
        }

        if (($count === 1) || (! $this->input->isInteractive())) {
            /** @var Model $user */
            $user = $userModel::query()->firstOrFail();

            return $user;
        }

        $users = $userModel::query()->limit(10)->get(['id', 'name', 'email']);

        $this->table(
            ['ID', 'Name', 'Email'],
            $users->map(static fn (Model $user): array => [
                'id' => $user->getKey(),
                'name' => (string) $user->getAttribute('name'),
                'email' => (string) $user->getAttribute('email'),
            ])->all(),
        );

        $selectedUserId = $this->ask('Provide an existing User ID or leave blank to create a new user');

        if (filled($selectedUserId)) {
            /** @var Model $user */
            $user = $userModel::query()->findOrFail($selectedUserId);

            return $user;
        }

        return $this->createUser($userModel);
    }

    /**
     * @param  class-string<Model>  $userModel
     */
    protected function createUser(string $userModel, ?string $email = null): Model
    {
        $email ??= (string) $this->ask('Email address');
        $name = (string) ($this->option('name') ?: $this->ask('Name'));
        $password = (string) ($this->option('password') ?: $this->secret('Password'));

        /** @var Model&Authenticatable $user */
        $user = new $userModel;
        $user->forceFill([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'email_verified_at' => now(),
        ]);
        $user->save();

        return $user;
    }
}
