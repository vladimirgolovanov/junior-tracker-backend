<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Registration\Exception\EmailAlreadyRegistered;
use App\Domain\Registration\Repository\RegistrationRepositoryInterface;
use App\Domain\Registration\ValueObject\NewAccount;
use App\Domain\Registration\ValueObject\NewEventType;
use App\Domain\Registration\ValueObject\RegisteredUser;
use App\Domain\User\ValueObject\Email;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Types\Types;

final readonly class DoctrineRegistrationRepository implements RegistrationRepositoryInterface
{
    private const EMAIL_INDEX = 'ix_users_email';

    public function __construct(
        private Connection $connection,
    ) {
    }

    public function emailExists(Email $email): bool
    {
        return false !== $this->connection->fetchOne(
            'SELECT 1 FROM users WHERE lower(email) = :email',
            ['email' => $email->lowercased()],
        );
    }

    public function save(NewAccount $account): RegisteredUser
    {
        try {
            return $this->connection->transactional(
                function (Connection $connection) use ($account): RegisteredUser {
                    $userId = $this->insertUser($connection, $account);

                    // Присоединение по приглашению переиспользует существующего ребёнка:
                    // ни childs, ни типы событий не трогаем — только связь в child_users.
                    $childId = null !== $account->existingChildId
                        ? $account->existingChildId
                        : $this->insertChild($connection, $account);

                    $connection->executeStatement(
                        'INSERT INTO child_users (child_id, user_id, is_owner) VALUES (:childId, :userId, :isOwner)',
                        ['childId' => $childId, 'userId' => $userId, 'isOwner' => $account->isOwner],
                        ['isOwner' => ParameterType::BOOLEAN],
                    );

                    foreach ($account->eventTypes as $eventType) {
                        $eventTypeId = $this->insertEventType($connection, $childId, $eventType, null);

                        if (null !== $eventType->end) {
                            $this->insertEventType($connection, $childId, $eventType->end, $eventTypeId);
                        }
                    }

                    return new RegisteredUser($userId, $childId);
                },
            );
        } catch (UniqueConstraintViolationException $exception) {
            // Страховка на случай гонки: ix_users_email регистрозависимый,
            // поэтому одновременные "a@x.ru" и "A@x.ru" он всё равно пропустит.
            // Нарушения других ограничений наружу идут как есть — иначе поломка
            // схемы выглядела бы для клиента как занятый email.
            if (!str_contains($exception->getMessage(), self::EMAIL_INDEX)) {
                throw $exception;
            }

            throw EmailAlreadyRegistered::create();
        }
    }

    private function insertUser(Connection $connection, NewAccount $account): int
    {
        $user = $account->user;

        return (int) $connection->fetchOne(
            'INSERT INTO users (email, hashed_password, is_active, is_superuser, is_verified, created_at, updated_at)
             VALUES (:email, :hashedPassword, :isActive, :isSuperuser, :isVerified, :createdAt, :updatedAt)
             RETURNING id',
            [
                'email' => $user->email->value,
                'hashedPassword' => $user->hashedPassword,
                'isActive' => $user->isActive,
                'isSuperuser' => $user->isSuperuser,
                'isVerified' => $user->isVerified,
                'createdAt' => $user->createdAt,
                'updatedAt' => $user->updatedAt,
            ],
            [
                'isActive' => ParameterType::BOOLEAN,
                'isSuperuser' => ParameterType::BOOLEAN,
                'isVerified' => ParameterType::BOOLEAN,
                'createdAt' => Types::DATETIME_IMMUTABLE,
                'updatedAt' => Types::DATETIME_IMMUTABLE,
            ],
        );
    }

    private function insertChild(Connection $connection, NewAccount $account): int
    {
        return (int) $connection->fetchOne(
            'INSERT INTO childs (name, timezone) VALUES (:name, :timezone) RETURNING id',
            [
                'name' => $account->child->name,
                'timezone' => $account->child->timezone->value,
            ],
        );
    }

    private function insertEventType(
        Connection $connection,
        int $childId,
        NewEventType $eventType,
        ?int $parentId,
    ): int {
        [$keywords, $keywordParams] = KeywordsLiteral::build($eventType->keywords);

        $params = [
            'name' => $eventType->name,
            'childId' => $childId,
            'format' => $eventType->format,
            'color' => $eventType->color,
            'parentId' => $parentId,
            'showInLastEvents' => $eventType->showInLastEvents,
            'showInQuickActions' => $eventType->showInQuickActions,
        ] + $keywordParams;

        return (int) $connection->fetchOne(
            sprintf(
                'INSERT INTO event_types
                     (name, child_id, keywords, format, color, parent_id, show_in_last_events, show_in_quick_actions)
                 VALUES (:name, :childId, %s, :format, :color, :parentId, :showInLastEvents, :showInQuickActions)
                 RETURNING id',
                $keywords,
            ),
            $params,
            [
                'showInLastEvents' => ParameterType::BOOLEAN,
                'showInQuickActions' => ParameterType::BOOLEAN,
            ],
        );
    }
}
