<?php

declare(strict_types=1);

namespace App\Domain\Event\Exception;

final class EventTypeNotFound extends \RuntimeException
{
    public static function rangeType(int $childId, string $startName): self
    {
        return new self(sprintf(
            'Range event type "%s" (with its paired end type) not found for child %d.',
            $startName,
            $childId,
        ));
    }

    public static function byId(int $id): self
    {
        return new self(sprintf('Event type %d not found.', $id));
    }

    public static function inChild(int $childId, int $eventTypeId): self
    {
        return new self(sprintf('Event type %d does not belong to child %d.', $eventTypeId, $childId));
    }

    public static function plainType(int $childId, string $name): self
    {
        return new self(sprintf(
            'Plain event type "%s" not found for child %d.',
            $name,
            $childId,
        ));
    }
}
