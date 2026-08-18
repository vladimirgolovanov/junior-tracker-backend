<?php

declare(strict_types=1);

namespace App\Domain\ChildUser\Repository;

use App\Domain\ChildUser\ValueObject\ChildUser;

interface ChildUserRepositoryInterface
{
    /**
     * Owners first, then by user id — the owner is what the caller looks for.
     *
     * @return ChildUser[]
     */
    public function findAllByChild(int $childId): array;

    public function find(int $childId, int $userId): ?ChildUser;

    /**
     * @return bool false when the change would leave the child without an owner
     */
    public function updateOwnership(int $childId, int $userId, bool $isOwner): bool;

    /**
     * @return bool false when the removal would leave the child without an owner
     */
    public function remove(int $childId, int $userId): bool;
}
