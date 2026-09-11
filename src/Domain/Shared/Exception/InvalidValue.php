<?php

declare(strict_types=1);

namespace App\Domain\Shared\Exception;

final class InvalidValue extends \DomainException
{
    private function __construct(
        public readonly string $field,
        string $message,
    ) {
        parent::__construct($message);
    }

    public static function missingField(string $field): self
    {
        return new self($field, sprintf('Field "%s" is required.', $field));
    }

    public static function notAString(string $field): self
    {
        return new self($field, sprintf('Field "%s" must be a string.', $field));
    }

    public static function malformedEmail(): self
    {
        return new self('email', 'Email is not a valid address.');
    }

    public static function emailTooLong(int $maxLength): self
    {
        return new self('email', sprintf('Email must not exceed %d characters.', $maxLength));
    }

    public static function passwordTooShort(int $minLength): self
    {
        return new self('password', sprintf('Password must be at least %d characters long.', $minLength));
    }

    public static function passwordTooLong(int $maxLength): self
    {
        return new self('password', sprintf('Password must not exceed %d characters.', $maxLength));
    }

    public static function passwordEqualsEmail(): self
    {
        return new self('password', 'Password must not be equal to the email.');
    }

    public static function malformedDateTime(string $field): self
    {
        return new self($field, sprintf(
            'Field "%s" must be an ISO 8601 datetime with an offset, e.g. "2026-08-03T11:40:00Z".',
            $field,
        ));
    }

    public static function unknownTimezone(): self
    {
        return new self('timezone', 'Timezone must be a valid IANA identifier, e.g. "Europe/Moscow".');
    }

    public static function dateRangeOutOfOrder(): self
    {
        return new self('from', 'Field "from" must not be later than "to".');
    }
}
