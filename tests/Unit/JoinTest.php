<?php

declare(strict_types=1);

namespace FncTests\Unit;

use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;

use function Fnc\join;

// phpcs:disable Generic.PHP.ForbiddenFunctions.FoundWithAlternative

#[CoversFunction('Fnc\join')]
final class JoinTest extends TestCase
{
    public function testWithZeroElements(): void
    {
        $data = [];

        $result = join('_', $data);

        $this->assertSame('', $result);
    }

    public function testWithOneElement(): void
    {
        $data = ['foo'];

        $result = join('_', $data);

        $this->assertSame('foo', $result);
    }

    public function testWithMultipleElements(): void
    {
        $data = ['foo', 'bar'];

        $result = join('_', $data);

        $this->assertSame('foo_bar', $result);
    }

    public function testWithMultipleMixedElements(): void
    {
        $result = join(' ', ['foo', 'bar', 1, 2.5]);

        $this->assertSame('foo bar 1 2.5', $result);
    }
}
