<?php

declare(strict_types=1);

namespace FncTests\Unit;

use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;
use stdClass;

use function Fnc\always;

#[CoversFunction('Fnc\always')]
final class AlwaysTest extends TestCase
{
    public function testReturnsSameInstance(): void
    {
        $value = new stdClass();

        $this->assertSame($value, always($value)());
    }

    public function testIgnoresArguments(): void
    {
        $value = new stdClass();

        $this->assertSame($value, always($value)(1, 'a'));
    }
}
