<?php

declare(strict_types=1);

namespace FncTests\Unit;

use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;
use stdClass;

use function Fnc\identity;

#[CoversFunction('Fnc\identity')]
final class IdentityTest extends TestCase
{
    public function testIdentity(): void
    {
        $data = new stdClass();

        $result = identity($data);

        $this->assertSame($data, $result);
    }
}
