<?php

declare(strict_types=1);

namespace App\Domain\Feedback\ValueObject;

/**
 * A row of the feedback table: one piece of feedback a user submitted.
 */
final readonly class Feedback
{
    public function __construct(
        public int $id,
        public int $userId,
        public string $text,
        public \DateTimeImmutable $createdAt,
    ) {
    }
}
