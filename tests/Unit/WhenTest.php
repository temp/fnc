<?php

declare(strict_types=1);

namespace FncTests\Unit;

use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;

use function Fnc\when;
use function is_string;
use function trim;

#[CoversFunction('Fnc\when')]
final class WhenTest extends TestCase
{
    public function testWhen(): void
    {
        $this->assertSame('x', when(is_string(...), trim(...), ' x '));
        $this->assertSame(5, when(is_string(...), trim(...), 5));
    }
}
