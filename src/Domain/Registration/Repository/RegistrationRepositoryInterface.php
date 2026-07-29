<?php

declare(strict_types=1);

namespace App\Domain\Registration\Repository;

use App\Domain\Registration\Exception\EmailAlreadyRegistered;
use App\Domain\Registration\ValueObject\NewAccount;
use App\Domain\Registration\ValueObject\RegisteredUser;
use App\Domain\User\ValueObject\Email;

interface RegistrationRepositoryInterface
{
    public function emailExists(Email $email): bool;

    /**
     * Сохраняет аккаунт целиком — пользователя, его первого ребёнка,
     * связь между ними и типы событий — атомарно.
     *
     * @throws EmailAlreadyRegistered если адрес заняли между проверкой и вставкой
     */
    public function save(NewAccount $account): RegisteredUser;
}
