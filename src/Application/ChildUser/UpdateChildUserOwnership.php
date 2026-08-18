<?php

declare(strict_types=1);

namespace App\Application\ChildUser;

use App\Domain\Auth\Exception\AccessDenied;
use App\Domain\Child\Repository\ChildAccessRepositoryInterface;
use App\Domain\ChildUser\Exception\ChildUserNotFound;
use App\Domain\ChildUser\Exception\LastOwnerProtected;
use App\Domain\ChildUser\Repository\ChildUserRepositoryInterface;
use App\Domain\ChildUser\ValueObject\ChildUser;

final readonly class UpdateChildUserOwnership
{
    public function __construct(
        private ChildUserRepositoryInterface $childUsers,
        private ChildAccessRepositoryInterface $childAccess,
    ) {
    }

    /**
     * @throws AccessDenied
     * @throws ChildUserNotFound
     * @throws LastOwnerProtected
     */
    public function __invoke(int $actorId, int $childId, int $targetUserId, bool $isOwner): ChildUser
    {
        if (!$this->childAccess->userIsOwnerOfChild($actorId, $childId)) {
            throw AccessDenied::notOwnerOfChild($childId);
        }

        $member = $this->childUsers->find($childId, $targetUserId);

        if (null === $member) {
            throw ChildUserNotFound::create($childId, $targetUserId);
        }

        // Membership is confirmed, so the only way the write can be refused is
        // the guard on the last owner.
        if (!$this->childUsers->updateOwnership($childId, $targetUserId, $isOwner)) {
            throw LastOwnerProtected::create($childId);
        }

        return $member->withOwnership($isOwner);
    }
}
