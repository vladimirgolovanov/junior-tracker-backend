<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Sleep\Repository\SleepPredictRepositoryInterface;
use App\Domain\Sleep\ValueObject\SleepPrediction;
use Doctrine\DBAL\Connection;

final readonly class DoctrineSleepPredictRepository implements SleepPredictRepositoryInterface
{
    private const UTC = 'UTC';

    public function __construct(
        private Connection $connection,
    ) {
    }

    public function findByChildAndOccurredAt(
        int $childId,
        \DateTimeImmutable $occurredAt,
        \DateTimeZone $timezone,
    ): array {
        $utc = new \DateTimeZone(self::UTC);

        // (child_id, occurred_at) is not unique: the predictor may write the same event
        // more than once, and the freshest row wins.
        $data = $this->connection->fetchOne(
            'SELECT data
             FROM sleep_predicts
             WHERE child_id = :childId
               AND occurred_at = :occurredAt
             ORDER BY id DESC
             LIMIT 1',
            [
                'childId' => $childId,
                'occurredAt' => $occurredAt->setTimezone($utc)->format('Y-m-d H:i:sP'),
            ],
        );

        if (false === $data) {
            return [];
        }

        return array_map(
            static fn (array $segment): SleepPrediction => new SleepPrediction(
                // The predictor stores naive UTC strings ("2026-07-30 09:05:00").
                startAt: (new \DateTimeImmutable($segment['start_dt'], $utc))->setTimezone($timezone),
                endAt: (new \DateTimeImmutable($segment['end_dt'], $utc))->setTimezone($timezone),
                minutes: (int) $segment['time'],
                segmentType: (string) $segment['segment_type'],
            ),
            $this->segments($data),
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function segments(string $data): array
    {
        $decoded = json_decode($data, true);

        if (!is_array($decoded) || !is_array($decoded['predictions'] ?? null)) {
            // A failed prediction is stored as {"error": "..."} instead of
            // {"predictions": [...]} and reads here the same way as a missing row.
            return [];
        }

        return $decoded['predictions'];
    }
}
