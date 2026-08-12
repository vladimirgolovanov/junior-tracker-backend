<?php

declare(strict_types=1);

namespace App\Domain\Status\ValueObject;

use App\Domain\Event\ValueObject\EventDetails;

final readonly class ChildStatus
{
    /**
     * @param list<EventDetails>  $lastEvents  от свежих к старым
     * @param list<Action> $actions
     */
    public function __construct(
        public int $childId,
        public CurrentSleepState $sleep,
        public array $lastEvents,
        public array $actions,
        public int $currentMin,
        public string $today,
    ) {
    }
}
