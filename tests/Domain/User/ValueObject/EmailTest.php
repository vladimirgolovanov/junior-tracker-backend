<?php

declare(strict_types=1);

namespace App\Tests\Domain\User\ValueObject;

use App\Domain\Shared\Exception\InvalidValue;
use App\Domain\User\ValueObject\Email;
use PHPUnit\Framework\TestCase;

final class EmailTest extends TestCase
{
    public function testKeepsOriginalCaseAndTrimsWhitespace(): void
    {
        $email = new Email('  Parent@Example.COM ');

        self::assertSame('Parent@Example.COM', $email->value);
        self::assertSame('parent@example.com', $email->lowercased());
    }

    public function testRejectsMalformedAddress(): void
    {
        $this->expectException(InvalidValue::class);

        new Email('parent@');
    }

    public function testRejectsEmptyString(): void
    {
        $this->expectException(InvalidValue::class);

        new Email('');
    }

    public function testRejectsAddressLongerThanColumn(): void
    {
        $local = str_repeat('a', Email::MAX_LENGTH);

        try {
            new Email($local.'@example.com');
        } catch (InvalidValue $exception) {
            self::assertSame('email', $exception->field);

            return;
        }

        self::fail('Expected InvalidValue for an overly long email.');
    }
}
