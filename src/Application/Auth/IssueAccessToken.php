<?php

declare(strict_types=1);

namespace App\Application\Auth;

use App\Domain\Auth\Repository\AccessTokenRepositoryInterface;
use App\Domain\Auth\Service\TokenValueGeneratorInterface;
use App\Domain\Auth\ValueObject\AccessToken;

final readonly class IssueAccessToken
{
    private const UTC = 'UTC';

    public function __construct(
        private TokenValueGeneratorInterface $tokenValueGenerator,
        private AccessTokenRepositoryInterface $accessTokenRepository,
    ) {
    }

    public function __invoke(int $userId, \DateTimeImmutable $now): AccessToken
    {
        $token = new AccessToken(
            value: $this->tokenValueGenerator->generate(),
            userId: $userId,
            createdAt: $now->setTimezone(new \DateTimeZone(self::UTC)),
        );

        $this->accessTokenRepository->save($token);

        return $token;
    }
}
