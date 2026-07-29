<?php

declare(strict_types=1);

namespace App\Tests\Domain\User\ValueObject;

use App\Domain\Shared\Exception\InvalidValue;
use App\Domain\User\ValueObject\PlainPassword;
use PHPUnit\Framework\TestCase;

final class PlainPasswordTest extends TestCase
{
    public function testAcceptsShortestAllowedPassword(): void
    {
        $password = new PlainPassword(str_repeat('a', PlainPassword::MIN_LENGTH));

        self::assertSame(str_repeat('a', PlainPassword::MIN_LENGTH), $password->value);
    }

    public function testRejectsTooShortPassword(): void
    {
        $this->expectException(InvalidValue::class);

        new PlainPassword(str_repeat('a', PlainPassword::MIN_LENGTH - 1));
    }

    public function testRejectsTooLongPassword(): void
    {
        $this->expectException(InvalidValue::class);

        new PlainPassword(str_repeat('a', PlainPassword::MAX_LENGTH + 1));
    }

    public function testCountsMultibyteCharacters(): void
    {
        $password = new PlainPassword(str_repeat('я', PlainPassword::MAX_LENGTH));

        self::assertSame(PlainPassword::MAX_LENGTH, mb_strlen($password->value));
    }

    public function testKeepsSurroundingWhitespace(): void
    {
        self::assertSame(' password ', (new PlainPassword(' password '))->value);
    }
}
