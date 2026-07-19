<?php

declare(strict_types=1);

namespace App\Domain\Child\Repository;

interface ChildRepositoryInterface
{
    public function findTimezone(int $childId): \DateTimeZone;
}
