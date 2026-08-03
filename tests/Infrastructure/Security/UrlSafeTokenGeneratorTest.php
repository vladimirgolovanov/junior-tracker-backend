<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Security;

use App\Domain\Auth\ValueObject\AccessToken;
use App\Infrastructure\Security\UrlSafeTokenGenerator;
use PHPUnit\Framework\TestCase;

final class UrlSafeTokenGeneratorTest extends TestCase
{
    /**
     * Токен должен влезать в access_tokens.token varchar(43) и совпадать
     * по алфавиту с secrets.token_urlsafe() из fastapi-users.
     */
    public function testProducesUrlSafeTokenOfExpectedLength(): void
    {
        $token = (new UrlSafeTokenGenerator())->generate();

        self::assertSame(AccessToken::MAX_LENGTH, mb_strlen($token));
        self::assertMatchesRegularExpression('~^[A-Za-z0-9_-]{43}$~', $token);
    }

    public function testGeneratesDistinctTokens(): void
    {
        $generator = new UrlSafeTokenGenerator();

        self::assertNotSame($generator->generate(), $generator->generate());
    }
}
