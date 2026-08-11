<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Request;

use App\Domain\Event\ValueObject\EventRangeDraft;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateRangeEventRequest
{
    public function __construct(
        #[Assert\NotNull(message: 'Field "child_id" is required.')]
        #[Assert\Positive(message: 'Field "child_id" must be a positive integer.')]
        public ?int $child_id = null,

        #[Assert\NotNull(message: 'Field "event_type_id" is required.')]
        #[Assert\Positive(message: 'Field "event_type_id" must be a positive integer.')]
        public ?int $event_type_id = null,

        public ?string $occurred_at = null,

        #[Assert\Positive(message: 'Field "parent_id" must be a positive integer.')]
        public ?int $range_length = null,
    ) {
    }

    public function toDraft(): EventRangeDraft
    {
        return new EventRangeDraft(
            childId: (int) $this->child_id,
            eventTypeId: (int) $this->event_type_id,
            occurredAt: DateTimeParser::parse($this->occurred_at, 'occurred_at'),
            rangeLength: (int) $this->range_length,
        );
    }
}
