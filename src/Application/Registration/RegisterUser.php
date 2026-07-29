<?php

declare(strict_types=1);

namespace App\Application\Registration;

use App\Domain\Child\ValueObject\Timezone;
use App\Domain\ChildInvite\Exception\InviteAlreadyAccepted;
use App\Domain\ChildInvite\Exception\InviteExpired;
use App\Domain\ChildInvite\Exception\InviteNotFound;
use App\Domain\ChildInvite\Repository\ChildInviteRepositoryInterface;
use App\Domain\Registration\Exception\EmailAlreadyRegistered;
use App\Domain\Registration\Repository\RegistrationRepositoryInterface;
use App\Domain\Registration\Service\AccountFactory;
use App\Domain\Registration\ValueObject\RegisteredUser;
use App\Domain\User\ValueObject\Credentials;

final readonly class RegisterUser
{
    public function __construct(
        private RegistrationRepositoryInterface $registrationRepository,
        private AccountFactory $accountFactory,
        private ChildInviteRepositoryInterface $childInviteRepository,
    ) {
    }

    /**
     * Обычная регистрация: заводим нового ребёнка, пользователь — его владелец.
     *
     * @throws EmailAlreadyRegistered
     */
    public function register(
        Credentials $credentials,
        Timezone $timezone,
        \DateTimeImmutable $now,
    ): RegisteredUser {
        $this->ensureEmailAvailable($credentials);

        return $this->registrationRepository->save(
            $this->accountFactory->create($credentials, $timezone, $now),
        );
    }

    /**
     * Регистрация по приглашению: присоединяемся к существующему ребёнку.
     * Таймзона не нужна — ребёнок уже создан со своей.
     *
     * @throws EmailAlreadyRegistered
     * @throws InviteNotFound
     * @throws InviteExpired
     * @throws InviteAlreadyAccepted
     */
    public function joinChildByInvite(
        Credentials $credentials,
        string $inviteCode,
        \DateTimeImmutable $now,
    ): RegisteredUser {
        $this->ensureEmailAvailable($credentials);

        $invite = $this->childInviteRepository->findByCode($inviteCode);
        $invite->ensureAcceptable($now);

        $registered = $this->registrationRepository->save(
            $this->accountFactory->createForExistingChild($credentials, $invite->childId, $now),
        );

        $this->childInviteRepository->markAccepted($invite->id, $registered->userId, $now);

        return $registered;
    }

    /**
     * @throws EmailAlreadyRegistered
     */
    private function ensureEmailAvailable(Credentials $credentials): void
    {
        if ($this->registrationRepository->emailExists($credentials->email)) {
            throw EmailAlreadyRegistered::create();
        }
    }
}
