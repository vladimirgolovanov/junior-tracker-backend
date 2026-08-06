<?php

declare(strict_types=1);

namespace App\Domain\Event\ValueObject;

/**
 * Частичное обновление события: null означает «не менять», как и у EventTypePatch.
 * child_id и event_type_id не меняются — перенос события в другой тип или другому
 * ребёнку это не правка, а удаление с созданием заново.
 */
final readonly class EventPatch
{
    public function __construct(
        public ?\DateTimeImmutable $occurredAt = null,
        public ?int $volume = null,
        public ?string $description = null,
    ) {
    }

    public function isEmpty(): bool
    {
        return null === $this->occurredAt
            && null === $this->volume
            && null === $this->description;
    }
}
