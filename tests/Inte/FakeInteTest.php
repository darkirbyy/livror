<?php

declare(strict_types=1);

namespace App\Tests\Inte;

use App\Repository\GameRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class FakeInteTest extends KernelTestCase
{
    public function testFake(): void
    {
        self::bootKernel();
        $container = static::getContainer()->get(GameRepository::class);
        $this->assertInstanceOf(GameRepository::class, $container);
    }
}
