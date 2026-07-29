<?php

declare(strict_types=1);

namespace App\Tests\Application\Sleep;

use App\Application\Sleep\GetSleepSummary;
use App\Domain\Child\Repository\ChildRepositoryInterface;
use App\Domain\Event\Repository\EventRepositoryInterface;
use App\Domain\Event\Repository\EventTypeRepositoryInterface;
use App\Domain\Event\ValueObject\Event;
use App\Domain\Event\ValueObject\RangeEventType;
use App\Domain\Sleep\Service\CycleDayEventsIsolator;
use App\Domain\Sleep\Service\DayPartResolver;
use App\Domain\Sleep\Service\DaySummaryBuilder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class GetSleepSummaryTest extends TestCase
{
    private const SLEEP_START = 1;
    private const SLEEP_END = 2;

    /**
     * @param Event[]              $events
     * @param array<string, mixed> $expected
     */
    #[DataProvider('summaryProvider')]
    public function testForRange(
        array $events,
        int $childId,
        \DateTimeImmutable $firstDay,
        \DateTimeImmutable $lastDay,
        \DateTimeImmutable $now,
        array $expected,
    ): void {
        $service = new GetSleepSummary(
            $this->eventRepository($events),
            $this->eventTypeRepository(),
            $this->childRepository(),
            new CycleDayEventsIsolator(),
            new DaySummaryBuilder(new DayPartResolver()),
        );

        $summaries = $service->forRange(
            childId: $childId,
            firstDay: $firstDay,
            lastDay: $lastDay,
            now: $now,
        );

        self::assertCount(1, $summaries);
        $summary = $summaries[0];

        $actual = [
            'totalSleepMinutes' => $summary->totalSleepMinutes,
            'daySleepMinutes' => $summary->daySleepMinutes,
            'nightSleepMinutes' => $summary->nightSleepMinutes,
            'currentSleepMinutes' => $summary->currentSleepMinutes,
            'isCurrentlyAsleep' => $summary->isCurrentlyAsleep,
            'bedtime' => $summary->bedtime?->format('Y-m-d H:i'),
            'morningAwakeTime' => $summary->morningAwakeTime?->format('Y-m-d H:i'),
        ];

        foreach ($expected as $key => $value) {
            self::assertSame($value, $actual[$key], $key);
        }
    }

    public static function summaryProvider(): iterable
    {
        $tz = new \DateTimeZone('Europe/Belgrade');

        yield 'диапазон в один день' => [
            'events' => [
                new Event(new \DateTimeImmutable('2026-07-13 06:30'), self::SLEEP_END),
                new Event(new \DateTimeImmutable('2026-07-13 12:00'), self::SLEEP_START),
                new Event(new \DateTimeImmutable('2026-07-13 14:30'), self::SLEEP_END),
                new Event(new \DateTimeImmutable('2026-07-13 21:00'), self::SLEEP_START),
                new Event(new \DateTimeImmutable('2026-07-14 07:00'), self::SLEEP_END),
            ],
            'childId' => 42,
            'firstDay' => new \DateTimeImmutable('2026-07-13'),
            'lastDay' => new \DateTimeImmutable('2026-07-13'),
            'now' => new \DateTimeImmutable('2026-07-15 10:00'),
            'expected' => [
                'totalSleepMinutes' => 750,
                'daySleepMinutes' => 150,
                'nightSleepMinutes' => 600,
                'bedtime' => '2026-07-13 21:00',
                'morningAwakeTime' => '2026-07-13 06:30',
                'isCurrentlyAsleep' => false,
            ],
        ];

        yield 'текущий день, ещё спит' => [
            'events' => [
                new Event(new \DateTimeImmutable('2026-07-17 07:45', $tz), self::SLEEP_END),
                new Event(new \DateTimeImmutable('2026-07-17 11:25', $tz), self::SLEEP_START),
                new Event(new \DateTimeImmutable('2026-07-17 13:00', $tz), self::SLEEP_END),
                new Event(new \DateTimeImmutable('2026-07-17 19:25', $tz), self::SLEEP_START),
            ],
            'childId' => 1,
            'firstDay' => new \DateTimeImmutable('2026-07-17'),
            'lastDay' => new \DateTimeImmutable('2026-07-17'),
            'now' => new \DateTimeImmutable('2026-07-17 21:19', $tz),
            'expected' => [
                'isCurrentlyAsleep' => true,
                'bedtime' => '2026-07-17 19:25',
                'nightSleepMinutes' => 114,
            ],
        ];

        yield 'новый день, только проснулся' => [
            'events' => [
                new Event(new \DateTimeImmutable('2026-07-17 07:45', $tz), self::SLEEP_END),
                new Event(new \DateTimeImmutable('2026-07-17 11:25', $tz), self::SLEEP_START),
                new Event(new \DateTimeImmutable('2026-07-17 13:00', $tz), self::SLEEP_END),
                new Event(new \DateTimeImmutable('2026-07-17 19:25', $tz), self::SLEEP_START),
                new Event(new \DateTimeImmutable('2026-07-18 07:00', $tz), self::SLEEP_END),
            ],
            'childId' => 1,
            'firstDay' => new \DateTimeImmutable('2026-07-18'),
            'lastDay' => new \DateTimeImmutable('2026-07-18'),
            'now' => new \DateTimeImmutable('2026-07-18 08:00', $tz),
            'expected' => [
                'morningAwakeTime' => '2026-07-18 07:00',
                'isCurrentlyAsleep' => false,
            ],
        ];

        yield 'откат на предыдущий день' => [
            'events' => [
                new Event(new \DateTimeImmutable('2026-07-17 07:45', $tz), self::SLEEP_END),
                new Event(new \DateTimeImmutable('2026-07-17 11:25', $tz), self::SLEEP_START),
                new Event(new \DateTimeImmutable('2026-07-17 13:00', $tz), self::SLEEP_END),
                new Event(new \DateTimeImmutable('2026-07-17 19:25', $tz), self::SLEEP_START),
            ],
            'childId' => 1,
            'firstDay' => new \DateTimeImmutable('2026-07-18', $tz),
            'lastDay' => new \DateTimeImmutable('2026-07-18', $tz),
            'now' => new \DateTimeImmutable('2026-07-18 02:00', $tz),
            'expected' => [
                'morningAwakeTime' => '2026-07-17 07:45',
                'bedtime' => '2026-07-17 19:25',
                'isCurrentlyAsleep' => true,
                'currentSleepMinutes' => 395,
            ],
        ];
    }

    /** @param Event[] $events */
    private function eventRepository(array $events): EventRepositoryInterface
    {
        return new class($events) implements EventRepositoryInterface {
            /** @param Event[] $events */
            public function __construct(private array $events)
            {
            }

            public function findByChildAndTypes(int $childId, array $eventTypeIds, \DateTimeImmutable $from, \DateTimeImmutable $to, \DateTimeZone $timezone): array
            {
                return array_values(array_filter(
                    $this->events,
                    static fn (Event $e): bool => $e->occurredAt >= $from
                        && $e->occurredAt <= $to
                        && in_array($e->eventTypeId, $eventTypeIds, true),
                ));
            }
        };
    }

    private function eventTypeRepository(): EventTypeRepositoryInterface
    {
        return new class(self::SLEEP_START, self::SLEEP_END) implements EventTypeRepositoryInterface {
            public function __construct(
                private int $startId,
                private int $endId,
            ) {
            }

            public function findRangeType(int $childId, string $startName): RangeEventType
            {
                return new RangeEventType($this->startId, $this->endId);
            }

            public function findPlainType(int $childId, string $name): int
            {
                return 0;
            }
        };
    }

    private function childRepository(): ChildRepositoryInterface
    {
        return new class implements ChildRepositoryInterface {
            public function findTimezone(int $childId): \DateTimeZone
            {
                return new \DateTimeZone('Europe/Belgrade');
            }
        };
    }
}
