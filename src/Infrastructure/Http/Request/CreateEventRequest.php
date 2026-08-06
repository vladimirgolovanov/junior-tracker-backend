<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Request;

use App\Domain\Event\ValueObject\EventDraft;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Тело POST /api/v2/events.
 * occurred_at необязателен: кнопка быстрого действия отправляет событие «сейчас».
 */
final class CreateEventRequest
{
    public function __construct(
        #[Assert\NotNull(message: 'Field "child_id" is required.')]
        #[Assert\Positive(message: 'Field "child_id" must be a positive integer.')]
        public readonly ?int $child_id = null,

        #[Assert\NotNull(message: 'Field "event_type_id" is required.')]
        #[Assert\Positive(message: 'Field "event_type_id" must be a positive integer.')]
        public readonly ?int $event_type_id = null,

        public readonly ?string $occurred_at = null,

        #[Assert\PositiveOrZero(message: 'Field "volume" must not be negative.')]
        public readonly ?int $volume = null,

        public readonly ?string $description = null,
    ) {
    }

    public function toDraft(\DateTimeImmutable $now): EventDraft
    {
        return new EventDraft(
            childId: (int) $this->child_id,
            eventTypeId: (int) $this->event_type_id,
            occurredAt: null === $this->occurred_at
                ? $now
                : DateTimeParser::parse($this->occurred_at, 'occurred_at'),
            volume: $this->volume,
            description: $this->description,
        );
    }
}
