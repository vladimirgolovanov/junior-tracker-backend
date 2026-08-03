<?php

declare(strict_types=1);

namespace App\Domain\Event\ValueObject;

final readonly class EventType
{
    /**
     * @param string[]|null $keywords
     */
    public function __construct(
        public int $id,
        public int $childId,
        public string $name,
        public ?string $format,
        public ?string $color,
        public ?int $parentId,
        public ?array $keywords,
    ) {
    }
}
