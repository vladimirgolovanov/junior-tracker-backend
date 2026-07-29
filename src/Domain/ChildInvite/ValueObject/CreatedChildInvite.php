<?php

declare(strict_types=1);

namespace App\Domain\ChildInvite\ValueObject;

/**
 * Результат создания приглашения: код для ссылки и срок его действия.
 */
final readonly class CreatedChildInvite
{
    public function __construct(
        public string $code,
        public \DateTimeImmutable $expiresAt,
    ) {
    }
}
