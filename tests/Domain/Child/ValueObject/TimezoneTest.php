<?php

declare(strict_types=1);

namespace App\Tests\Domain\Child\ValueObject;

use App\Domain\Child\ValueObject\Timezone;
use App\Domain\Shared\Exception\InvalidValue;
use PHPUnit\Framework\TestCase;

final class TimezoneTest extends TestCase
{
    public function testAcceptsIanaIdentifier(): void
    {
        $timezone = new Timezone('Europe/Moscow');

        self::assertSame('Europe/Moscow', $timezone->value);
        self::assertSame('Europe/Moscow', $timezone->toDateTimeZone()->getName());
    }

    /**
     * \DateTimeZone проглотил бы оба варианта, но в childs.timezone нужен
     * идентификатор, который переживёт переход на летнее время.
     */
    public function testRejectsOffsetAndAbbreviation(): void
    {
        foreach (['+03:00', 'MSK'] as $value) {
            try {
                new Timezone($value);
                self::fail(sprintf('Expected InvalidValue for timezone "%s".', $value));
            } catch (InvalidValue $exception) {
                self::assertSame('timezone', $exception->field);
            }
        }
    }

    public function testRejectsUnknownIdentifier(): void
    {
        $this->expectException(InvalidValue::class);

        new Timezone('Nowhere/Nowhere');
    }
}
