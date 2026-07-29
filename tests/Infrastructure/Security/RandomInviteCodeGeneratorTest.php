<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Security;

use App\Infrastructure\Security\RandomInviteCodeGenerator;
use PHPUnit\Framework\TestCase;

final class RandomInviteCodeGeneratorTest extends TestCase
{
    public function testGeneratesUrlSafeNonEmptyCode(): void
    {
        $code = (new RandomInviteCodeGenerator())->generate();

        self::assertNotSame('', $code);
        self::assertMatchesRegularExpression('/^[A-Za-z0-9_-]+$/', $code);
    }

    public function testGeneratesDistinctCodes(): void
    {
        $generator = new RandomInviteCodeGenerator();

        self::assertNotSame($generator->generate(), $generator->generate());
    }
}
