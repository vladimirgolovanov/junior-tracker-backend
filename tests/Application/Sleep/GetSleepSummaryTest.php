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
use PHPUnit\Framework\TestCase;

final class GetSleepSummaryTest extends TestCase
{
    private const SLEEP_START = 1;
    private const SLEEP_END = 2;

    public function testForRangeSingleDay(): void
    {
        $events = [
            new Event(new \DateTimeImmutable('2026-07-13 06:30'), self::SLEEP_END),
            new Event(new \DateTimeImmutable('2026-07-13 12:00'), self::SLEEP_START),
            new Event(new \DateTimeImmutable('2026-07-13 14:30'), self::SLEEP_END),
            new Event(new \DateTimeImmutable('2026-07-13 21:00'), self::SLEEP_START),
            new Event(new \DateTimeImmutable('2026-07-14 07:00'), self::SLEEP_END),
        ];

        $service = new GetSleepSummary(
            $this->eventRepository($events),
            $this->eventTypeRepository(),
            $this->childRepository(),
            new CycleDayEventsIsolator(),
            new DaySummaryBuilder(new DayPartResolver()),
        );


        $summaries = $service->forRange(
            childId: 42,
            firstDay: new \DateTimeImmutable('2026-07-13'),
            lastDay: new \DateTimeImmutable('2026-07-13'),
            now: new \DateTimeImmutable('2026-07-15 10:00'),
        );

        self::assertCount(1, $summaries);

        $summary = $summaries[0];
        self::assertSame(750, $summary->totalSleepMinutes);
        self::assertSame(150, $summary->daySleepMinutes);
        self::assertSame(600, $summary->nightSleepMinutes);
        self::assertSame('2026-07-13 21:00', $summary->bedtime->format('Y-m-d H:i'));
        self::assertSame('2026-07-13 06:30', $summary->morningAwakeTime->format('Y-m-d H:i'));
        self::assertFalse($summary->isCurrentlyAsleep);
    }

    public function testForRangeCurrentDayStillAsleep(): void
    {
        $tz = new \DateTimeZone('Europe/Belgrade');

        $events = [
            new Event(new \DateTimeImmutable('2026-07-17 07:45', $tz), self::SLEEP_END),
            new Event(new \DateTimeImmutable('2026-07-17 11:25', $tz), self::SLEEP_START),
            new Event(new \DateTimeImmutable('2026-07-17 13:00', $tz), self::SLEEP_END),
            new Event(new \DateTimeImmutable('2026-07-17 19:25', $tz), self::SLEEP_START),
        ];

        $service = new GetSleepSummary(
            $this->eventRepository($events),
            $this->eventTypeRepository(),
            $this->childRepository(),
            new CycleDayEventsIsolator(),
            new DaySummaryBuilder(new DayPartResolver()),
        );

        $summaries = $service->forRange(
            childId: 1,
            firstDay: new \DateTimeImmutable('2026-07-17'),
            lastDay: new \DateTimeImmutable('2026-07-17'),
            now: new \DateTimeImmutable('2026-07-17 21:19', $tz),
        );

        $summary = $summaries[0];

        self::assertTrue($summary->isCurrentlyAsleep);
        self::assertSame('2026-07-17 19:25', $summary->bedtime?->format('Y-m-d H:i'));
        self::assertSame(114, $summary->nightSleepMinutes);
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
