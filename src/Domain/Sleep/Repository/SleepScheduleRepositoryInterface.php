<?php

declare(strict_types=1);

namespace App\Domain\Sleep\Repository;

use App\Domain\Sleep\ValueObject\SleepSchedule;

interface SleepScheduleRepositoryInterface
{
    /**
     * Returns the child's day/night window, falling back to defaults when it is not configured.
     */
    public function findForChild(int $childId): SleepSchedule;
}
