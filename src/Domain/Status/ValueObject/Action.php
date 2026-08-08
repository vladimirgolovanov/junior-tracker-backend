<?php

declare(strict_types=1);

namespace App\Domain\Status\ValueObject;

final readonly class Action
{
    public const string FOCUS_VOLUME = 'volume';
    public const string FOCUS_DESCRIPTION = 'description';

    /**
     * @param list<int>|null $volumes
     */
    public function __construct(
        public int $eventTypeId,
        public ?string $focus,
        public bool $showInQuickActions,
        public ?array $volumes = null,
    ) {
    }

    /**
     * @param list<int> $volumes
     */
    public function withVolumes(array $volumes): self
    {
        return new self($this->eventTypeId, $this->focus, $this->showInQuickActions, $volumes);
    }
}
