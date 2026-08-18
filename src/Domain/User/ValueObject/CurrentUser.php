<?php

declare(strict_types=1);

namespace App\Domain\User\ValueObject;

final readonly class CurrentUser
{
    /**
     * @param ChildMembership[] $memberships
     */
    public function __construct(
        public int $id,
        public Email $email,
        public array $memberships,
    ) {
    }
}
