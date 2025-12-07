<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\BackpathUrlGenerator;
use PHPUnit\Framework\Attributes as PU;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class BackpathUrlGeneratorTest extends TestCase
{
    #[PU\Test]
    #[PU\DataProvider('invalidBackpathValues')]
    public function invalidBackpath(?string $backpath): void
    {
        $requestStack = new RequestStack([new Request(['backpath' => $backpath])]);
        $backpathUrlGenerator = new BackpathUrlGenerator($requestStack);
        $this->assertSame('/review', $backpathUrlGenerator->generate('/review'));
    }

    #[PU\Test]
    #[PU\DataProvider('validBackpathValues')]
    public function validBackpath(string $backpath): void
    {
        $requestStack = new RequestStack([new Request(['backpath' => $backpath])]);
        $backpathUrlGenerator = new BackpathUrlGenerator($requestStack);
        $this->assertSame($backpath, $backpathUrlGenerator->generate('/review'));
    }

    public static function invalidBackpathValues(): array
    {
        return [
            'null' => [null],
            'empty' => [''],
            'external URL' => ['https://duckduckgo.com/'],
            'relative URI' => ['../game'],
        ];
    }

    public static function validBackpathValues(): array
    {
        return [
            'root URI' => ['/game'],
            'root URI and query param' => ['/game?limit=20&offset=5'],
        ];
    }
}
