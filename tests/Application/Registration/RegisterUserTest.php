<?php

declare(strict_types=1);

namespace App\Tests\Application\Registration;

use App\Application\Registration\RegisterUser;
use App\Domain\Child\ValueObject\Timezone;
use App\Domain\ChildInvite\Exception\InviteNotFound;
use App\Domain\ChildInvite\Repository\ChildInviteRepositoryInterface;
use App\Domain\ChildInvite\ValueObject\ChildInvite;
use App\Domain\ChildInvite\ValueObject\NewChildInvite;
use App\Domain\Registration\Exception\EmailAlreadyRegistered;
use App\Domain\Registration\Repository\RegistrationRepositoryInterface;
use App\Domain\Registration\Service\AccountFactory;
use App\Domain\Registration\Service\DefaultEventTypesFactory;
use App\Domain\Registration\ValueObject\NewAccount;
use App\Domain\Registration\ValueObject\RegisteredUser;
use App\Domain\User\Service\PasswordHasherInterface;
use App\Domain\User\ValueObject\Credentials;
use App\Domain\User\ValueObject\Email;
use App\Domain\User\ValueObject\PlainPassword;
use PHPUnit\Framework\TestCase;

/**
 * Сценарий регистрации; сами правила онбординга проверяет AccountFactoryTest.
 */
final class RegisterUserTest extends TestCase
{
    public function testSavesAccountBuiltForTheGivenCredentials(): void
    {
        $repository = $this->registrationRepository(emailExists: false);

        $registered = $this->registerUser($repository)->register(
            $this->credentials(),
            new Timezone('Europe/Moscow'),
            new \DateTimeImmutable('2026-07-23 10:00'),
        );

        self::assertSame(1, $registered->userId);
        self::assertSame(2, $registered->childId);

        self::assertSame('parent@example.com', $repository->saved?->user->email->value);
        self::assertSame('Europe/Moscow', $repository->saved?->child->timezone->value);
    }

    public function testRejectsTakenEmailBeforeTouchingTheDatabase(): void
    {
        $repository = $this->registrationRepository(emailExists: true);

        $this->expectException(EmailAlreadyRegistered::class);

        try {
            $this->registerUser($repository)->register(
                $this->credentials(),
                new Timezone('Europe/Moscow'),
                new \DateTimeImmutable('2026-07-23 10:00'),
            );
        } finally {
            self::assertNull($repository->saved);
        }
    }

    public function testJoinsExistingChildWhenInviteCodeGiven(): void
    {
        $repository = $this->registrationRepository(emailExists: false);
        $invites = $this->childInviteRepository(
            new ChildInvite(id: 7, childId: 42, acceptedAt: null, expiresAt: new \DateTimeImmutable('2026-07-30 10:00')),
        );

        $registered = $this->registerUser($repository, $invites)->joinChildByInvite(
            $this->credentials(),
            'invite-code',
            new \DateTimeImmutable('2026-07-23 10:00'),
        );

        // присоединяемся к существующему ребёнку, а не создаём нового
        self::assertNull($repository->saved?->child);
        self::assertSame(42, $repository->saved?->existingChildId);
        self::assertFalse($repository->saved?->isOwner);
        self::assertSame([], $repository->saved?->eventTypes);

        // приглашение помечено принятым именно новым пользователем
        self::assertSame(7, $invites->acceptedInviteId);
        self::assertSame($registered->userId, $invites->acceptedUserId);
    }

    public function testRejectsUnknownInviteCodeAndKeepsDatabaseUntouched(): void
    {
        $repository = $this->registrationRepository(emailExists: false);
        $invites = $this->childInviteRepository(findError: InviteNotFound::create());

        $this->expectException(InviteNotFound::class);

        try {
            $this->registerUser($repository, $invites)->joinChildByInvite(
                $this->credentials(),
                'nope',
                new \DateTimeImmutable('2026-07-23 10:00'),
            );
        } finally {
            self::assertNull($repository->saved);
            self::assertNull($invites->acceptedInviteId);
        }
    }

    public function testRejectsTakenEmailInInviteFlowBeforeLookingUpTheCode(): void
    {
        $repository = $this->registrationRepository(emailExists: true);
        $invites = $this->childInviteRepository(
            new ChildInvite(id: 7, childId: 42, acceptedAt: null, expiresAt: new \DateTimeImmutable('2026-07-30 10:00')),
        );

        $this->expectException(EmailAlreadyRegistered::class);

        try {
            $this->registerUser($repository, $invites)->joinChildByInvite(
                $this->credentials(),
                'invite-code',
                new \DateTimeImmutable('2026-07-23 10:00'),
            );
        } finally {
            self::assertFalse($invites->findByCodeCalled);
            self::assertNull($repository->saved);
        }
    }

    private function registerUser(
        RegistrationRepositoryInterface $repository,
        ?ChildInviteRepositoryInterface $inviteRepository = null,
    ): RegisterUser {
        return new RegisterUser(
            $repository,
            new AccountFactory($this->passwordHasher(), new DefaultEventTypesFactory()),
            $inviteRepository ?? $this->childInviteRepository(),
        );
    }

    private function credentials(): Credentials
    {
        return new Credentials(new Email('parent@example.com'), new PlainPassword('correcthorse'));
    }

    private function passwordHasher(): PasswordHasherInterface
    {
        return new class implements PasswordHasherInterface {
            public function hash(PlainPassword $password): string
            {
                return 'hashed:'.$password->value;
            }
        };
    }

    private function registrationRepository(bool $emailExists)
    {
        return new class($emailExists) implements RegistrationRepositoryInterface {
            public ?NewAccount $saved = null;

            public function __construct(
                private bool $emailExists,
            ) {
            }

            public function emailExists(Email $email): bool
            {
                return $this->emailExists;
            }

            public function save(NewAccount $account): RegisteredUser
            {
                $this->saved = $account;

                return new RegisteredUser(1, 2);
            }
        };
    }

    private function childInviteRepository(?ChildInvite $invite = null, ?\Throwable $findError = null)
    {
        return new class($invite, $findError) implements ChildInviteRepositoryInterface {
            public bool $findByCodeCalled = false;
            public ?int $acceptedInviteId = null;
            public ?int $acceptedUserId = null;

            public function __construct(
                private ?ChildInvite $invite,
                private ?\Throwable $findError,
            ) {
            }

            public function create(NewChildInvite $invite): void
            {
            }

            public function findByCode(string $code): ChildInvite
            {
                $this->findByCodeCalled = true;

                if (null !== $this->findError) {
                    throw $this->findError;
                }

                return $this->invite ?? throw InviteNotFound::create();
            }

            public function markAccepted(int $inviteId, int $userId, \DateTimeImmutable $now): void
            {
                $this->acceptedInviteId = $inviteId;
                $this->acceptedUserId = $userId;
            }
        };
    }
}
