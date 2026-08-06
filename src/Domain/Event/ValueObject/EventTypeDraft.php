<?php

declare(strict_types=1);

namespace App\Domain\Event\ValueObject;

/**
 * Данные для создания типа события через публичный API.
 * Отдельно от Registration\ValueObject\NewEventType: тот описывает набор типов
 * по умолчанию при регистрации и не знает ни про childId, ни про пользовательские настройки.
 */
final readonly class EventTypeDraft
{
    /**
     * @param string[]|null $keywords
     */
    public function __construct(
        public int $childId,
        public string $name,
        public string $format,
        public ?string $color = null,
        public ?array $keywords = null,
        public bool $showInLastEvents = true,
        public bool $showInQuickActions = true,
    ) {
    }

    /**
     * Парный тип-окончание для format="range": имя по правилу RangeEndNameResolver,
     * ключевые слова не наследуются (закрывающее событие ловится по имени старта),
     * остальное — от родителя.
     */
    public function pairedEnd(string $name): self
    {
        return new self(
            childId: $this->childId,
            name: $name,
            format: 'range_end',
            color: $this->color,
            keywords: null,
            showInLastEvents: $this->showInLastEvents,
            showInQuickActions: $this->showInQuickActions,
        );
    }
}
