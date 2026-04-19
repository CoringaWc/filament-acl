<?php

declare(strict_types=1);

use CoringaWc\FilamentAcl\Support\Utils;
use CoringaWc\FilamentAcl\Tests\TestCase;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

test('it detects uuid model keys', function (): void {
    /** @var TestCase $this */
    expect(Utils::detectMorphKeyType(FakeUuidUser::class))->toBe('uuid');
});

test('it detects ulid model keys', function (): void {
    /** @var TestCase $this */
    expect(Utils::detectMorphKeyType(FakeUlidUser::class))->toBe('ulid');
});

test('it detects integer model keys', function (): void {
    /** @var TestCase $this */
    expect(Utils::detectMorphKeyType(FakeIntUser::class))->toBe('unsignedBigInteger');
});

class FakeUuidUser extends Model
{
    use HasUuids;

    protected $guarded = [];
}

class FakeUlidUser extends Model
{
    use HasUlids;

    protected $guarded = [];
}

class FakeIntUser extends Model
{
    protected $guarded = [];
}
