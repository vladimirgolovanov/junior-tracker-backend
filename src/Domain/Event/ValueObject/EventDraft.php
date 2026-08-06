<?php

declare(strict_types=1);

namespace App\Domain\Event\ValueObject;

/**
 * Данные для создания события.
 * tg_message_id сюда не входит: это ссылка на сообщение телеграм-бота,
 * публичному API она не нужна.
 */
final readonly class EventDraft
{
    public function __construct(
        public int $childId,
        public int $eventTypeId,
        public \DateTimeImmutable $occurredAt,
        public ?int $volume = null,
        public ?string $description = null,
    ) {
    }
}
