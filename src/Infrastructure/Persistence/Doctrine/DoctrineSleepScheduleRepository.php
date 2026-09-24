<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Sleep\Repository\SleepScheduleRepositoryInterface;
use App\Domain\Sleep\ValueObject\SleepSchedule;
use Doctrine\DBAL\Connection;

final readonly class DoctrineSleepScheduleRepository implements SleepScheduleRepositoryInterface
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function findForChild(int $childId): SleepSchedule
    {
        // to_char normalizes the `time` columns (returned as "06:00:00") to the "H:i" the VO expects,
        // and yields NULL for the (nullable, default-less) columns when the window is not configured.
        $row = $this->connection->fetchAssociative(
            "SELECT to_char(day_start, 'HH24:MI') AS day_start,
                    to_char(day_end,   'HH24:MI') AS day_end
             FROM childs
             WHERE id = :id",
            ['id' => $childId],
        );

        if (false === $row) {
            return SleepSchedule::fromNullableStrings(null, null);
        }

        return SleepSchedule::fromNullableStrings($row['day_start'], $row['day_end']);
    }
}
