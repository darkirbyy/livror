<?php

declare(strict_types=1);
use PHPUnit\Framework\TestCase;

final class FakeTest extends TestCase
{
    public function testFake(): void
    {
        $test = 1;

        $this->assertSame($test, 1);
    }
}
