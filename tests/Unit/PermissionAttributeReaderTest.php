<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Tests\Unit;

use CoringaWc\FilamentAcl\Attributes\CustomPermissionActions;
use CoringaWc\FilamentAcl\Attributes\PermissionActions;
use CoringaWc\FilamentAcl\Attributes\PermissionPanel;
use CoringaWc\FilamentAcl\Attributes\PermissionSubject;
use CoringaWc\FilamentAcl\Attributes\RegisterPermissions;
use CoringaWc\FilamentAcl\Attributes\SharedPermissionOwner;
use CoringaWc\FilamentAcl\Support\PermissionAttributeReader;
use CoringaWc\FilamentAcl\Tests\TestCase;

class PermissionAttributeReaderTest extends TestCase
{
    public function test_read_returns_null_when_no_attribute_present(): void
    {
        $result = PermissionAttributeReader::read(ClassWithoutAttributes::class, PermissionSubject::class);

        self::assertNull($result);
    }

    public function test_has_returns_false_when_no_attribute_present(): void
    {
        self::assertFalse(PermissionAttributeReader::has(ClassWithoutAttributes::class, PermissionSubject::class));
    }

    public function test_read_permission_subject_attribute(): void
    {
        $result = PermissionAttributeReader::read(ClassWithPermissionSubject::class, PermissionSubject::class);

        self::assertInstanceOf(PermissionSubject::class, $result);
        self::assertSame('custom-subject', $result->subject);
    }

    public function test_has_returns_true_for_existing_attribute(): void
    {
        self::assertTrue(PermissionAttributeReader::has(ClassWithPermissionSubject::class, PermissionSubject::class));
    }

    public function test_read_shared_permission_owner_attribute(): void
    {
        $result = PermissionAttributeReader::read(ClassWithSharedOwner::class, SharedPermissionOwner::class);

        self::assertInstanceOf(SharedPermissionOwner::class, $result);
        self::assertSame('App\Filament\Resources\PostResource', $result->ownerClass);
    }

    public function test_read_custom_permission_actions_attribute(): void
    {
        $result = PermissionAttributeReader::read(ClassWithCustomActions::class, CustomPermissionActions::class);

        self::assertInstanceOf(CustomPermissionActions::class, $result);
        self::assertSame(['archive', 'export'], $result->actions);
    }

    public function test_read_permission_actions_attribute(): void
    {
        $result = PermissionAttributeReader::read(ClassWithPermissionActions::class, PermissionActions::class);

        self::assertInstanceOf(PermissionActions::class, $result);
        self::assertSame(['view'], $result->actions);
    }

    public function test_read_register_permissions_false(): void
    {
        $result = PermissionAttributeReader::read(ClassWithRegisterPermissionsFalse::class, RegisterPermissions::class);

        self::assertInstanceOf(RegisterPermissions::class, $result);
        self::assertFalse($result->register);
    }

    public function test_read_register_permissions_default_true(): void
    {
        $result = PermissionAttributeReader::read(ClassWithRegisterPermissionsDefault::class, RegisterPermissions::class);

        self::assertInstanceOf(RegisterPermissions::class, $result);
        self::assertTrue($result->register);
    }

    public function test_read_permission_panel_attribute(): void
    {
        $result = PermissionAttributeReader::read(ClassWithPermissionPanel::class, PermissionPanel::class);

        self::assertInstanceOf(PermissionPanel::class, $result);
        self::assertSame('admin', $result->panel);
    }

    public function test_read_does_not_return_unrelated_attribute(): void
    {
        $result = PermissionAttributeReader::read(ClassWithPermissionSubject::class, SharedPermissionOwner::class);

        self::assertNull($result);
    }
}

// Test fixture classes

class ClassWithoutAttributes {}

#[PermissionSubject('custom-subject')]
class ClassWithPermissionSubject {}

#[SharedPermissionOwner('App\Filament\Resources\PostResource')]
class ClassWithSharedOwner {}

#[CustomPermissionActions(['archive', 'export'])]
class ClassWithCustomActions {}

#[PermissionActions(['view'])]
class ClassWithPermissionActions {}

#[RegisterPermissions(false)]
class ClassWithRegisterPermissionsFalse {}

#[RegisterPermissions]
class ClassWithRegisterPermissionsDefault {}

#[PermissionPanel('admin')]
class ClassWithPermissionPanel {}
