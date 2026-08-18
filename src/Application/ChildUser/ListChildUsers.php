<?php

declare(strict_types=1);

namespace App\Application\ChildUser;

use App\Domain\Auth\Exception\AccessDenied;
use App\Domain\Child\Repository\ChildAccessRepositoryInterface;
use App\Domain\ChildUser\Repository\ChildUserRepositoryInterface;
use App\Domain\ChildUser\ValueObject\ChildUser;

final readonly class ListChildUsers
{
    public function __construct(
        private ChildUserRepositoryInterface $childUsers,
        private ChildAccessRepositoryInterface $childAccess,
    ) {
    }

    /**
     * @return ChildUser[]
     *
     * @throws AccessDenied
     */
    public function __invoke(int $userId, int $childId): array
    {
        if (!$this->childAccess->userIsOwnerOfChild($userId, $childId)) {
            throw AccessDenied::notOwnerOfChild($childId);
        }

        return $this->childUsers->findAllByChild($childId);
    }
}
