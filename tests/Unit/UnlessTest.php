<?php

declare(strict_types=1);

namespace FncTests\Unit;

use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;

use function Fnc\isNil;
use function Fnc\unless;
use function trim;

#[CoversFunction('Fnc\unless')]
final class UnlessTest extends TestCase
{
    public function testUnless(): void
    {
        $this->assertSame('x', unless(isNil(...), trim(...), ' x '));
        $this->assertNull(unless(isNil(...), trim(...), null));
    }
}
