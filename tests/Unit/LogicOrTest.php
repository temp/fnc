<?php

declare(strict_types=1);

namespace FncTests\Unit;

use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;

use function Fnc\logicOr;

#[CoversFunction('Fnc\logicOr')]
final class LogicOrTest extends TestCase
{
    public function testLogicOr(): void
    {
        $this->assertFalse(logicOr(false, false));
        $this->assertTrue(logicOr(false, true));
        $this->assertTrue(logicOr(true, false));
        $this->assertTrue(logicOr(true, true));
    }
}
