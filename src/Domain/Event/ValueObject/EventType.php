<?php

declare(strict_types=1);

namespace App\Domain\Event\ValueObject;

final readonly class EventType
{
    /**
     * @param string[]|null $keywords
     * @param bool          $showInLastEvents   пользовательская настройка: показывать ли
     *                                          последнее событие этого типа в ленте статуса
     * @param bool          $showInQuickActions пользовательская настройка: показывать ли кнопку
     *                                          быстрого действия; у range-пары решает start-тип
     */
    public function __construct(
        public int $id,
        public int $childId,
        public string $name,
        public ?string $format,
        public ?string $color,
        public ?int $parentId,
        public ?array $keywords,
        public bool $showInLastEvents,
        public bool $showInQuickActions,
    ) {
    }

    public function isRangeStart(): bool
    {
        return 'range' === $this->format;
    }
}
