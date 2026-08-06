<?php

declare(strict_types=1);

namespace App\Domain\Event\ValueObject;

/**
 * Частичное обновление типа события.
 * null означает «не менять» — семантика перенесена из FastAPI, где update-схема
 * применяется с exclude_none=True. Обнулить color или keywords через PATCH нельзя.
 * format не меняется: он определяет и разбор события, и парность range-типов.
 */
final readonly class EventTypePatch
{
    /**
     * @param string[]|null $keywords
     */
    public function __construct(
        public ?string $name = null,
        public ?string $color = null,
        public ?array $keywords = null,
        public ?bool $showInLastEvents = null,
        public ?bool $showInQuickActions = null,
    ) {
    }

    public function isEmpty(): bool
    {
        return null === $this->name
            && null === $this->color
            && null === $this->keywords
            && null === $this->showInLastEvents
            && null === $this->showInQuickActions;
    }
}
