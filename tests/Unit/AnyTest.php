<?php

declare(strict_types=1);

namespace FncTests\Unit;

use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;

use function Fnc\any;
use function Fnc\isEmpty;

#[CoversFunction('Fnc\any')]
final class AnyTest extends TestCase
{
    public function testAllNotEmpty(): void
    {
        $data = [1, 2];

        $result = any(isEmpty(...), $data);

        $this->assertFalse($result);
    }

    public function testAllEmpty(): void
    {
        $data = [null, null];

        $result = any(isEmpty(...), $data);

        $this->assertTrue($result);
    }

    public function testPartlyEmpty(): void
    {
        $data = [null, 2];

        $result = any(isEmpty(...), $data);

        $this->assertTrue($result);
    }
}
