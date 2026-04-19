<?php

declare(strict_types=1);

use CoringaWc\FilamentAcl\Enums\PermissionEntityType;
use CoringaWc\FilamentAcl\Facades\FilamentPermission;
use CoringaWc\FilamentAcl\Support\ConfiguredPermissionSubjectResolver;
use CoringaWc\FilamentAcl\Tests\TestCase;

it('prefers the owner class subject before callbacks and config', function (): void {
    /** @var TestCase $this */
    config()->set('filament-acl.subject_overrides', [
        FakeResourceWithPermissionSubject::class => 'ConfiguredSubject',
    ]);

    FilamentPermission::resolvePermissionSubjectUsing(static fn (): string => 'CallbackSubject');

    $resolver = app(ConfiguredPermissionSubjectResolver::class);

    expect(
        $resolver->resolve(FakeResourceWithPermissionSubject::class, PermissionEntityType::Resource),
    )->toBe('DeclaredSubject');
});

it('uses a generic fallback for unknown classes', function (): void {
    /** @var TestCase $this */
    $resolver = app(ConfiguredPermissionSubjectResolver::class);

    expect(
        $resolver->resolve(
            'Vendor\\Package\\Resources\\Orders\\OrderResource',
            PermissionEntityType::Resource,
        ),
    )->toBe('VendorPackageOrders');
});

class FakeResourceWithPermissionSubject
{
    public static function getPermissionSubject(): ?string
    {
        return 'DeclaredSubject';

    }
}
