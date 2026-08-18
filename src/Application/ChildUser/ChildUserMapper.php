<?php

declare(strict_types=1);

namespace App\Application\ChildUser;

use App\Domain\ChildUser\ValueObject\ChildUser;

final readonly class ChildUserMapper
{
    /**
     * @param ChildUser[] $childUsers
     *
     * @return array<int, array<string, mixed>>
     */
    public function toArray(array $childUsers): array
    {
        return array_map($this->one(...), $childUsers);
    }

    /**
     * @return array<string, mixed>
     */
    public function one(ChildUser $childUser): array
    {
        return [
            'user_id' => $childUser->userId,
            'email' => $childUser->email,
            'is_owner' => $childUser->isOwner,
        ];
    }
}
