<?php

declare(strict_types=1);

namespace App\Domain\Child\Repository;

interface ChildAccessRepositoryInterface
{
    public function userHasAccessToChild(int $userId, int $childId): bool;

    /**
     * Stricter than access: managing who can see a child is owner-only.
     */
    public function userIsOwnerOfChild(int $userId, int $childId): bool;
}
