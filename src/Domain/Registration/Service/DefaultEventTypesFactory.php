<?php

declare(strict_types=1);

namespace App\Domain\Registration\Service;

use App\Domain\Registration\ValueObject\NewEventType;

/**
 * Набор типов событий, который получает каждый новый ребёнок.
 * Перенесён из DEFAULT_EVENT_TYPES в FastAPI и должен совпадать с ним,
 * пока обе регистрации не сведены в одну.
 */
final readonly class DefaultEventTypesFactory
{
    /**
     * @return NewEventType[]
     */
    public function create(): array
    {
        return [
            new NewEventType(
                name: 'sleep_start',
                keywords: ['сон'],
                format: 'range',
                end: new NewEventType(
                    name: 'sleep_end',
                    keywords: null,
                    format: 'range_end',
                ),
            ),
            new NewEventType(
                name: 'formula',
                keywords: ['смесь'],
                format: 'metric',
                color: '#ff9eb5',
            ),
            new NewEventType(
                name: 'food',
                keywords: ['прикорм'],
                format: 'described',
                color: '#2ecc71',
            ),
            new NewEventType(
                name: 'poo',
                keywords: ['покакал'],
                format: 'plain',
                color: '#8B4513',
            ),
            new NewEventType(
                name: 'bath',
                keywords: ['ванна'],
                format: 'plain',
                color: '#8B4513', # todo: new color
            ),
            new NewEventType(
                name: 'breastfeeding_start',
                keywords: ['гв'],
                format: 'range',
                color: '#2ecc71',
                end: new NewEventType(
                    name: 'breastfeeding_end',
                    keywords: null,
                    format: 'range_end',
                ),
            ),
        ];
    }
}
