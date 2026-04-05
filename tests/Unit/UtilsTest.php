<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Tests\Unit;

use CoringaWc\FilamentAcl\Support\Utils;
use CoringaWc\FilamentAcl\Tests\TestCase;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class UtilsTest extends TestCase
{
    public function test_it_detects_uuid_model_keys(): void
    {
        self::assertSame('uuid', Utils::detectMorphKeyType(FakeUuidUser::class));
    }

    public function test_it_detects_ulid_model_keys(): void
    {
        self::assertSame('ulid', Utils::detectMorphKeyType(FakeUlidUser::class));
    }

    public function test_it_detects_integer_model_keys(): void
    {
        self::assertSame('unsignedBigInteger', Utils::detectMorphKeyType(FakeIntUser::class));
    }
}

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
