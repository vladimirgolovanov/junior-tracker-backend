<?php

declare(strict_types=1);

namespace App\Application\Status;

use App\Domain\Event\ValueObject\EventDetails;
use App\Domain\Status\ValueObject\ChildStatus;
use App\Domain\Status\ValueObject\Action;

final class ChildStatusMapper
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(ChildStatus $status): array
    {
        return [
            'child_id' => $status->childId,
            'is_currently_asleep' => $status->sleep->isCurrentlyAsleep,
            'current_sleep_minutes' => $status->sleep->sleepMinutes,
            'current_awake_minutes' => $status->sleep->awakeMinutes,
            'last_events' => array_map($this->lastEventToArray(...), $status->lastEvents),
            'actions' => array_map($this->actionToArray(...), $status->actions),
            'current_min' => $status->currentMin,
            'today' => $status->today,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function lastEventToArray(EventDetails $event): array
    {
        return [
            'event_type_id' => $event->eventTypeId,
            'name' => $event->name,
            'occurred_at' => $event->occurredAt->format('Y-m-d H:i:s'),
            'volume' => $event->volume,
            'description' => $event->description,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function actionToArray(Action $action): array
    {
        $result = [
            'event_type_id' => $action->eventTypeId,
            'focus' => $action->focus,
            'show_in_quick_actions' => $action->showInQuickActions,
        ];

        // volumes есть только у типов с объёмом — у остальных ключа быть не должно.
        if (null !== $action->volumes) {
            $result['volumes'] = $action->volumes;
        }

        return $result;
    }
}
