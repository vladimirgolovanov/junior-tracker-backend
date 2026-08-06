<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Request;

use App\Domain\Event\ValueObject\EventPatch;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Тело PATCH /api/v2/events/{id}.
 * null означает «не менять» — та же семантика, что у UpdateEventTypeRequest.
 * child_id и event_type_id не меняются.
 */
final class UpdateEventRequest
{
    public function __construct(
        public readonly ?string $occurred_at = null,

        #[Assert\PositiveOrZero(message: 'Field "volume" must not be negative.')]
        public readonly ?int $volume = null,

        public readonly ?string $description = null,
    ) {
    }

    public function toPatch(): EventPatch
    {
        return new EventPatch(
            occurredAt: null === $this->occurred_at
                ? null
                : DateTimeParser::parse($this->occurred_at, 'occurred_at'),
            volume: $this->volume,
            description: $this->description,
        );
    }
}
