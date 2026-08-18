<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Domain\User\Service\PasswordHasherInterface;
use App\Domain\User\ValueObject\PlainPassword;

/**
 * Хеш проверяет FastAPI (fastapi-users поверх pwdlib), а не этот сервис,
 * поэтому параметры обязаны совпадать с дефолтами argon2-cffi:
 * $argon2id$v=19$m=65536,t=3,p=4$...
 */
final readonly class Argon2PasswordHasher implements PasswordHasherInterface
{
    private const MEMORY_COST = 65536;
    private const TIME_COST = 3;
    private const THREADS = 4;

    public function hash(PlainPassword $password): string
    {
        return password_hash($password->value, \PASSWORD_ARGON2ID, [
            'memory_cost' => self::MEMORY_COST,
            'time_cost' => self::TIME_COST,
            'threads' => self::THREADS,
        ]);
    }

    /**
     * Mirror image of hash(): password_verify() reads the cost parameters from
     * the PHC string itself, so hashes written by pwdlib verify here as well.
     */
    public function verify(string $plainPassword, string $hashedPassword): bool
    {
        return password_verify($plainPassword, $hashedPassword);
    }
}
