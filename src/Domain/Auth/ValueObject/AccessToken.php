<?php

declare(strict_types=1);

namespace App\Domain\Auth\ValueObject;

/**
 * Непрозрачный bearer-токен, который FastAPI (DatabaseStrategy) валидирует
 * поиском в таблице access_tokens. Колонка token — varchar(43).
 */
final readonly class AccessToken
{
    public const MAX_LENGTH = 43;

    public function __construct(
        public string $value,
        public int $userId,
        public \DateTimeImmutable $createdAt,
    ) {
        if ('' === $value || mb_strlen($value) > self::MAX_LENGTH) {
            throw new \InvalidArgumentException(
                sprintf('Access token value must be 1..%d characters long.', self::MAX_LENGTH),
            );
        }
    }
}
