<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Security;

use App\Domain\User\ValueObject\PlainPassword;
use App\Infrastructure\Security\Argon2PasswordHasher;
use PHPUnit\Framework\TestCase;

final class Argon2PasswordHasherTest extends TestCase
{
    /**
     * Пароли проверяет FastAPI: пока логин живёт там, параметры хеша обязаны
     * совпадать с дефолтами argon2-cffi, иначе зарегистрированный в Symfony
     * пользователь не сможет войти.
     */
    public function testProducesHashCompatibleWithFastApi(): void
    {
        $hash = (new Argon2PasswordHasher())->hash(new PlainPassword('correcthorse'));

        self::assertStringStartsWith('$argon2id$v=19$m=65536,t=3,p=4$', $hash);
        self::assertTrue(password_verify('correcthorse', $hash));
    }

    public function testSaltsEveryHash(): void
    {
        $hasher = new Argon2PasswordHasher();
        $password = new PlainPassword('correcthorse');

        self::assertNotSame($hasher->hash($password), $hasher->hash($password));
    }
}
