<?php

declare(strict_types=1);

namespace App\Tests\Domain\Auth\ValueObject;

use App\Domain\Auth\ValueObject\AccessToken;
use PHPUnit\Framework\TestCase;

final class AccessTokenTest extends TestCase
{
    public function testAcceptsMaxLengthValue(): void
    {
        $value = str_repeat('a', AccessToken::MAX_LENGTH);

        $token = new AccessToken($value, 1, new \DateTimeImmutable('2026-07-30 10:00'));

        self::assertSame($value, $token->value);
        self::assertSame(1, $token->userId);
    }

    public function testRejectsValueLongerThanColumn(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new AccessToken(str_repeat('a', AccessToken::MAX_LENGTH + 1), 1, new \DateTimeImmutable('2026-07-30 10:00'));
    }

    public function testRejectsEmptyValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new AccessToken('', 1, new \DateTimeImmutable('2026-07-30 10:00'));
    }
}
