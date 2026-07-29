<?php

declare(strict_types=1);

namespace App\Tests\Domain\User\ValueObject;

use App\Domain\Shared\Exception\InvalidValue;
use App\Domain\User\ValueObject\Credentials;
use App\Domain\User\ValueObject\Email;
use App\Domain\User\ValueObject\PlainPassword;
use PHPUnit\Framework\TestCase;

final class CredentialsTest extends TestCase
{
    public function testAcceptsDistinctEmailAndPassword(): void
    {
        $credentials = new Credentials(
            new Email('parent@example.com'),
            new PlainPassword('correcthorse'),
        );

        self::assertSame('parent@example.com', $credentials->email->value);
        self::assertSame('correcthorse', $credentials->password->value);
    }

    public function testRejectsPasswordEqualToEmailRegardlessOfCase(): void
    {
        $this->expectException(InvalidValue::class);

        new Credentials(
            new Email('parent@example.com'),
            new PlainPassword('Parent@Example.COM'),
        );
    }
}
