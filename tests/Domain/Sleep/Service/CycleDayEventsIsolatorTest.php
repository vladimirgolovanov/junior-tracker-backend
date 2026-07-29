<?php

declare(strict_types=1);

namespace App\Tests\Domain\Sleep\Service;

use App\Domain\Event\ValueObject\Event;
use App\Domain\Event\ValueObject\RangeEventType;
use App\Domain\Sleep\Service\CycleDayEventsIsolator;
use App\Domain\Sleep\ValueObject\SleepSchedule;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CycleDayEventsIsolatorTest extends TestCase
{
    private const SLEEP_START = 1;
    private const SLEEP_END = 2;

    /**
     * @param Event[]              $events
     * @param list<array{int, string}> $expected
     */
    #[DataProvider('isolateProvider')]
    public function testIsolate(array $events, \DateTimeImmutable $date, array $expected): void
    {
        $isolated = (new CycleDayEventsIsolator())->isolate(
            events: $events,
            date: $date,
            eventTypes: new RangeEventType(self::SLEEP_START, self::SLEEP_END),
            schedule: new SleepSchedule(),
        );

        $actual = array_map(
            static fn (Event $e): array => [$e->eventTypeId, $e->occurredAt->format('Y-m-d H:i')],
            $isolated,
        );

        self::assertSame($expected, $actual);
    }

    public static function isolateProvider(): iterable
    {
        yield 'цикл целиком внутри дня' => [
            'events' => [
                new Event(new \DateTimeImmutable('2026-01-16 07:30'), self::SLEEP_END),
                new Event(new \DateTimeImmutable('2026-01-16 10:00'), self::SLEEP_START),
                new Event(new \DateTimeImmutable('2026-01-16 11:00'), self::SLEEP_END),
                new Event(new \DateTimeImmutable('2026-01-16 19:00'), self::SLEEP_START),
            ],
            'date' => new \DateTimeImmutable('2026-01-16'),
            'expected' => [
                [self::SLEEP_END, '2026-01-16 07:30'],
                [self::SLEEP_START, '2026-01-16 10:00'],
                [self::SLEEP_END, '2026-01-16 11:00'],
                [self::SLEEP_START, '2026-01-16 19:00'],
            ],
        ];
        yield 'пробуждение сразу после 06:00' => [
            'events' => [
                new Event(new \DateTimeImmutable('2026-01-16 05:30'), self::SLEEP_END),
                new Event(new \DateTimeImmutable('2026-01-16 09:00'), self::SLEEP_START),
                new Event(new \DateTimeImmutable('2026-01-16 10:30'), self::SLEEP_END),
                new Event(new \DateTimeImmutable('2026-01-16 19:00'), self::SLEEP_START),
            ],
            'date' => new \DateTimeImmutable('2026-01-16'),
            'expected' => [
                [self::SLEEP_END, '2026-01-16 05:30'],
                [self::SLEEP_START, '2026-01-16 09:00'],
                [self::SLEEP_END, '2026-01-16 10:30'],
                [self::SLEEP_START, '2026-01-16 19:00'],
            ],
        ];
        yield 'засыпание до 06:00 отброшено, следующий день обрезан' => [
            'events' => [
                new Event(new \DateTimeImmutable('2026-05-04 00:50'), self::SLEEP_START),
                new Event(new \DateTimeImmutable('2026-05-04 06:30'), self::SLEEP_END),
                new Event(new \DateTimeImmutable('2026-05-04 08:45'), self::SLEEP_START),
                new Event(new \DateTimeImmutable('2026-05-04 09:55'), self::SLEEP_END),
                new Event(new \DateTimeImmutable('2026-05-04 13:40'), self::SLEEP_START),
                new Event(new \DateTimeImmutable('2026-05-04 14:50'), self::SLEEP_END),
                new Event(new \DateTimeImmutable('2026-05-04 19:35'), self::SLEEP_START),
                new Event(new \DateTimeImmutable('2026-05-05 07:00'), self::SLEEP_END),
                new Event(new \DateTimeImmutable('2026-05-05 09:20'), self::SLEEP_START),
                new Event(new \DateTimeImmutable('2026-05-05 10:45'), self::SLEEP_END),
                new Event(new \DateTimeImmutable('2026-05-05 14:30'), self::SLEEP_START),
            ],
            'date' => new \DateTimeImmutable('2026-05-04'),
            'expected' => [
                [self::SLEEP_END, '2026-05-04 06:30'],
                [self::SLEEP_START, '2026-05-04 08:45'],
                [self::SLEEP_END, '2026-05-04 09:55'],
                [self::SLEEP_START, '2026-05-04 13:40'],
                [self::SLEEP_END, '2026-05-04 14:50'],
                [self::SLEEP_START, '2026-05-04 19:35'],
                [self::SLEEP_END, '2026-05-05 07:00'],
            ],
        ];
        yield 'новый день, только проснулся' => [
            'events' => [
                new Event(new \DateTimeImmutable('2026-05-04 00:50'), self::SLEEP_START),
                new Event(new \DateTimeImmutable('2026-05-04 06:30'), self::SLEEP_END),
                new Event(new \DateTimeImmutable('2026-05-04 08:45'), self::SLEEP_START),
                new Event(new \DateTimeImmutable('2026-05-04 09:55'), self::SLEEP_END),
                new Event(new \DateTimeImmutable('2026-05-04 13:40'), self::SLEEP_START),
                new Event(new \DateTimeImmutable('2026-05-04 14:50'), self::SLEEP_END),
                new Event(new \DateTimeImmutable('2026-05-04 19:35'), self::SLEEP_START),
                new Event(new \DateTimeImmutable('2026-05-05 07:00'), self::SLEEP_END),
            ],
            'date' => new \DateTimeImmutable('2026-05-05'),
            'expected' => [
                [self::SLEEP_END, '2026-05-05 07:00'],
            ],
        ];
    }
}
