<?php

declare(strict_types=1);

namespace App\Application\Auth;

use App\Domain\Auth\Exception\Unauthenticated;
use App\Domain\Auth\Repository\AccessTokenRepositoryInterface;

final readonly class AuthenticateByToken
{
    public function __construct(
        private AccessTokenRepositoryInterface $accessTokenRepository,
        private int $ttlSeconds,
    ) {
    }

    /**
     * @return int id аутентифицированного пользователя
     *
     * @throws Unauthenticated токен неизвестен или старше TTL
     */
    public function __invoke(string $token, \DateTimeImmutable $now): int
    {
        $notOlderThan = $now->sub(new \DateInterval(sprintf('PT%dS', $this->ttlSeconds)));

        $userId = $this->accessTokenRepository->findUserId($token, $notOlderThan);

        if (null === $userId) {
            throw Unauthenticated::invalidToken();
        }

        return $userId;
    }
}
