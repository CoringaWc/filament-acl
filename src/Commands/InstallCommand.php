<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Commands;

use CoringaWc\FilamentAcl\FilamentPermissionServiceProvider;
use CoringaWc\FilamentAcl\Support\Utils;
use Filament\Facades\Filament;
use Illuminate\Console\Command;
use Illuminate\Console\Prohibitable;
use Illuminate\Filesystem\Filesystem;
use Spatie\Permission\PermissionServiceProvider;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'filament-acl:install', description: 'Publish and configure the Filament ACL package')]
class InstallCommand extends Command
{
    use Prohibitable;

    /** @var string */
    protected $signature = 'filament-acl:install
        {--force : Overwrite existing config and migration files}
        {--panel= : Panel ID to forward to the admin-user command}
        {--with-admin-user : Run the admin-user command after installation}
        {--migrate : Run database migrations after publishing files}
        {--sync : Sync permissions after installation}';

    public function __construct(protected Filesystem $files)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        if ($this->isProhibited()) {
            return self::FAILURE;
        }

        $detectedMorphKeyType = Utils::detectMorphKeyType();
        $didSyncPermissions = false;

        $this->publishPermissionConfig();
        $this->publishPackageConfig($detectedMorphKeyType);
        $this->publishPackageMigration();

        if ((bool) $this->option('migrate') || ($this->input->isInteractive() && $this->confirm('Run migrations now?', true))) {
            $this->call('migrate', ['--force' => true]);
            $this->call('filament-acl:sync', [
                '--with-protected-role' => true,
            ]);
            $didSyncPermissions = true;
        }

        $panelId = $this->resolvePanelId();

        if ((! $didSyncPermissions) && ((bool) $this->option('sync') || ($this->input->isInteractive() && $this->confirm(sprintf(
            'Sync permissions for panel [%s] now?',
            $panelId ?? 'all',
        ), true)))) {
            $arguments = [
                '--with-protected-role' => true,
            ];

            if ($panelId !== null) {
                $arguments['--panel'] = [$panelId];
            }

            $this->call('filament-acl:sync', $arguments);
        }

        if ((bool) $this->option('with-admin-user') || ($this->input->isInteractive() && $this->confirm('Create or promote an admin user now?', true))) {
            $this->call('filament-acl:admin-user', array_filter([
                '--panel' => $panelId,
            ], static fn (mixed $value): bool => filled($value)));
        }

        $this->components->info(sprintf(
            'Filament ACL installed. Detected user morph key type: [%s].',
            $detectedMorphKeyType,
        ));

        return self::SUCCESS;
    }

    protected function publishPermissionConfig(): void
    {
        $path = config_path('permission.php');

        if ($this->files->exists($path) && (! $this->shouldOverwrite('permission config'))) {
            return;
        }

        $this->call('vendor:publish', [
            '--provider' => PermissionServiceProvider::class,
            '--tag' => 'permission-config',
            '--force' => true,
        ]);
    }

    protected function publishPackageConfig(string $detectedMorphKeyType): void
    {
        if (Utils::hasPublishedConfig() && (! $this->shouldOverwrite('filament-acl config'))) {
            $this->components->warn(sprintf(
                'Skipping config publish. Ensure %s has database.model_morph_key.type set to [%s].',
                Utils::getPublishedConfigPath(),
                $detectedMorphKeyType,
            ));

            return;
        }

        $this->call('vendor:publish', [
            '--provider' => FilamentPermissionServiceProvider::class,
            '--tag' => 'filament-acl-config',
            '--force' => true,
        ]);

        $this->synchronizeMorphKeyTypeInConfig($detectedMorphKeyType);
    }

    protected function publishPackageMigration(): void
    {
        $existingMigration = Utils::findPublishedPermissionMigration();

        if (($existingMigration !== null) && (! $this->shouldOverwrite('filament-acl migration'))) {
            return;
        }

        if ($existingMigration !== null) {
            $this->files->delete($existingMigration);
        }

        $this->call('vendor:publish', [
            '--provider' => FilamentPermissionServiceProvider::class,
            '--tag' => 'filament-acl-migrations',
            '--force' => true,
        ]);
    }

    protected function shouldOverwrite(string $subject): bool
    {
        if ((bool) $this->option('force')) {
            return true;
        }

        if (! $this->input->isInteractive()) {
            return false;
        }

        return $this->confirm(sprintf(
            'A %s file already exists. Do you want to overwrite it?',
            $subject,
        ), false);
    }

    protected function synchronizeMorphKeyTypeInConfig(string $detectedMorphKeyType): void
    {
        $configPath = Utils::getPublishedConfigPath();

        if (! $this->files->exists($configPath)) {
            return;
        }

        $contents = $this->files->get($configPath);

        $updatedContents = preg_replace(
            "/('model_morph_key'\\s*=>\\s*\\[[^\\]]*?'type'\\s*=>\\s*)(null|'[^']*')/s",
            "\$1'{$detectedMorphKeyType}'",
            $contents,
            1,
        );

        if (! is_string($updatedContents)) {
            return;
        }

        $this->files->put($configPath, $updatedContents);
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
}
