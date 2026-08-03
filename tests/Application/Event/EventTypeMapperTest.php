<?php

declare(strict_types=1);

namespace App\Tests\Application\Event;

use App\Application\Event\EventTypeMapper;
use App\Domain\Event\ValueObject\EventType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class EventTypeMapperTest extends TestCase
{
    /**
     * @return iterable<string, array{string, bool, bool, bool}>
     */
    public static function formatFlags(): iterable
    {
        //                        format         show_in_filters volume describe
        yield 'range'      => ['range',       false, false, false];
        yield 'range_end'  => ['range_end',   false, false, false];
        yield 'plain'      => ['plain',       true,  false, false];
        yield 'metric'     => ['metric',      true,  true,  false];
        yield 'described'  => ['described',   true,  false, true];
    }

    #[DataProvider('formatFlags')]
    public function testDerivesFlagsFromFormat(
        string $format,
        bool $showInFilters,
        bool $volumeInput,
        bool $describeInput,
    ): void {
        $eventType = new EventType(1, 42, 'name', $format, null, null, null);

        [$row] = (new EventTypeMapper())->toArray([$eventType]);

        self::assertSame($showInFilters, $row['show_in_filters']);
        self::assertSame($volumeInput, $row['volume_input']);
        self::assertSame($describeInput, $row['describe_input']);
    }

    public function testPassesColorAndKeywordsThrough(): void
    {
        $eventType = new EventType(5, 1, 'formula', 'metric', 'ff9eb5', null, ['смесь', 'смест']);

        [$row] = (new EventTypeMapper())->toArray([$eventType]);

        self::assertSame('ff9eb5', $row['color']);
        self::assertSame(['смесь', 'смест'], $row['keywords']);
    }

    public function testKeepsFieldSetAndOrderOfContract(): void
    {
        $eventType = new EventType(2, 1, 'sleep_end', 'range_end', null, 1, null);

        [$row] = (new EventTypeMapper())->toArray([$eventType]);

        self::assertSame(
            ['id', 'format', 'color', 'parent_id', 'name', 'keywords', 'child_id', 'show_in_filters', 'volume_input', 'describe_input'],
            array_keys($row),
        );
        self::assertSame(1, $row['parent_id']);
        self::assertNull($row['keywords']);
    }
}
