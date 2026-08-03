<?php

declare(strict_types=1);

namespace App\Tests\Application\Event;

use App\Application\Event\ListEventTypes;
use App\Domain\Auth\Exception\AccessDenied;
use App\Domain\Child\Repository\ChildAccessRepositoryInterface;
use App\Domain\Event\Repository\EventTypeReadRepositoryInterface;
use App\Domain\Event\ValueObject\EventType;
use PHPUnit\Framework\TestCase;

final class ListEventTypesTest extends TestCase
{
    public function testReturnsListWhenUserHasAccess(): void
    {
        $repository = $this->eventTypeRepository([
            new EventType(1, 42, 'sleep_start', 'range', null, null, ['сон']),
        ]);

        $result = (new ListEventTypes($this->childAccess(true), $repository))(userId: 7, childId: 42);

        self::assertCount(1, $result);
        self::assertSame(42, $repository->askedChildId);
    }

    public function testDeniesAndSkipsRepositoryWhenNoAccess(): void
    {
        $repository = $this->eventTypeRepository([]);

        $this->expectException(AccessDenied::class);

        try {
            (new ListEventTypes($this->childAccess(false), $repository))(userId: 7, childId: 42);
        } finally {
            self::assertNull($repository->askedChildId);
        }
    }

    private function childAccess(bool $allowed): ChildAccessRepositoryInterface
    {
        return new class($allowed) implements ChildAccessRepositoryInterface {
            public function __construct(
                private bool $allowed,
            ) {
            }

            public function userHasAccessToChild(int $userId, int $childId): bool
            {
                return $this->allowed;
            }
        };
    }

    /**
     * @param EventType[] $eventTypes
     */
    private function eventTypeRepository(array $eventTypes)
    {
        return new class($eventTypes) implements EventTypeReadRepositoryInterface {
            public ?int $askedChildId = null;

            /**
             * @param EventType[] $eventTypes
             */
            public function __construct(
                private array $eventTypes,
            ) {
            }

            public function listByChild(int $childId): array
            {
                $this->askedChildId = $childId;

                return $this->eventTypes;
            }
        };
    }
}
