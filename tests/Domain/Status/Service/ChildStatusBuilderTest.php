<?php

declare(strict_types=1);

namespace App\Tests\Domain\Status\Service;

use App\Domain\Event\ValueObject\EventDetails;
use App\Domain\Event\ValueObject\EventType;
use App\Domain\Status\Service\ChildStatusBuilder;
use App\Domain\Status\ValueObject\ChildStatus;
use App\Domain\Status\ValueObject\CurrentSleepState;
use App\Domain\Status\ValueObject\QuickAction;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ChildStatusBuilderTest extends TestCase
{
    #[DataProvider('buildProvider')]
    public function testBuild(
        array $eventTypes,
        array $lastEvents,
        array $volumes,
        \DateTimeImmutable $now,
        array $expected,
    ): void {
        $builder = new ChildStatusBuilder();
        $result = $builder->build(1, $eventTypes, $lastEvents, $volumes, $now);

        self::assertSame(
            $expected,
            $this->toArray($result)
        );
    }

    private function toArray(ChildStatus $status): array
    {
        return [
            'child_id' => $status->childId,
            'is_currently_asleep' => $status->sleep->isCurrentlyAsleep,
            'current_sleep_minutes' => $status->sleep->sleepMinutes,
            'current_awake_minutes' => $status->sleep->awakeMinutes,
            'last_events' => array_map(fn (EventDetails $e) => [
                'event_type_id' => $e->eventTypeId,
                'name' => $e->name,
                'occurred_at' => $e->occurredAt->format(\DateTimeInterface::ATOM),
                'volume' => $e->volume,
                'description' => $e->description,
            ], $status->lastEvents),
            'quick_actions' => array_map(function (QuickAction $a) {
                $result = ['event_type_id' => $a->eventTypeId, 'focus' => $a->focus];
                if (null !== $a->volumes) {
                    $result['volumes'] = $a->volumes;
                }
                return $result;
            }, $status->quickActions),
        ];
    }

    public static function buildProvider(): iterable
    {
        yield 'full scenario' => [
            'eventTypes' => [
                new EventType(id: 1, childId: 1, name: 'sleep_start', format: 'range', color: null, parentId: null, keywords: null, showInLastEvents: false, showInQuickActions: true),
                new EventType(id: 2, childId: 1, name: 'sleep_end', format: null, color: null, parentId: 1, keywords: null, showInLastEvents: false, showInQuickActions: true),
                new EventType(id: 3, childId: 1, name: 'feeding', format: 'metric', color: null, parentId: null, keywords: null, showInLastEvents: true, showInQuickActions: true),
                new EventType(id: 4, childId: 1, name: 'walk', format: null, color: null, parentId: null, keywords: null, showInLastEvents: true, showInQuickActions: false),
                new EventType(id: 5, childId: 1, name: 'bf_start', format: 'range', color: null, parentId: null, keywords: null, showInLastEvents: true, showInQuickActions: true),
                new EventType(id: 6, childId: 1, name: 'bf_end', format: null, color: null, parentId: 5, keywords: null, showInLastEvents: true, showInQuickActions: true),
            ],
            'lastEvents' => [
                new EventDetails(id: 15, childId: 1, eventTypeId: 6, name: 'bf_end', occurredAt: new \DateTimeImmutable('2026-01-01 11:20'), volume: null, description: null),
                new EventDetails(id: 14, childId: 1, eventTypeId: 5, name: 'bf_start', occurredAt: new \DateTimeImmutable('2026-01-01 11:00'), volume: null, description: null),
                new EventDetails(id: 10, childId: 1, eventTypeId: 1, name: 'sleep_start', occurredAt: new \DateTimeImmutable('2026-01-01 10:00'), volume: null, description: null),
                new EventDetails(id: 11, childId: 1, eventTypeId: 2, name: 'sleep_end', occurredAt: new \DateTimeImmutable('2026-01-01 09:00'), volume: null, description: null),
                new EventDetails(id: 12, childId: 1, eventTypeId: 3, name: 'feeding', occurredAt: new \DateTimeImmutable('2026-01-01 08:00'), volume: 120, description: null),
            ],
            'volumes' => [3 => [120, 150]],
            'now' => new \DateTimeImmutable('2026-01-01 12:00'),
            'expected' => [
                'child_id' => 1,
                'is_currently_asleep' => true,
                'current_sleep_minutes' => 120,
                'current_awake_minutes' => 0,
                'last_events' => [
                    [
                        'event_type_id' => 3,
                        'name' => 'feeding',
                        'occurred_at' => '2026-01-01T08:00:00+00:00',
                        'volume' => 120,
                        'description' => null,
                    ],
                    [
                        'event_type_id' => 6,
                        'name' => 'bf_end',
                        'occurred_at' => '2026-01-01T11:20:00+00:00',
                        'volume' => null,
                        'description' => null,
                    ],
                ],
                'quick_actions' => [
                    [
                        'event_type_id' => 2,
                        'focus' => null,
                    ],
                    [
                        'event_type_id' => 3,
                        'focus' => 'volume',
                        'volumes' => [120, 150],
                    ],
                    [
                        'event_type_id' => 5,
                        'focus' => null,
                    ],
                ],
            ],
        ];
    }
}
