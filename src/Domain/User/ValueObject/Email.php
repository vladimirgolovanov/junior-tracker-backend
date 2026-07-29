<?php

declare(strict_types=1);

namespace App\Domain\User\ValueObject;

use App\Domain\Shared\Exception\InvalidValue;

final readonly class Email
{
    public const MAX_LENGTH = 320;

    public string $value;

    public function __construct(string $value)
    {
        $value = trim($value);

        if (mb_strlen($value) > self::MAX_LENGTH) {
            throw InvalidValue::emailTooLong(self::MAX_LENGTH);
        }

        if (false === filter_var($value, \FILTER_VALIDATE_EMAIL)) {
            throw InvalidValue::malformedEmail();
        }

        $this->value = $value;
    }

    /**
     * FastAPI ищет пользователя по lower(email), поэтому занятость адреса
     * проверяется без учёта регистра.
     */
    public function lowercased(): string
    {
        return mb_strtolower($this->value);
    }
}
