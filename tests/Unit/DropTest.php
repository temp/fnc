<?php

declare(strict_types=1);

namespace FncTests\Unit;

use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;

use function Fnc\drop;

#[CoversFunction('Fnc\drop')]
final class DropTest extends TestCase
{
    public function testNumericArrayValue(): void
    {
        $data = ['foo', 'bar', 'baz'];

        $result = drop(2, $data);

        $this->assertSame(['baz'], $result);
    }

    public function testAssociativeArrayValue(): void
    {
        $data = ['foo' => 1, 'bar' => 2, 'baz' => 3];

        $result = drop(2, $data);

        $this->assertSame(['baz' => 3], $result);
    }

    public function testStringValue(): void
    {
        $data = 'foobar';

        $result = drop(3, $data);

        $this->assertSame('bar', $result);
    }
}
