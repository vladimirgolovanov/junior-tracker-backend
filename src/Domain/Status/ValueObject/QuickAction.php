<?php

declare(strict_types=1);

namespace App\Domain\Status\ValueObject;

/**
 * Кнопка быстрого действия. Подпись и стиль фронт берёт из самого типа события,
 * поэтому здесь только то, чего в типе нет: какую половину пары писать,
 * какое поле автофокусить и что подсказать в поле объёма.
 */
final readonly class QuickAction
{
    public const FOCUS_VOLUME = 'volume';
    public const FOCUS_DESCRIPTION = 'description';

    /**
     * @param list<int>|null $volumes
     */
    public function __construct(
        public int $eventTypeId,
        public ?string $focus,
        public ?array $volumes = null,
    ) {
    }

    /**
     * @param list<int> $volumes
     */
    public function withVolumes(array $volumes): self
    {
        return new self($this->eventTypeId, $this->focus, $volumes);
    }
}
