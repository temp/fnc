<?php

declare(strict_types=1);

namespace FncTests\Unit;

use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;

use function Fnc\_acceptsArgs;
use function str_pad;
use function trim;

// phpcs:disable SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter

#[CoversFunction('Fnc\_acceptsArgs')]
final class InternalAcceptsArgsTest extends TestCase
{
    public function testClosure(): void
    {
        $fn = static fn ($a, $b = null) => $a;

        $this->assertTrue(_acceptsArgs($fn, 1));
        $this->assertTrue(_acceptsArgs($fn, 2));
        $this->assertFalse(_acceptsArgs($fn, 3));
    }

    public function testVariadic(): void
    {
        $this->assertTrue(_acceptsArgs(static fn (...$args) => $args, 5));
    }

    public function testInternalFunctionCountsRequiredParametersOnly(): void
    {
        // trim() has an optional $characters parameter that must not receive a key
        $this->assertTrue(_acceptsArgs('trim', 1));
        $this->assertFalse(_acceptsArgs('trim', 2));
    }

    public function testPartialApplicationCountsRequiredParametersOnly(): void
    {
        // trim(?, ...) keeps trim()'s optional $characters parameter, but isn't internal itself
        $this->assertTrue(_acceptsArgs(trim(?, ...), 1));
        $this->assertFalse(_acceptsArgs(trim(?, ...), 2));
        $this->assertTrue(_acceptsArgs(str_pad(?, ?, ...), 2));
        $this->assertFalse(_acceptsArgs(str_pad(?, ?, ...), 3));
    }
}
