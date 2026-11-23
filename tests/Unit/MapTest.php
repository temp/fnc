<?php

declare(strict_types=1);

namespace FncTests\Unit;

use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;

use function Fnc\map;
use function strtoupper;

#[CoversFunction('Fnc\map')]
final class MapTest extends TestCase
{
    public function testNull(): void
    {
        $data = null;

        $result = map($this->double(...), $data);

        $this->assertSame([], $result);
    }

    public function testEmptyArray(): void
    {
        $data = [];

        $result = map($this->double(...), $data);

        $this->assertSame([], $result);
    }

    public function testInt(): void
    {
        $data = [1, 2, 3];

        $result = map($this->double(...), $data);

        $this->assertSame([2, 4, 6], $result);
    }

    public function testString(): void
    {
        $data = ['abc' => 'def'];

        $result = map(
            static function ($i) {
                return strtoupper($i);
            },
            $data,
        );

        $this->assertSame(['abc' => 'DEF'], $result);
    }

    private function double(int $i): int
    {
        return $i * 2;
    }
}
