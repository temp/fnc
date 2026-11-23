<?php

declare(strict_types=1);

namespace FncTests\Unit;

use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;
use Time\Duration;
use ValueError;

use function Fnc\clamp;
use function Fnc\map;

#[CoversFunction('Fnc\clamp')]
final class ClampTest extends TestCase
{
    public function testClamp(): void
    {
        $this->assertSame(1, clamp(1, 10, -5));
        $this->assertSame(5, clamp(1, 10, 5));
        $this->assertSame(10, clamp(1, 10, 15));
        $this->assertSame(1.5, clamp(1.5, 2.5, 0.0));
    }

    public function testPartialApplication(): void
    {
        $this->assertSame([0, 50, 100], map(clamp(0, 100, ?), [-5, 50, 150]));
    }

    public function testComparableObjects(): void
    {
        $min = Duration::fromSeconds(60);
        $max = Duration::fromSeconds(600);

        $this->assertSame($min, clamp($min, $max, Duration::fromSeconds(30)));
    }

    public function testMinGreaterThanMaxThrows(): void
    {
        $this->expectException(ValueError::class);

        clamp(10, 1, 5);
    }
}
