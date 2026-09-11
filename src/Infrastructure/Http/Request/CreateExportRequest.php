<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Request;

use App\Domain\Export\ValueObject\ExportType;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Body of POST /api/v2/children/{childId}/exports. Property names match the
 * JSON keys so validation error keys line up with them.
 */
final readonly class CreateExportRequest
{
    public function __construct(
        #[Assert\NotNull(message: 'Field "type" is required.')]
        #[Assert\Choice(callback: [ExportType::class, 'values'], message: 'Field "type" must be a supported export type.')]
        public ?string $type = null,

        public ?string $from = null,

        public ?string $to = null,
    ) {
    }

    public function type(): ExportType
    {
        // Safe: Assert\Choice guarantees a known value before this runs.
        return ExportType::from((string) $this->type);
    }

    public function from(): ?\DateTimeImmutable
    {
        return null === $this->from ? null : DateTimeParser::parse($this->from, 'from');
    }

    public function to(): ?\DateTimeImmutable
    {
        return null === $this->to ? null : DateTimeParser::parse($this->to, 'to');
    }
}
