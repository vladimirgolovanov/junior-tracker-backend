<?php

declare(strict_types=1);

namespace App\Tests\Domain\Sleep\Service;

use App\Domain\Event\ValueObject\Event;
use App\Domain\Event\ValueObject\RangeEventType;
use App\Domain\Sleep\Service\DayPartResolver;
use App\Domain\Sleep\Service\DaySummaryBuilder;
use App\Domain\Sleep\ValueObject\DaySummary;
use App\Domain\Sleep\ValueObject\SleepSchedule;
use App\Domain\Sleep\ValueObject\SleepSegment;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DaySummaryBuilderTest extends TestCase
{
    const SLEEP_START = 1;
    const SLEEP_END = 2;

    #[DataProvider('daySummaryProvider')]
    public function testBuildDaySummary(array $events, ?\DateTimeImmutable $currentTime, array $expected): void
    {
        $summary = (new DaySummaryBuilder(new DayPartResolver()))->buildDaySummary(
            events: $events,
            eventTypes: new RangeEventType(self::SLEEP_START, self::SLEEP_END),
            schedule: new SleepSchedule(),
            currentTime: $currentTime,
        );

        self::assertSame($expected, $this->toArray($summary));
    }

    public static function daySummaryProvider(): iterable
    {
        yield 'дневной сон и ночной сон' => [
            'events' => [
                new Event(new \DateTimeImmutable('2026-07-13 06:30'), self::SLEEP_END),
                new Event(new \DateTimeImmutable('2026-07-13 12:00'), self::SLEEP_START),
                new Event(new \DateTimeImmutable('2026-07-13 14:30'), self::SLEEP_END),
                new Event(new \DateTimeImmutable('2026-07-13 21:00'), self::SLEEP_START),
                new Event(new \DateTimeImmutable('2026-07-14 07:00'), self::SLEEP_END),
            ],
            'currentTime' => null,
            'expected' => [
                'segments' => [
                    [
                        'start' => '2026-07-13 06:30',
                        'end' => '2026-07-13 12:00',
                        'state' => 'awake',
                        'dayPart' => 'day',
                        'napNumber' => null,
                        'isCurrent' => false,
                        'minutes' => 330,
                    ],
                    [
                        'start' => '2026-07-13 12:00',
                        'end' => '2026-07-13 14:30',
                        'state' => 'sleep',
                        'dayPart' => 'day',
                        'napNumber' => 1,
                        'isCurrent' => false,
                        'minutes' => 150,
                    ],
                    [
                        'start' => '2026-07-13 14:30',
                        'end' => '2026-07-13 21:00',
                        'state' => 'awake',
                        'dayPart' => 'day',
                        'napNumber' => null,
                        'isCurrent' => false,
                        'minutes' => 390,
                    ],
                    [
                        'start' => '2026-07-13 21:00',
                        'end' => '2026-07-14 07:00',
                        'state' => 'sleep',
                        'dayPart' => 'night',
                        'napNumber' => null,
                        'isCurrent' => false,
                        'minutes' => 600,
                    ],
                ],
                'bedtime' => '2026-07-13 21:00',
                'morningAwakeTime' => '2026-07-13 06:30',
                'totalSleepMinutes' => 750,
                'daySleepMinutes' => 150,
                'nightSleepMinutes' => 600,
                'totalAwakeMinutes' => 720,
                'dayAwakeMinutes' => 720,
                'nightAwakeMinutes' => 0,
                'currentSleepMinutes' => 0,
                'currentAwakeMinutes' => 0,
                'isCurrentlyAsleep' => false,
                'cycleLengthMinutes' => 1470,
            ],
        ];
        yield 'just waked up' => [
            'events' => [
                new Event(new \DateTimeImmutable('2026-07-13 06:30'), self::SLEEP_END),
            ],
            'currentTime' => new \DateTimeImmutable('2026-07-13 08:00'),
            'expected' => [
                'segments' => [
                    [
                        'start' => '2026-07-13 06:30',
                        'end' => '2026-07-13 08:00',
                        'state' => 'awake',
                        'dayPart' => 'day',
                        'napNumber' => null,
                        'isCurrent' => true,
                        'minutes' => 90,
                    ],
                ],
                'bedtime' => null,
                'morningAwakeTime' => '2026-07-13 06:30',
                'totalSleepMinutes' => 0,
                'daySleepMinutes' => 0,
                'nightSleepMinutes' => 0,
                'totalAwakeMinutes' => 90,
                'dayAwakeMinutes' => 90,
                'nightAwakeMinutes' => 0,
                'currentSleepMinutes' => 0,
                'currentAwakeMinutes' => 90,
                'isCurrentlyAsleep' => false,
                'cycleLengthMinutes' => 90,
            ],
        ];
        yield 'ещё спит с вечера' => [
            'events' => [
                new Event(new \DateTimeImmutable('2026-07-13 06:30'), self::SLEEP_END),
                new Event(new \DateTimeImmutable('2026-07-13 12:00'), self::SLEEP_START),
                new Event(new \DateTimeImmutable('2026-07-13 14:30'), self::SLEEP_END),
                new Event(new \DateTimeImmutable('2026-07-13 21:00'), self::SLEEP_START),
            ],
            'currentTime' => new \DateTimeImmutable('2026-07-14 07:30'),
            'expected' => [
                'segments' => [
                    [
                        'start' => '2026-07-13 06:30',
                        'end' => '2026-07-13 12:00',
                        'state' => 'awake',
                        'dayPart' => 'day',
                        'napNumber' => null,
                        'isCurrent' => false,
                        'minutes' => 330,
                    ],
                    [
                        'start' => '2026-07-13 12:00',
                        'end' => '2026-07-13 14:30',
                        'state' => 'sleep',
                        'dayPart' => 'day',
                        'napNumber' => 1,
                        'isCurrent' => false,
                        'minutes' => 150,
                    ],
                    [
                        'start' => '2026-07-13 14:30',
                        'end' => '2026-07-13 21:00',
                        'state' => 'awake',
                        'dayPart' => 'day',
                        'napNumber' => null,
                        'isCurrent' => false,
                        'minutes' => 390,
                    ],
                    [
                        'start' => '2026-07-13 21:00',
                        'end' => '2026-07-14 07:30',
                        'state' => 'sleep',
                        'dayPart' => 'night',
                        'napNumber' => null,
                        'isCurrent' => true,
                        'minutes' => 630,
                    ],
                ],
                'bedtime' => '2026-07-13 21:00',
                'morningAwakeTime' => '2026-07-13 06:30',
                'totalSleepMinutes' => 780,
                'daySleepMinutes' => 150,
                'nightSleepMinutes' => 630,
                'totalAwakeMinutes' => 720,
                'dayAwakeMinutes' => 720,
                'nightAwakeMinutes' => 0,
                'currentSleepMinutes' => 630,
                'currentAwakeMinutes' => 0,
                'isCurrentlyAsleep' => true,
                'cycleLengthMinutes' => 1500,
            ],
        ];
        yield 'отбой до 20:00, ещё спит' => [
            'events' => [
                new Event(new \DateTimeImmutable('2026-07-17 07:45', new \DateTimeZone('Europe/Belgrade')), self::SLEEP_END),
                new Event(new \DateTimeImmutable('2026-07-17 11:25', new \DateTimeZone('Europe/Belgrade')), self::SLEEP_START),
                new Event(new \DateTimeImmutable('2026-07-17 13:00', new \DateTimeZone('Europe/Belgrade')), self::SLEEP_END),
                new Event(new \DateTimeImmutable('2026-07-17 19:25', new \DateTimeZone('Europe/Belgrade')), self::SLEEP_START),
            ],
            'currentTime' => new \DateTimeImmutable('2026-07-17 21:19', new \DateTimeZone('Europe/Belgrade')),
            'expected' => [
                'segments' => [
                    [
                        'start' => '2026-07-17 07:45',
                        'end' => '2026-07-17 11:25',
                        'state' => 'awake',
                        'dayPart' => 'day',
                        'napNumber' => null,
                        'isCurrent' => false,
                        'minutes' => 220,
                    ],
                    [
                        'start' => '2026-07-17 11:25',
                        'end' => '2026-07-17 13:00',
                        'state' => 'sleep',
                        'dayPart' => 'day',
                        'napNumber' => 1,
                        'isCurrent' => false,
                        'minutes' => 95,
                    ],
                    [
                        'start' => '2026-07-17 13:00',
                        'end' => '2026-07-17 19:25',
                        'state' => 'awake',
                        'dayPart' => 'day',
                        'napNumber' => null,
                        'isCurrent' => false,
                        'minutes' => 385,
                    ],
                    [
                        'start' => '2026-07-17 19:25',
                        'end' => '2026-07-17 21:19',
                        'state' => 'sleep',
                        'dayPart' => 'night',
                        'napNumber' => null,
                        'isCurrent' => true,
                        'minutes' => 114,
                    ],
                ],
                'bedtime' => '2026-07-17 19:25',
                'morningAwakeTime' => '2026-07-17 07:45',
                'totalSleepMinutes' => 209,
                'daySleepMinutes' => 95,
                'nightSleepMinutes' => 114,
                'totalAwakeMinutes' => 605,
                'dayAwakeMinutes' => 605,
                'nightAwakeMinutes' => 0,
                'currentSleepMinutes' => 114,
                'currentAwakeMinutes' => 0,
                'isCurrentlyAsleep' => true,
                'cycleLengthMinutes' => 814,
            ],
        ];
    }

    private function toArray(DaySummary $summary): array
    {
        return [
            'segments' => array_map(
                static fn (SleepSegment $s): array => [
                    'start' => $s->start->format('Y-m-d H:i'),
                    'end' => $s->end->format('Y-m-d H:i'),
                    'state' => $s->state->value,
                    'dayPart' => $s->dayPart->value,
                    'napNumber' => $s->napNumber,
                    'isCurrent' => $s->isCurrent,
                    'minutes' => intdiv($s->durationInSeconds(), 60),
                ],
                $summary->segments,
            ),
            'bedtime' => $summary->bedtime?->format('Y-m-d H:i'),
            'morningAwakeTime' => $summary->morningAwakeTime?->format('Y-m-d H:i'),
            'totalSleepMinutes' => $summary->totalSleepMinutes,
            'daySleepMinutes' => $summary->daySleepMinutes,
            'nightSleepMinutes' => $summary->nightSleepMinutes,
            'totalAwakeMinutes' => $summary->totalAwakeMinutes,
            'dayAwakeMinutes' => $summary->dayAwakeMinutes,
            'nightAwakeMinutes' => $summary->nightAwakeMinutes,
            'currentSleepMinutes' => $summary->currentSleepMinutes,
            'currentAwakeMinutes' => $summary->currentAwakeMinutes,
            'isCurrentlyAsleep' => $summary->isCurrentlyAsleep,
            'cycleLengthMinutes' => $summary->cycleLengthMinutes,
        ];
    }
}
