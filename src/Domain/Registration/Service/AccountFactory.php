<?php

declare(strict_types=1);

namespace App\Domain\Registration\Service;

use App\Domain\Child\ValueObject\Timezone;
use App\Domain\Registration\ValueObject\NewAccount;
use App\Domain\Registration\ValueObject\NewChild;
use App\Domain\Registration\ValueObject\NewUser;
use App\Domain\User\Service\PasswordHasherInterface;
use App\Domain\User\ValueObject\Credentials;

/**
 * Главное правило онбординга: что именно получает новый пользователь.
 * Всё, что здесь решается, проверяется unit-тестом без базы.
 */
final readonly class AccountFactory
{
    private const CHILD_NAME = 'My Child';
    private const UTC = 'UTC';

    public function __construct(
        private PasswordHasherInterface $passwordHasher,
        private DefaultEventTypesFactory $defaultEventTypes,
    ) {
    }

    public function create(
        Credentials $credentials,
        Timezone $timezone,
        \DateTimeImmutable $now,
    ): NewAccount {
        return new NewAccount(
            user: $this->newUser($credentials, $now),
            child: new NewChild(
                name: self::CHILD_NAME,
                timezone: $timezone,
            ),
            existingChildId: null,
            isOwner: true,
            eventTypes: $this->defaultEventTypes->create(),
        );
    }

    /**
     * Присоединение к уже существующему ребёнку по приглашению:
     * нового ребёнка не создаём, типы событий не дублируем, владельцем не делаем.
     */
    public function createForExistingChild(
        Credentials $credentials,
        int $childId,
        \DateTimeImmutable $now,
    ): NewAccount {
        return new NewAccount(
            user: $this->newUser($credentials, $now),
            child: null,
            existingChildId: $childId,
            isOwner: false,
            eventTypes: [],
        );
    }

    private function newUser(Credentials $credentials, \DateTimeImmutable $now): NewUser
    {
        // users.created_at / updated_at — timestamp without time zone,
        // поэтому момент времени фиксируется в UTC.
        $registeredAt = $now->setTimezone(new \DateTimeZone(self::UTC));

        return new NewUser(
            email: $credentials->email,
            hashedPassword: $this->passwordHasher->hash($credentials->password),
            isActive: true,
            isSuperuser: false,
            isVerified: true,
            createdAt: $registeredAt,
            updatedAt: $registeredAt,
        );
    }
}
