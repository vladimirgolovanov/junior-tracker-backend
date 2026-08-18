<?php

declare(strict_types=1);

namespace App\Domain\ChildInvite\ValueObject;

use App\Domain\ChildInvite\Enum\ChildInviteStatus;

/**
 * A whole invite row, for showing the history of who was invited and by whom.
 * Deliberately separate from ChildInvite, which carries only what redeeming a
 * code needs.
 */
final readonly class ChildInviteSummary
{
    public function __construct(
        public int $id,
        public string $code,
        public int $inviterId,
        public ?int $acceptedById,
        public \DateTimeImmutable $createdAt,
        public ?\DateTimeImmutable $acceptedAt,
        public \DateTimeImmutable $expiresAt,
    ) {
    }

    /**
     * Same precedence as ChildInvite::ensureAcceptable(): an accepted invite
     * stays accepted even once its expiry has passed.
     */
    public function status(\DateTimeImmutable $now): ChildInviteStatus
    {
        return match (true) {
            null !== $this->acceptedAt => ChildInviteStatus::Accepted,
            $now >= $this->expiresAt => ChildInviteStatus::Expired,
            default => ChildInviteStatus::Pending,
        };
    }
}
