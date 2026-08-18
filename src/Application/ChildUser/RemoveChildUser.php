<?php

declare(strict_types=1);

namespace App\Application\ChildUser;

use App\Domain\Auth\Exception\AccessDenied;
use App\Domain\Child\Repository\ChildAccessRepositoryInterface;
use App\Domain\ChildUser\Exception\ChildUserNotFound;
use App\Domain\ChildUser\Exception\LastOwnerProtected;
use App\Domain\ChildUser\Repository\ChildUserRepositoryInterface;

/**
 * Removing a member only cuts the child_users link. The account itself stays:
 * dropping someone from a child must not delete a person's account behind their
 * back — that is what DELETE /api/v2/account is for.
 */
final readonly class RemoveChildUser
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
    public function __invoke(int $actorId, int $childId, int $targetUserId): void
    {
        // Leaving on your own needs no ownership: anyone may walk away from a
        // child. Removing somebody else is an owner-only action.
        if ($actorId !== $targetUserId && !$this->childAccess->userIsOwnerOfChild($actorId, $childId)) {
            throw AccessDenied::notOwnerOfChild($childId);
        }

        if (null === $this->childUsers->find($childId, $targetUserId)) {
            throw ChildUserNotFound::create($childId, $targetUserId);
        }

        if (!$this->childUsers->remove($childId, $targetUserId)) {
            throw LastOwnerProtected::create($childId);
        }
    }
}
