<?php

declare(strict_types=1);

namespace FncTests\Unit;

use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;

use function Fnc\reduce;
use function sprintf;
use function str_pad;

#[CoversFunction('Fnc\reduce')]
final class ReduceTest extends TestCase
{
    public function testReduce(): void
    {
        $data = [1, 2, 3];

        $add = static function ($a, $b) {
            return $a + $b;
        };

        $this->assertEquals(16, reduce($add, 10, $data));

        $b = ['a' => 1, 'b' => 2];
        $concatKeyVals = static function ($acc, $value, $key) {
            return $acc . (string) $key . (string) $value;
        };

        $this->assertEquals('a1b2', reduce($concatKeyVals, '', $b));
    }

    public function testReduceWithInternalFunction(): void
    {
        $this->assertSame(5, reduce('max', 0, [1, 5, 3]));
    }

    public function testReducePassesKeyIfAccepted(): void
    {
        $result = reduce(static fn ($acc, $v, $k) => $acc . $k . $v, '', ['a' => 1, 'b' => 2]);

        $this->assertSame('a1b2', $result);
    }

    public function testReducePassesKeyToVariadicFunction(): void
    {
        $result = reduce(static fn ($acc, $v, ...$rest) => [...$acc, $rest], [], ['a' => 1]);

        $this->assertSame([['a']], $result);
    }

    public function testPartialApplicationWithEnoughPlaceholdersReceivesKey(): void
    {
        $this->assertSame('x1y2', reduce(sprintf('%s%3$s%2$s', ?, ?, ?), '', ['x' => 1, 'y' => 2]));
    }

    public function testPartialApplicationDoesNotReceiveKey(): void
    {
        // str_pad()'s optional $pad_string must not receive the key 'a'
        $this->assertSame('x  ', reduce(str_pad(?, ?, ...), 'x', ['a' => 3]));
    }
}
