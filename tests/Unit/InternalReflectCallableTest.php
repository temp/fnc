<?php

declare(strict_types=1);

namespace FncTests\Unit;

use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;

use function Fnc\_reflectCallable;

#[CoversFunction('Fnc\_reflectCallable')]
final class InternalReflectCallableTest extends TestCase
{
    public function testCallable(): void
    {
        $this->assertSame('trim', _reflectCallable('trim')->getName());
    }

    public function testArrayCallable(): void
    {
        $this->assertSame('testArrayCallable', _reflectCallable([$this, 'testArrayCallable'])->getName());
    }
}
