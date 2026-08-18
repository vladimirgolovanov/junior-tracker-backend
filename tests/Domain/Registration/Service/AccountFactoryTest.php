<?php

declare(strict_types=1);

namespace App\Tests\Domain\Registration\Service;

use App\Domain\Child\ValueObject\Timezone;
use App\Domain\Registration\Service\AccountFactory;
use App\Domain\Registration\Service\DefaultEventTypesFactory;
use App\Domain\Registration\ValueObject\NewAccount;
use App\Domain\User\Service\PasswordHasherInterface;
use App\Domain\User\ValueObject\Credentials;
use App\Domain\User\ValueObject\Email;
use App\Domain\User\ValueObject\PlainPassword;
use PHPUnit\Framework\TestCase;

/**
 * Правила онбординга: что именно получает новый пользователь.
 */
final class AccountFactoryTest extends TestCase
{
    public function testStoresHashedPasswordInsteadOfPlainOne(): void
    {
        self::assertSame('hashed:correcthorse', $this->createAccount()->user->hashedPassword);
    }

    public function testGivesChildTheDefaultEventTypes(): void
    {
        $eventTypes = $this->createAccount()->eventTypes;

        self::assertSame(
            ['sleep_start', 'formula', 'food', 'poo', 'bath', 'breastfeeding_start'],
            array_map(static fn ($eventType): string => $eventType->name, $eventTypes),
        );
        self::assertSame('sleep_end', $eventTypes[0]->end?->name);
    }

    public function testJoinsExistingChildInsteadOfCreatingNewOne(): void
    {
        $account = $this->joinExistingChild(42);

        self::assertNull($account->child);               // нового ребёнка не создаём
        self::assertSame(42, $account->existingChildId);  // используем текущего
        self::assertFalse($account->isOwner);             // присоединившийся — не владелец
        self::assertSame([], $account->eventTypes);       // типы событий не дублируем
    }

    private function joinExistingChild(int $childId): NewAccount
    {
        $factory = new AccountFactory($this->passwordHasher(), new DefaultEventTypesFactory());

        return $factory->createForExistingChild(
            new Credentials(new Email('parent@example.com'), new PlainPassword('correcthorse')),
            $childId,
            new \DateTimeImmutable('2026-07-23 10:00'),
        );
    }

    private function createAccount(?\DateTimeImmutable $now = null): NewAccount
    {
        $factory = new AccountFactory($this->passwordHasher(), new DefaultEventTypesFactory());

        return $factory->create(
            new Credentials(new Email('parent@example.com'), new PlainPassword('correcthorse')),
            new Timezone('Europe/Moscow'),
            $now ?? new \DateTimeImmutable('2026-07-23 10:00'),
        );
    }

    private function passwordHasher(): PasswordHasherInterface
    {
        return new class implements PasswordHasherInterface {
            public function hash(PlainPassword $password): string
            {
                return 'hashed:'.$password->value;
            }

            public function verify(string $plainPassword, string $hashedPassword): bool
            {
                return 'hashed:'.$plainPassword === $hashedPassword;
            }
        };
    }
}
