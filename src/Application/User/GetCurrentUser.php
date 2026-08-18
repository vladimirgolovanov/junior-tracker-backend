<?php

declare(strict_types=1);

namespace App\Application\User;

use App\Domain\Auth\Exception\Unauthenticated;
use App\Domain\User\Repository\CurrentUserRepositoryInterface;
use App\Domain\User\ValueObject\CurrentUser;

final readonly class GetCurrentUser
{
    public function __construct(
        private CurrentUserRepositoryInterface $users,
    ) {
    }

    /**
     * @throws Unauthenticated
     */
    public function __invoke(int $userId): CurrentUser
    {
        $user = $this->users->find($userId);

        if (null === $user) {
            // access_tokens cascades on user deletion, so a valid token should
            // never outlive its user. If it somehow does, the token is stale.
            throw Unauthenticated::invalidToken();
        }

        return $user;
    }
}
