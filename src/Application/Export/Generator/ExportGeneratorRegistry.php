<?php

declare(strict_types=1);

namespace App\Application\Export\Generator;

use App\Domain\Export\Generator\ExportGenerator;
use App\Domain\Export\ValueObject\ExportType;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

/**
 * Resolves the generator responsible for a given export type. Generators are
 * collected by the 'app.export_generator' tag (declared on the interface), so a
 * new type becomes available simply by adding a tagged implementation.
 */
final class ExportGeneratorRegistry
{
    /** @var array<string, ExportGenerator> */
    private array $byType = [];

    /**
     * @param iterable<ExportGenerator> $generators
     */
    public function __construct(
        #[AutowireIterator('app.export_generator')]
        iterable $generators,
    ) {
        foreach ($generators as $generator) {
            $this->byType[$generator->type()->value] = $generator;
        }
    }

    public function get(ExportType $type): ExportGenerator
    {
        return $this->byType[$type->value]
            ?? throw new \LogicException(sprintf('No export generator registered for type "%s".', $type->value));
    }
}
