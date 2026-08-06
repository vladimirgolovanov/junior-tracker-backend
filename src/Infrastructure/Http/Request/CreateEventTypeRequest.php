<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Request;

use App\Domain\Event\ValueObject\EventTypeDraft;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Тело POST /api/v2/event_types/.
 * Свойства названы как поля запроса, чтобы ключи ошибок валидации совпадали с ними.
 */
final class CreateEventTypeRequest
{
    public const FORMATS = ['range', 'range_end', 'plain', 'described', 'metric'];

    /**
     * @param string[]|null $keywords
     */
    public function __construct(
        #[Assert\NotNull(message: 'Field "child_id" is required.')]
        #[Assert\Positive(message: 'Field "child_id" must be a positive integer.')]
        public readonly ?int $child_id = null,

        #[Assert\NotBlank(message: 'Field "name" is required.')]
        #[Assert\Length(max: 255, maxMessage: 'Field "name" must be at most {{ limit }} characters.')]
        public readonly ?string $name = null,

        #[Assert\NotBlank(message: 'Field "format" is required.')]
        #[Assert\Choice(choices: self::FORMATS, message: 'Field "format" must be one of: {{ choices }}.')]
        public readonly ?string $format = null,

        public readonly ?string $color = null,

        #[Assert\All([new Assert\Type(type: 'string', message: 'Field "keywords" must contain strings only.')])]
        public readonly ?array $keywords = null,

        public readonly bool $show_in_last_events = true,

        public readonly bool $show_in_quick_actions = true,
    ) {
    }

    public function toDraft(): EventTypeDraft
    {
        return new EventTypeDraft(
            childId: (int) $this->child_id,
            name: (string) $this->name,
            format: (string) $this->format,
            color: $this->color,
            keywords: $this->keywords,
            showInLastEvents: $this->show_in_last_events,
            showInQuickActions: $this->show_in_quick_actions,
        );
    }
}
