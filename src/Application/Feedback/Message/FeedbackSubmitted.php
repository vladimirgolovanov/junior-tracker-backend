<?php

declare(strict_types=1);

namespace App\Application\Feedback\Message;

/**
 * Event published to the feedback queue when a user submits feedback. It carries
 * the full payload (not just an id) because the queue is drained by a separate,
 * possibly non-PHP service that cannot read this database.
 */
final readonly class FeedbackSubmitted
{
    public function __construct(
        public int $id,
        public int $userId,
        public string $text,
        public \DateTimeImmutable $createdAt,
    ) {
    }
}
