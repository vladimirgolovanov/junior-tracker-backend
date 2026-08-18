<?php

declare(strict_types=1);

namespace App\Domain\User\ValueObject;

/**
 * The user's role is not a property of the user: it is held per child in
 * child_users.is_owner. The same person can own one child and merely be a
 * member of another.
 */
final readonly class ChildMembership
{
    public function __construct(
        public int $childId,
        public bool $isOwner,
    ) {
    }
}
