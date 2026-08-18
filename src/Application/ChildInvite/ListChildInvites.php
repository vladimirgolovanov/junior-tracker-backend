<?php

declare(strict_types=1);

namespace App\Application\ChildInvite;

use App\Domain\Auth\Exception\AccessDenied;
use App\Domain\Child\Repository\ChildAccessRepositoryInterface;
use App\Domain\ChildInvite\Repository\ChildInviteRepositoryInterface;
use App\Domain\ChildInvite\ValueObject\ChildInviteSummary;

final readonly class ListChildInvites
{
    public function __construct(
        private ChildInviteRepositoryInterface $childInviteRepository,
        private ChildAccessRepositoryInterface $childAccess,
    ) {
    }

    /**
     * @return ChildInviteSummary[]
     *
     * @throws AccessDenied
     */
    public function __invoke(int $userId, int $childId): array
    {
        if (!$this->childAccess->userIsOwnerOfChild($userId, $childId)) {
            throw AccessDenied::notOwnerOfChild($childId);
        }

        return $this->childInviteRepository->findAllByChild($childId);
    }
}
