<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Feedback\Repository\FeedbackRepositoryInterface;
use App\Domain\Feedback\ValueObject\Feedback;
use Doctrine\DBAL\Connection;

final readonly class DoctrineFeedbackRepository implements FeedbackRepositoryInterface
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function create(int $userId, string $text): Feedback
    {
        // created_at is filled by the DEFAULT NOW(), so we read it back to return
        // the exact stored value instead of guessing it here.
        $row = $this->connection->fetchAssociative(
            'INSERT INTO feedback (user_id, text)
             VALUES (:userId, :text)
             RETURNING id, user_id, text, created_at',
            [
                'userId' => $userId,
                'text' => $text,
            ],
        );

        // The row was just inserted in the same statement, so it must be there.
        \assert(false !== $row);

        return $this->hydrate($row);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrate(array $row): Feedback
    {
        return new Feedback(
            id: (int) $row['id'],
            userId: (int) $row['user_id'],
            text: (string) $row['text'],
            createdAt: new \DateTimeImmutable($row['created_at']),
        );
    }
}
