<?php

declare(strict_types=1);
use CoringaWc\FilamentAcl\Support\PermissionActionResolver;
use CoringaWc\FilamentAcl\Tests\TestCase;

it('returns null for mistyped owner class references', function (): void {
    /** @var TestCase $this */
    $resolver = app(PermissionActionResolver::class);

    expect(
        $resolver->resolve('update', 'CoringaWc\\FilamentAcl\\Tests\\Fixtures\\FakePostResorce'),
    )->toBeNull();
});
it('keeps plain string permission subjects intact', function (): void {
    /** @var TestCase $this */
    $resolver = app(PermissionActionResolver::class);

    expect($resolver->resolve('update', 'BlogPosts'))->toBe('BlogPosts');
});
