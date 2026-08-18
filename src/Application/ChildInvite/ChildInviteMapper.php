<?php

declare(strict_types=1);

namespace App\Application\ChildInvite;

use App\Domain\ChildInvite\ValueObject\ChildInviteSummary;

final readonly class ChildInviteMapper
{
    /**
     * Status is derived rather than stored, so it needs the current moment —
     * the same one the request is being served at.
     *
     * @param ChildInviteSummary[] $invites
     *
     * @return array<int, array<string, mixed>>
     */
    public function toArray(array $invites, \DateTimeImmutable $now): array
    {
        return array_map(fn (ChildInviteSummary $invite): array => $this->one($invite, $now), $invites);
    }

    /**
     * @return array<string, mixed>
     */
    public function one(ChildInviteSummary $invite, \DateTimeImmutable $now): array
    {
        return [
            'id' => $invite->id,
            'code' => $invite->code,
            'status' => $invite->status($now)->value,
            'inviter_id' => $invite->inviterId,
            'accepted_by_id' => $invite->acceptedById,
            // ATOM matches what POST /invites already returns for expires_at.
            'created_at' => $invite->createdAt->format(\DateTimeInterface::ATOM),
            'accepted_at' => $invite->acceptedAt?->format(\DateTimeInterface::ATOM),
            'expires_at' => $invite->expiresAt->format(\DateTimeInterface::ATOM),
        ];
    }
}
