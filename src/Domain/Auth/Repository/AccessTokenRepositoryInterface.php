<?php

declare(strict_types=1);

namespace App\Domain\Auth\Repository;

use App\Domain\Auth\ValueObject\AccessToken;

interface AccessTokenRepositoryInterface
{
    public function save(AccessToken $token): void;

    /**
     * Возвращает id пользователя по действующему токену либо null,
     * если токена нет или он старше $notOlderThan.
     */
    public function findUserId(string $token, \DateTimeImmutable $notOlderThan): ?int;
}
