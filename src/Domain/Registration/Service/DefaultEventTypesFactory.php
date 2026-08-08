<?php

declare(strict_types=1);

namespace App\Domain\Registration\Service;

use App\Domain\Registration\ValueObject\NewEventType;

final readonly class DefaultEventTypesFactory
{
    public function create(): array
    {
        return [
            new NewEventType(
                name: 'sleep_start',
                keywords: ['сон'],
                format: 'range',
                showInLastEvents: false,
                showInQuickActions: true,
                end: new NewEventType(
                    name: 'sleep_end',
                    keywords: null,
                    format: 'range_end',
                    showInLastEvents: false,
                    showInQuickActions: true,
                ),
            ),
            new NewEventType(
                name: 'formula',
                keywords: ['смесь'],
                format: 'metric',
                showInLastEvents: true,
                showInQuickActions: true,
                color: '#ff9eb5',
            ),
            new NewEventType(
                name: 'food',
                keywords: ['прикорм'],
                format: 'described',
                showInLastEvents: true,
                showInQuickActions: true,
                color: '#2ecc71',
            ),
            new NewEventType(
                name: 'poo',
                keywords: ['покакал'],
                format: 'plain',
                showInLastEvents: false,
                showInQuickActions: false,
                color: '#8B4513',
            ),
            new NewEventType(
                name: 'bath',
                keywords: ['ванна'],
                format: 'plain',
                showInLastEvents: false,
                showInQuickActions: false,
                color: '#8B4513', # todo: new color
            ),
            new NewEventType(
                name: 'breastfeeding_start',
                keywords: ['гв'],
                format: 'range',
                showInLastEvents: true,
                showInQuickActions: true,
                color: '#2ecc71',
                end: new NewEventType(
                    name: 'breastfeeding_end',
                    keywords: null,
                    format: 'range_end',
                    showInLastEvents: true,
                    showInQuickActions: true,
                ),
            ),
        ];
    }
}
