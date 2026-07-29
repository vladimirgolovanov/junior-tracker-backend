<?php

declare(strict_types=1);

namespace App\Domain\Registration\ValueObject;

/**
 * Полное описание аккаунта, который получает новый пользователь.
 * Собирается доменом; хранилище только раскладывает это по таблицам.
 *
 * Ребёнок задаётся одним из двух способов: либо создаётся новый ($child),
 * либо пользователь присоединяется к существующему ($existingChildId).
 */
final readonly class NewAccount
{
    /**
     * @param NewEventType[] $eventTypes
     * @param bool           $isOwner    владеет ли пользователь этим ребёнком
     */
    public function __construct(
        public NewUser $user,
        public ?NewChild $child,
        public ?int $existingChildId,
        public bool $isOwner,
        public array $eventTypes,
    ) {
    }
}
