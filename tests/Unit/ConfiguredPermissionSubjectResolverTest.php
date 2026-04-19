<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Tests\Unit;

use CoringaWc\FilamentAcl\Enums\PermissionEntityType;
use CoringaWc\FilamentAcl\Facades\FilamentPermission;
use CoringaWc\FilamentAcl\Support\ConfiguredPermissionSubjectResolver;
use CoringaWc\FilamentAcl\Tests\TestCase;

class ConfiguredPermissionSubjectResolverTest extends TestCase
{
    public function test_it_prefers_the_owner_class_subject_before_callbacks_and_config(): void
    {
        config()->set('filament-acl.subject_overrides', [
            FakeResourceWithPermissionSubject::class => 'ConfiguredSubject',
        ]);

        FilamentPermission::resolvePermissionSubjectUsing(static fn (): string => 'CallbackSubject');

        $resolver = $this->appContainer()->make(ConfiguredPermissionSubjectResolver::class);

        self::assertSame(
            'DeclaredSubject',
            $resolver->resolve(FakeResourceWithPermissionSubject::class, PermissionEntityType::Resource),
        );
    }

    public function test_it_uses_a_generic_fallback_for_unknown_classes(): void
    {
        $resolver = $this->appContainer()->make(ConfiguredPermissionSubjectResolver::class);

        self::assertSame(
            'VendorPackageOrders',
            $resolver->resolve(
                'Vendor\\Package\\Resources\\Orders\\OrderResource',
                PermissionEntityType::Resource,
            ),
        );
    }
}

class FakeResourceWithPermissionSubject
{
    public static function getPermissionSubject(): ?string
    {
        return 'DeclaredSubject';
    }
}
