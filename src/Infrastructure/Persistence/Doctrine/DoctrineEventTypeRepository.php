<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Event\Exception\EventTypeNotFound;
use App\Domain\Event\Repository\EventTypeReadRepositoryInterface;
use App\Domain\Event\Repository\EventTypeRepositoryInterface;
use App\Domain\Event\Repository\EventTypeWriteRepositoryInterface;
use App\Domain\Event\ValueObject\EventType;
use App\Domain\Event\ValueObject\EventTypeDraft;
use App\Domain\Event\ValueObject\EventTypePatch;
use App\Domain\Event\ValueObject\RangeEventType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

final readonly class DoctrineEventTypeRepository implements EventTypeRepositoryInterface, EventTypeReadRepositoryInterface, EventTypeWriteRepositoryInterface
{
    private const COLUMNS = 'id, child_id, name, format, color, parent_id, to_json(keywords) AS keywords,
                             show_in_last_events, show_in_quick_actions';

    public function __construct(
        private Connection $connection,
    ) {
    }

    public function listByChild(int $childId): array
    {
        // to_json(keywords) отдаёт корректный JSON-массив (или null),
        // без ручного разбора Postgres-литерала text[] вида {a,b}.
        $rows = $this->connection->fetchAllAssociative(
            sprintf('SELECT %s FROM event_types WHERE child_id = :childId ORDER BY id', self::COLUMNS),
            ['childId' => $childId],
        );

        return array_map($this->hydrate(...), $rows);
    }

    public function findById(int $id): ?EventType
    {
        $row = $this->connection->fetchAssociative(
            sprintf('SELECT %s FROM event_types WHERE id = :id', self::COLUMNS),
            ['id' => $id],
        );

        return false === $row ? null : $this->hydrate($row);
    }

    public function findRangeType(int $childId, string $startName): RangeEventType
    {
        $row = $this->connection->fetchAssociative(
            'SELECT s.id AS start_id, e.id AS end_id
             FROM event_types s
             JOIN event_types e ON e.parent_id = s.id
             WHERE s.child_id = :childId AND s.name = :startName',
            ['childId' => $childId, 'startName' => $startName],
        );

        if (false === $row) {
            throw EventTypeNotFound::rangeType($childId, $startName);
        }

        return new RangeEventType((int) $row['start_id'], (int) $row['end_id']);
    }

    public function findPlainType(int $childId, string $name): int
    {
        $id = $this->connection->fetchOne(
            'SELECT id FROM event_types WHERE child_id = :childId AND name = :name',
            ['childId' => $childId, 'name' => $name],
        );

        if (false === $id) {
            throw EventTypeNotFound::plainType($childId, $name);
        }

        return (int) $id;
    }

    public function create(EventTypeDraft $draft, ?EventTypeDraft $pairedEnd = null): int
    {
        if (null === $pairedEnd) {
            return $this->insert($draft, null);
        }

        // Пара range/range_end бессмысленна наполовину: либо обе строки, либо ни одной.
        return $this->connection->transactional(function () use ($draft, $pairedEnd): int {
            $startId = $this->insert($draft, null);
            $this->insert($pairedEnd, $startId);

            return $startId;
        });
    }

    public function update(int $id, EventTypePatch $patch): void
    {
        $assignments = [];
        $params = ['id' => $id];
        $types = [];

        if (null !== $patch->name) {
            $assignments[] = 'name = :name';
            $params['name'] = $patch->name;
        }

        if (null !== $patch->color) {
            $assignments[] = 'color = :color';
            $params['color'] = $patch->color;
        }

        if (null !== $patch->keywords) {
            [$keywords, $keywordParams] = KeywordsLiteral::build($patch->keywords);
            $assignments[] = 'keywords = '.$keywords;
            $params += $keywordParams;
        }

        if (null !== $patch->showInLastEvents) {
            $assignments[] = 'show_in_last_events = :showInLastEvents';
            $params['showInLastEvents'] = $patch->showInLastEvents;
            $types['showInLastEvents'] = ParameterType::BOOLEAN;
        }

        if (null !== $patch->showInQuickActions) {
            $assignments[] = 'show_in_quick_actions = :showInQuickActions';
            $params['showInQuickActions'] = $patch->showInQuickActions;
            $types['showInQuickActions'] = ParameterType::BOOLEAN;
        }

        if ([] === $assignments) {
            return;
        }

        $this->connection->executeStatement(
            sprintf('UPDATE event_types SET %s WHERE id = :id', implode(', ', $assignments)),
            $params,
            $types,
        );
    }

    private function insert(EventTypeDraft $draft, ?int $parentId): int
    {
        [$keywords, $keywordParams] = KeywordsLiteral::build($draft->keywords);

        return (int) $this->connection->fetchOne(
            sprintf(
                'INSERT INTO event_types
                     (name, child_id, keywords, format, color, parent_id, show_in_last_events, show_in_quick_actions)
                 VALUES (:name, :childId, %s, :format, :color, :parentId, :showInLastEvents, :showInQuickActions)
                 RETURNING id',
                $keywords,
            ),
            [
                'name' => $draft->name,
                'childId' => $draft->childId,
                'format' => $draft->format,
                'color' => $draft->color,
                'parentId' => $parentId,
                'showInLastEvents' => $draft->showInLastEvents,
                'showInQuickActions' => $draft->showInQuickActions,
            ] + $keywordParams,
            [
                'showInLastEvents' => ParameterType::BOOLEAN,
                'showInQuickActions' => ParameterType::BOOLEAN,
            ],
        );
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrate(array $row): EventType
    {
        /** @var string[]|null $keywords */
        $keywords = null === $row['keywords'] ? null : json_decode($row['keywords'], true);

        return new EventType(
            id: (int) $row['id'],
            childId: (int) $row['child_id'],
            name: $row['name'],
            format: $row['format'],
            color: $row['color'],
            parentId: null === $row['parent_id'] ? null : (int) $row['parent_id'],
            keywords: $keywords,
            showInLastEvents: (bool) $row['show_in_last_events'],
            showInQuickActions: (bool) $row['show_in_quick_actions'],
        );
    }
}
