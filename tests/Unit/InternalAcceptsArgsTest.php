<?php

declare(strict_types=1);

namespace FncTests\Unit;

use Closure;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\TestCase;

use function Fnc\_acceptsArgs;
use function Fnc\curry2;

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

    public function testCurriedClosureUsesRegisteredArity(): void
    {
        $curried = curry2(static fn ($a, $b) => $a);

        $this->assertTrue(_acceptsArgs($curried, 2));
        $this->assertFalse(_acceptsArgs($curried, 3));
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

    #[RequiresPhp('>=8.6.0')]
    public function testPartialApplicationCountsRequiredParametersOnly(): void
    {
        // trim(?, ...) keeps trim()'s optional $characters parameter, but isn't internal itself
        $trim = self::partial('trim(?, ...)');

        $this->assertTrue(_acceptsArgs($trim, 1));
        $this->assertFalse(_acceptsArgs($trim, 2));
    }

    /** Creates the partial application at runtime, PHP < 8.6 can't parse the syntax. */
    public static function partial(string $code): Closure
    {
        // phpcs:ignore Squiz.PHP.Eval.Discouraged
        return eval('return ' . $code . ';');
    }
}
