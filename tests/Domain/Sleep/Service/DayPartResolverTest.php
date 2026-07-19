<?php

declare(strict_types=1);

namespace App\Tests\Domain\Sleep\Service;

use App\Domain\Sleep\Enum\DayPart;
use App\Domain\Sleep\Enum\SleepState;
use App\Domain\Sleep\Service\DayPartResolver;
use App\Domain\Sleep\ValueObject\SleepSchedule;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DayPartResolverTest extends TestCase
{
    #[DataProvider('resolveProvider')]
    public function testResolve(string $start, string $end, string $date, SleepState $state, DayPart $expected): void
    {
        self::assertSame(
            $expected,
            (new DayPartResolver())->resolve(
                new \DateTimeImmutable($start),
                new \DateTimeImmutable($end),
                new \DateTimeImmutable($date),
                $state,
                new SleepSchedule(),
            ),
        );
    }

    public static function resolveProvider(): iterable
    {
        yield 'дневной сон' => ['2026-03-27 09:00', '2026-03-27 10:30', '2026-03-27', SleepState::Asleep, DayPart::Day];
        yield 'вечерний отбой' => ['2026-03-27 21:00', '2026-03-28 07:00', '2026-03-27', SleepState::Asleep, DayPart::Night];
        yield 'уснул до 20:00, проснулся утром' => ['2026-03-27 19:00', '2026-03-28 07:00', '2026-03-27', SleepState::Asleep, DayPart::Night];
        yield 'сон до 20:00 ровно' => ['2026-03-27 18:00', '2026-03-27 20:00', '2026-03-27', SleepState::Asleep, DayPart::Day];
        yield 'бодрствование весь день' => ['2026-03-27 06:30', '2026-03-27 21:00', '2026-03-27', SleepState::Awake, DayPart::Day];
        yield 'бодрствование внутри дня' => ['2026-03-27 10:30', '2026-03-27 12:00', '2026-03-27', SleepState::Awake, DayPart::Day];
        yield 'проснулся ночью ненадолго' => ['2026-03-27 23:00', '2026-03-27 23:40', '2026-03-27', SleepState::Awake, DayPart::Night];
    }
}
