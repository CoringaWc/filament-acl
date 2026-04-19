<?php

declare(strict_types=1);

use CoringaWc\FilamentAcl\Attributes\CustomPermissionActions;
use CoringaWc\FilamentAcl\Attributes\PermissionActions;
use CoringaWc\FilamentAcl\Attributes\PermissionPanel;
use CoringaWc\FilamentAcl\Attributes\PermissionSubject;
use CoringaWc\FilamentAcl\Attributes\RegisterPermissions;
use CoringaWc\FilamentAcl\Attributes\SharedPermissionOwner;
use CoringaWc\FilamentAcl\Support\PermissionAttributeReader;
use CoringaWc\FilamentAcl\Tests\TestCase;

test('read returns null when no attribute present', function (): void {
    /** @var TestCase $this */
    $result = PermissionAttributeReader::read(ClassWithoutAttributes::class, PermissionSubject::class);

    expect($result)->toBeNull();
});

test('has returns false when no attribute present', function (): void {
    /** @var TestCase $this */
    expect(PermissionAttributeReader::has(ClassWithoutAttributes::class, PermissionSubject::class))->toBeFalse();
});

test('read permission subject attribute', function (): void {
    /** @var TestCase $this */
    $result = PermissionAttributeReader::read(ClassWithPermissionSubject::class, PermissionSubject::class);
    assert($result instanceof PermissionSubject);

    expect($result)->toBeInstanceOf(PermissionSubject::class)
        ->and($result->subject)->toBe('custom-subject');
});

test('has returns true for existing attribute', function (): void {
    /** @var TestCase $this */
    expect(PermissionAttributeReader::has(ClassWithPermissionSubject::class, PermissionSubject::class))->toBeTrue();
});

test('read shared permission owner attribute', function (): void {
    /** @var TestCase $this */
    $result = PermissionAttributeReader::read(ClassWithSharedOwner::class, SharedPermissionOwner::class);
    assert($result instanceof SharedPermissionOwner);

    expect($result)->toBeInstanceOf(SharedPermissionOwner::class)
        ->and($result->ownerClass)->toBe(SharedPermissionOwnerTarget::class);
});

test('read custom permission actions attribute', function (): void {
    /** @var TestCase $this */
    $result = PermissionAttributeReader::read(ClassWithCustomActions::class, CustomPermissionActions::class);
    assert($result instanceof CustomPermissionActions);

    expect($result)->toBeInstanceOf(CustomPermissionActions::class)
        ->and($result->actions)->toBe(['archive', 'export']);
});

test('read permission actions attribute', function (): void {
    /** @var TestCase $this */
    $result = PermissionAttributeReader::read(ClassWithPermissionActions::class, PermissionActions::class);
    assert($result instanceof PermissionActions);

    expect($result)->toBeInstanceOf(PermissionActions::class)
        ->and($result->actions)->toBe(['view']);
});

test('read register permissions false', function (): void {
    /** @var TestCase $this */
    $result = PermissionAttributeReader::read(ClassWithRegisterPermissionsFalse::class, RegisterPermissions::class);
    assert($result instanceof RegisterPermissions);

    expect($result)->toBeInstanceOf(RegisterPermissions::class)
        ->and($result->register)->toBeFalse();
});

test('read register permissions default true', function (): void {
    /** @var TestCase $this */
    $result = PermissionAttributeReader::read(ClassWithRegisterPermissionsDefault::class, RegisterPermissions::class);
    assert($result instanceof RegisterPermissions);

    expect($result)->toBeInstanceOf(RegisterPermissions::class)
        ->and($result->register)->toBeTrue();
});

test('read permission panel attribute', function (): void {
    /** @var TestCase $this */
    $result = PermissionAttributeReader::read(ClassWithPermissionPanel::class, PermissionPanel::class);
    assert($result instanceof PermissionPanel);

    expect($result)->toBeInstanceOf(PermissionPanel::class)
        ->and($result->panel)->toBe('admin');
});

test('read does not return unrelated attribute', function (): void {
    /** @var TestCase $this */
    $result = PermissionAttributeReader::read(ClassWithPermissionSubject::class, SharedPermissionOwner::class);

    expect($result)->toBeNull();
});

class ClassWithoutAttributes {}

class SharedPermissionOwnerTarget {}

#[PermissionSubject('custom-subject')]
class ClassWithPermissionSubject {}

#[SharedPermissionOwner(SharedPermissionOwnerTarget::class)]
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
