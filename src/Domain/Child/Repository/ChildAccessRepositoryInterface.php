<?php

declare(strict_types=1);

namespace App\Domain\Child\Repository;

interface ChildAccessRepositoryInterface
{
    public function userHasAccessToChild(int $userId, int $childId): bool;
}
