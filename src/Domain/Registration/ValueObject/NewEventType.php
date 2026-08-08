<?php

declare(strict_types=1);

namespace App\Domain\Registration\ValueObject;

final readonly class NewEventType
{
    /**
     * @param string[]|null $keywords
     * @param self|null     $end      парный тип-окончание для format="range";
     *                                сохраняется с parent_id этого типа
     */
    public function __construct(
        public string $name,
        public ?array $keywords,
        public string $format,
        public bool $showInLastEvents,
        public bool $showInQuickActions,
        public ?string $color = null,
        public ?self $end = null,
    ) {
    }
}
