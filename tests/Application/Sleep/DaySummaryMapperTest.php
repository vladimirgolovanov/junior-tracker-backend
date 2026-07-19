<?php

declare(strict_types=1);

namespace App\Tests\Application\Sleep;

use App\Application\Sleep\DaySummaryMapper;
use App\Domain\Sleep\Service\DayPartResolver;
use App\Domain\Sleep\Service\DaySummaryBuilder;
use App\Domain\Event\ValueObject\Event;
use App\Domain\Event\ValueObject\RangeEventType;
use App\Domain\Sleep\ValueObject\SleepSchedule;
use PHPUnit\Framework\TestCase;

final class DaySummaryMapperTest extends TestCase
{
    public function testSegmentsAreReversedNewestFirst(): void
    {
        $summary = (new DaySummaryBuilder(new DayPartResolver()))->buildDaySummary(
            events: [
                new Event(new \DateTimeImmutable('2026-07-13 06:30'), 2),
                new Event(new \DateTimeImmutable('2026-07-13 12:00'), 1),
                new Event(new \DateTimeImmutable('2026-07-13 14:30'), 2),
                new Event(new \DateTimeImmutable('2026-07-13 21:00'), 1),
                new Event(new \DateTimeImmutable('2026-07-14 07:00'), 2),
            ],
            eventTypes: new RangeEventType(1, 2),
            schedule: new SleepSchedule(),
            currentTime: null,
        );

        $result = (new DaySummaryMapper())->toArray($summary);

        $starts = array_column($result['segments'], 'start');

        self::assertSame(
            [
                '2026-07-13T21:00:00+00:00',
                '2026-07-13T14:30:00+00:00',
                '2026-07-13T12:00:00+00:00',
                '2026-07-13T06:30:00+00:00',
            ],
            $starts,
        );
    }
}
