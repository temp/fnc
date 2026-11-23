<?php

declare(strict_types=1);

namespace FncTests\Unit;

use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;

use function Fnc\logicAnd;

#[CoversFunction('Fnc\logicAnd')]
final class LogicAndTest extends TestCase
{
    public function testLogicAnd(): void
    {
        $this->assertFalse(logicAnd(false, false));
        $this->assertFalse(logicAnd(false, true));
        $this->assertFalse(logicAnd(true, false));
        $this->assertTrue(logicAnd(true, true));
    }
}
