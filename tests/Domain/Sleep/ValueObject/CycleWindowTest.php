<?php

declare(strict_types=1);

namespace App\Tests\Domain\Sleep\ValueObject;

use App\Domain\Sleep\ValueObject\CycleWindow;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CycleWindowTest extends TestCase
{
    #[DataProvider('windowProvider')]
    public function testForDates(string $firstDay, string $lastDay, string $expectedFrom, string $expectedTo): void
    {
        $window = CycleWindow::forDates(
            new \DateTimeImmutable($firstDay),
            new \DateTimeImmutable($lastDay),
        );

        self::assertSame($expectedFrom, $window->from->format('Y-m-d H:i:s'));
        self::assertSame($expectedTo, $window->to->format('Y-m-d H:i:s'));
    }

    public static function windowProvider(): iterable
    {
        yield 'один день' => ['2026-07-13', '2026-07-13', '2026-07-13 00:00:00', '2026-07-14 23:59:59'];
        yield 'диапазон трёх дней' => ['2026-07-13', '2026-07-15', '2026-07-13 00:00:00', '2026-07-16 23:59:59'];
        yield 'переход через конец месяца' => ['2026-07-31', '2026-07-31', '2026-07-31 00:00:00', '2026-08-01 23:59:59'];
    }
}
