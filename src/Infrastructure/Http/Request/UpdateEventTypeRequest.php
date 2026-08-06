<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Request;

use App\Domain\Event\ValueObject\EventTypePatch;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Тело PATCH /api/v2/event_types/{id}.
 * Непереданное поле и явный null неразличимы и одинаково означают «не менять» —
 * так же ведёт себя FastAPI (model_dump(exclude_none=True)).
 * format не меняется: от него зависит и разбор события, и парность range-типов.
 */
final class UpdateEventTypeRequest
{
    /**
     * @param string[]|null $keywords
     */
    public function __construct(
        #[Assert\Length(min: 1, max: 255, minMessage: 'Field "name" must not be blank.', maxMessage: 'Field "name" must be at most {{ limit }} characters.')]
        public readonly ?string $name = null,

        public readonly ?string $color = null,

        #[Assert\All([new Assert\Type(type: 'string', message: 'Field "keywords" must contain strings only.')])]
        public readonly ?array $keywords = null,

        public readonly ?bool $show_in_last_events = null,

        public readonly ?bool $show_in_quick_actions = null,
    ) {
    }

    public function toPatch(): EventTypePatch
    {
        return new EventTypePatch(
            name: $this->name,
            color: $this->color,
            keywords: $this->keywords,
            showInLastEvents: $this->show_in_last_events,
            showInQuickActions: $this->show_in_quick_actions,
        );
    }
}
