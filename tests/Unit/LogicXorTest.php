<?php

declare(strict_types=1);

namespace FncTests\Unit;

use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;

use function Fnc\logicXor;

#[CoversFunction('Fnc\logicXor')]
final class LogicXorTest extends TestCase
{
    public function testLogicXor(): void
    {
        $this->assertFalse(logicXor(false, false));
        $this->assertTrue(logicXor(false, true));
        $this->assertTrue(logicXor(true, false));
        $this->assertFalse(logicXor(true, true));
    }
}
