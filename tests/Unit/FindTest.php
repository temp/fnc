<?php

declare(strict_types=1);

namespace FncTests\Unit;

use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;

use function Fnc\find;

#[CoversFunction('Fnc\find')]
final class FindTest extends TestCase
{
    public function testFind(): void
    {
        $this->assertSame(['id' => 2], find(static fn ($v) => $v['id'] > 1, [['id' => 1], ['id' => 2], ['id' => 3]]));
    }

    public function testNotFound(): void
    {
        $greaterThanFive = static fn (int $v): bool => $v > 5;

        $this->assertNull(find($greaterThanFive, [1, 2]));
    }
}
