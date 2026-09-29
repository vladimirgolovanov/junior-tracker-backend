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
        yield 'пустой день' => [
            'events' => [],
            'currentTime' => null,
            'expected' => [
                'segments' => [],
                'bedtime' => null,
                'morningAwakeTime' => null,
                'totalSleepMinutes' => 0,
                'daySleepMinutes' => 0,
                'nightSleepMinutes' => 0,
                'totalAwakeMinutes' => 0,
                'dayAwakeMinutes' => 0,
                'nightAwakeMinutes' => 0,
                'currentSleepMinutes' => 0,
                'currentAwakeMinutes' => 0,
                'isCurrentlyAsleep' => false,
                'cycleLengthMinutes' => 0,
            ],
        ];
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
/*        yield 'ночное пробуждение' => [
            'events' => [
                new Event(new \DateTimeImmutable('2026-09-18 08:55'), self::SLEEP_END),
                new Event(new \DateTimeImmutable('2026-09-18 13:10'), self::SLEEP_START),
                new Event(new \DateTimeImmutable('2026-09-18 15:45'), self::SLEEP_END),
                new Event(new \DateTimeImmutable('2026-09-18 21:15'), self::SLEEP_START),
                new Event(new \DateTimeImmutable('2026-09-19 04:00'), self::SLEEP_END),
                new Event(new \DateTimeImmutable('2026-09-19 04:15'), self::SLEEP_START),
                new Event(new \DateTimeImmutable('2026-09-19 05:15'), self::SLEEP_END),
                new Event(new \DateTimeImmutable('2026-09-19 06:10'), self::SLEEP_START),
                new Event(new \DateTimeImmutable('2026-09-19 09:15'), self::SLEEP_END),
            ],
            'currentTime' => new \DateTimeImmutable('2026-09-19 10:10'),
            'expected' => [
                'segments' => [
                    [
                        'start' => '2026-09-18 08:55',
                        'end' => '2026-09-18 13:10',
                        'state' => 'awake',
                        'dayPart' => 'day',
                        'napNumber' => null,
                        'isCurrent' => false,
                        'minutes' => 255, // 4h 15m
                    ],
                    [
                        'start' => '2026-09-18 13:10',
                        'end' => '2026-09-18 15:45',
                        'state' => 'sleep',
                        'dayPart' => 'day',
                        'napNumber' => 1,
                        'isCurrent' => false,
                        'minutes' => 155, // 2h 35m
                    ],
                    [
                        'start' => '2026-09-18 15:45',
                        'end' => '2026-09-18 21:15',
                        'state' => 'awake',
                        'dayPart' => 'day',
                        'napNumber' => null,
                        'isCurrent' => false,
                        'minutes' => 330, // 5h 30m
                    ],
                    [
                        'start' => '2026-09-18 21:15',
                        'end' => '2026-09-19 04:00',
                        'state' => 'sleep',
                        'dayPart' => 'night',
                        'napNumber' => null,
                        'isCurrent' => false,
                        'minutes' => 405, // 6h 45m
                    ],
                    [
                        'start' => '2026-09-19 04:00',
                        'end' => '2026-09-19 04:15',
                        'state' => 'awake',
                        'dayPart' => 'night',
                        'napNumber' => null,
                        'isCurrent' => false,
                        'minutes' => 15, // 0h 15m
                    ],
                    [
                        'start' => '2026-09-19 04:15',
                        'end' => '2026-09-19 05:15',
                        'state' => 'sleep',
                        'dayPart' => 'night',
                        'napNumber' => null,
                        'isCurrent' => false,
                        'minutes' => 60, // 1h 0m
                    ],
                    [
                        'start' => '2026-09-19 05:15',
                        'end' => '2026-09-19 06:10',
                        'state' => 'awake',
                        'dayPart' => 'night',
                        'napNumber' => null,
                        'isCurrent' => false,
                        'minutes' => 55, // 0h 55m
                    ],
                    [
                        'start' => '2026-09-19 06:10',
                        'end' => '2026-09-19 09:15',
                        'state' => 'sleep',
                        'dayPart' => 'day',
                        'napNumber' => 1,
                        'isCurrent' => false,
                        'minutes' => 185, // 3h 5m
                    ],
                    [
                        'start' => '2026-09-19 09:15',
                        'end' => '2026-09-19 10:10',
                        'state' => 'awake',
                        'dayPart' => 'day',
                        'napNumber' => null,
                        'isCurrent' => true,
                        'minutes' => 55, // 0h 55m
                    ],
                ],
                'bedtime' => '2026-09-18 21:15',
                'morningAwakeTime' => '2026-09-18 08:55',
                'totalSleepMinutes' => 805, // 13h 25m
                'daySleepMinutes' => 340, // 2h 35m
                'nightSleepMinutes' => 465, // 10h 50m
                'totalAwakeMinutes' => 710, // 11h 50m
                'dayAwakeMinutes' => 640, // 10h 40m
                'nightAwakeMinutes' => 70, // 1h 10m
                'currentSleepMinutes' => 0, // 0h 0m
                'currentAwakeMinutes' => 55, // 0h 55m
                'isCurrentlyAsleep' => false,
                'cycleLengthMinutes' => 1515, // 25h 15m
            ],
        ];*/
        yield 'дробный сон' => [
            'events' => [
                new Event(new \DateTimeImmutable('2026-09-25 21:00:00'), self::SLEEP_START),
                new Event(new \DateTimeImmutable('2026-09-25 21:05:00'), self::SLEEP_END),
                new Event(new \DateTimeImmutable('2026-09-25 21:30:00'), self::SLEEP_START),
                new Event(new \DateTimeImmutable('2026-09-26 05:35:00'), self::SLEEP_END),
                new Event(new \DateTimeImmutable('2026-09-26 05:45:00'), self::SLEEP_START),
                new Event(new \DateTimeImmutable('2026-09-26 05:55:00'), self::SLEEP_END),
                new Event(new \DateTimeImmutable('2026-09-26 06:05:00'), self::SLEEP_START),
                new Event(new \DateTimeImmutable('2026-09-26 07:40:00'), self::SLEEP_END),
//                new Event(new \DateTimeImmutable('2026-09-26 08:35:00'), self::SLEEP_START),
//                new Event(new \DateTimeImmutable('2026-09-26 10:35:00'), self::SLEEP_END),
//                new Event(new \DateTimeImmutable('2026-09-26 12:20:00'), self::SLEEP_START),
//                new Event(new \DateTimeImmutable('2026-09-26 13:20:00'), self::SLEEP_END),
//                new Event(new \DateTimeImmutable('2026-09-26 21:50:00'), self::SLEEP_START),
//                new Event(new \DateTimeImmutable('2026-09-27 00:20:00'), self::SLEEP_END),
//                new Event(new \DateTimeImmutable('2026-09-27 00:40:00'), self::SLEEP_START),
            ],
            'currentTime' => new \DateTimeImmutable('2026-09-26 10:10'),
            'expected' => [
                'segments' => [
                    [
                        'start' => '2026-09-25 21:00',
                        'end' => '2026-09-25 21:05',
                        'state' => 'sleep',
                        'dayPart' => 'night',
                        'napNumber' => null,
                        'isCurrent' => false,
                        'minutes' => 5,
                    ],
                    [
                        'start' => '2026-09-25 21:05',
                        'end' => '2026-09-25 21:30',
                        'state' => 'awake',
                        'dayPart' => 'night',
                        'napNumber' => null,
                        'isCurrent' => false,
                        'minutes' => 25,
                    ],
                    [
                        'start' => '2026-09-25 21:30',
                        'end' => '2026-09-26 05:35',
                        'state' => 'sleep',
                        'dayPart' => 'night',
                        'napNumber' => null,
                        'isCurrent' => false,
                        'minutes' => 485,
                    ],
                    [
                        'start' => '2026-09-26 05:35',
                        'end' => '2026-09-26 05:45',
                        'state' => 'awake',
                        'dayPart' => 'night',
                        'napNumber' => null,
                        'isCurrent' => false,
                        'minutes' => 10,
                    ],
                    [
                        'start' => '2026-09-26 05:45',
                        'end' => '2026-09-26 05:55',
                        'state' => 'sleep',
                        'dayPart' => 'night',
                        'napNumber' => null,
                        'isCurrent' => false,
                        'minutes' => 10,
                    ],
                    [
                        'start' => '2026-09-26 05:55',
                        'end' => '2026-09-26 06:05',
                        'state' => 'awake',
                        'dayPart' => 'night',
                        'napNumber' => null,
                        'isCurrent' => false,
                        'minutes' => 10,
                    ],
                    [
                        'start' => '2026-09-26 06:05',
                        'end' => '2026-09-26 07:40',
                        'state' => 'sleep',
                        'dayPart' => 'day',
                        'napNumber' => 1,
                        'isCurrent' => false,
                        'minutes' => 95,
                    ],
                    [
                        'start' => '2026-09-26 07:40',
                        'end' => '2026-09-26 10:10',
                        'state' => 'awake',
                        'dayPart' => 'day',
                        'napNumber' => null,
                        'isCurrent' => true,
                        'minutes' => 150,
                    ],
                ],
                'bedtime' => '2026-09-25 21:00',
                'morningAwakeTime' => null,
                'totalSleepMinutes' => 595,
                'daySleepMinutes' => 95,
                'nightSleepMinutes' => 500,
                'totalAwakeMinutes' => 195, // 2h 55m
                'dayAwakeMinutes' => 150, // 2h 30m
                'nightAwakeMinutes' => 45, // 25m
                'currentSleepMinutes' => 0, // 0h 0m
                'currentAwakeMinutes' => 150, // 2h 30m
                'isCurrentlyAsleep' => false,
                'cycleLengthMinutes' => 790, // 13h 10m
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
