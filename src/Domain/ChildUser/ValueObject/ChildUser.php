<?php

declare(strict_types=1);

namespace App\Domain\ChildUser\ValueObject;

/**
 * A row of child_users joined with the user it points at. Email is the only
 * human-readable thing the users table carries — there is no name column.
 */
final readonly class ChildUser
{
    public function __construct(
        public int $userId,
        public string $email,
        public bool $isOwner,
    ) {
    }

    public function withOwnership(bool $isOwner): self
    {
        return new self($this->userId, $this->email, $isOwner);
    }
}
