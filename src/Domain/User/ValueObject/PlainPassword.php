<?php

declare(strict_types=1);

namespace App\Domain\User\ValueObject;

use App\Domain\Shared\Exception\InvalidValue;

final readonly class PlainPassword
{
    public const MIN_LENGTH = 8;
    public const MAX_LENGTH = 128;

    public function __construct(
        public string $value,
    ) {
        $length = mb_strlen($value);

        if ($length < self::MIN_LENGTH) {
            throw InvalidValue::passwordTooShort(self::MIN_LENGTH);
        }

        if ($length > self::MAX_LENGTH) {
            throw InvalidValue::passwordTooLong(self::MAX_LENGTH);
        }
    }
}
