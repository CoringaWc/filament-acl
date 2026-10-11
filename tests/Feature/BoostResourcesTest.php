<?php

declare(strict_types=1);

use CoringaWc\FilamentAcl\Tests\TestCase;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Laravel\Boost\Console\InstallCommand;
use Laravel\Boost\Console\UpdateCommand;
use Laravel\Boost\Contracts\SupportsSkills;
use Laravel\Boost\Install\Skill;
use Laravel\Boost\Install\SkillComposer;
use Laravel\Boost\Install\SkillWriter;
use Symfony\Component\Yaml\Yaml;

test('it distributes two valid Laravel Boost skills with bundled migration guidance', function (): void {
    $root = dirname(__DIR__, 2);
    $guidelines = file_get_contents($root . '/resources/boost/guidelines/core.blade.php');

    foreach (['filament-acl', 'filament-acl-upgrade'] as $name) {
        $contents = file_get_contents($root . '/resources/boost/skills/' . $name . '/SKILL.md');
        assert(is_string($contents));
        $contents = str_replace(["\r\n", "\r"], "\n", $contents);
        $parts = explode("---\n", $contents, 3);
        $metadata = Yaml::parse($parts[1]);
        expect($metadata['name'])->toBe($name)
            ->and($metadata['description'])->toBeString()
            ->and(strlen($metadata['description']))->toBeGreaterThan(0)
            ->and($guidelines)->toContain('activate the `' . $name . '` skill');
    }

    $reference = $root . '/resources/boost/skills/filament-acl-upgrade/references/migration.md';
    expect($reference)->toBeFile()
        ->and(file_get_contents($reference))->toBe(file_get_contents($root . '/UPGRADING.md'));
});

test('Boost discovers parses and installs both skill directories with their references', function (): void {
    /** @var TestCase $this */
    $composer = app(SkillComposer::class);
    $discover = new ReflectionMethod(SkillComposer::class, 'discoverSkillsFromDirectory');
    /** @var Collection<string, Skill> $skills */
    $skills = $discover->invoke($composer, dirname(__DIR__, 2) . '/resources/boost/skills', 'coringawc/filament-acl');
    expect($skills->keys()->sort()->values()->all())->toBe(['filament-acl', 'filament-acl-upgrade']);

    $relativePath = 'storage/framework/testing/boost-' . bin2hex(random_bytes(8));
    $agent = $this->mockBuilder(SupportsSkills::class)->getMock();
    $agent->method('skillsPath')->willReturn($relativePath);

    try {
        $result = (new SkillWriter($agent))->writeAll($skills);
        expect($result)->toBe(['filament-acl' => SkillWriter::SUCCESS, 'filament-acl-upgrade' => SkillWriter::SUCCESS])
            ->and(base_path($relativePath . '/filament-acl/SKILL.md'))->toBeFile()
            ->and(base_path($relativePath . '/filament-acl-upgrade/SKILL.md'))->toBeFile()
            ->and(trim((string) file_get_contents(base_path($relativePath . '/filament-acl-upgrade/references/migration.md'))))
            ->toBe(trim((string) file_get_contents(dirname(__DIR__, 2) . '/UPGRADING.md')));
    } finally {
        File::deleteDirectory(base_path($relativePath));
    }
});

test('installed Boost exposes install and update commands with supported discovery options', function (): void {
    /** @var TestCase $this */
    // Boost deliberately disables its provider in tests; register only the commands.
    $kernel = app(Kernel::class);
    $kernel->registerCommand(app(InstallCommand::class));
    $kernel->registerCommand(app(UpdateCommand::class));
    $commands = Artisan::all();
    expect($commands)->toHaveKeys(['boost:install', 'boost:update']);
    expect($commands['boost:update']->getDefinition()->hasOption('discover'))->toBeTrue();
});
