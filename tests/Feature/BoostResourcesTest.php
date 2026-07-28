<?php

declare(strict_types=1);

test('it distributes Laravel Boost guidelines and a valid skill', function (): void {
    $guidelinesPath = dirname(__DIR__, 2) . '/resources/boost/guidelines/core.blade.php';
    $skillPath = dirname(__DIR__, 2) . '/resources/boost/skills/filament-acl/SKILL.md';
    $skillContents = str_replace(["\r\n", "\r"], "\n", file_get_contents($skillPath));

    expect($guidelinesPath)->toBeFile()
        ->and(file_get_contents($guidelinesPath))->toContain('activate the `filament-acl` skill')
        ->and($skillPath)->toBeFile()
        ->and($skillContents)
        ->toStartWith("---\nname: filament-acl\n")
        ->toContain('description:')
        ->toContain('# Filament ACL');
});
