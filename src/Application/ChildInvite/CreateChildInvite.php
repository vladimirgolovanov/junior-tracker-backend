<?php

declare(strict_types=1);

namespace App\Application\ChildInvite;

use App\Domain\ChildInvite\Repository\ChildInviteRepositoryInterface;
use App\Domain\ChildInvite\Service\ChildInviteFactory;
use App\Domain\ChildInvite\ValueObject\CreatedChildInvite;

final readonly class CreateChildInvite
{
    public function __construct(
        private ChildInviteFactory $childInviteFactory,
        private ChildInviteRepositoryInterface $childInviteRepository,
    ) {
    }

    public function handle(int $childId, int $inviterId, \DateTimeImmutable $now): CreatedChildInvite
    {
        $invite = $this->childInviteFactory->create($inviterId, $childId, $now);

        $this->childInviteRepository->create($invite);

        return new CreatedChildInvite($invite->code, $invite->expiresAt);
    }
}
