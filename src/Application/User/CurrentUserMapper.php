<?php

declare(strict_types=1);

namespace App\Application\User;

use App\Domain\User\ValueObject\ChildMembership;
use App\Domain\User\ValueObject\CurrentUser;

final readonly class CurrentUserMapper
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(CurrentUser $user): array
    {
        return [
            'id' => $user->id,
            'email' => $user->email->value,
            'children' => array_map($this->membership(...), $user->memberships),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function membership(ChildMembership $membership): array
    {
        return [
            'child_id' => $membership->childId,
            'is_owner' => $membership->isOwner,
        ];
    }
}
