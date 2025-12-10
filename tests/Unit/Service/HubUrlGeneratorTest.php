<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\HubUrlGenerator;
use PHPUnit\Framework\Attributes as PU;
use PHPUnit\Framework\TestCase;

final class HubUrlGeneratorTest extends TestCase
{
    private $hubBaseUrl;
    private $hubAccountRoute;

    private $hubUrlGenerator;

    public function setUp(): void
    {
        $this->hubBaseUrl = '';
        $this->hubAccountRoute = '/hub/account';

        $this->hubUrlGenerator = new HubUrlGenerator($this->hubBaseUrl, $this->hubAccountRoute);
    }

    #[PU\Test]
    #[PU\DataProvider('generateValues')]
    public function generateRoot(string $route, array $params, $expectedUrl): void
    {
        $this->assertSame($this->hubBaseUrl . $route, $this->hubUrlGenerator->generateRoot($route));
    }

    #[PU\Test]
    #[PU\DataProvider('generateValues')]
    public function generateAccount(string $route, array $params, $expectedUrl): void
    {
        $this->assertSame($this->hubBaseUrl . $this->hubAccountRoute . $expectedUrl, $this->hubUrlGenerator->generateAccount($route, $params));
    }

    public static function generateValues(): array
    {
        return [
            'empty no param' => ['', [], ''],
            'empty with params' => ['', ['token' => 'abcd'], '?token=abcd'],
            'route no param' => ['/check', [], '/check'],
            'route with params' => ['/logout', ['token' => 'abcd'], '/logout?token=abcd'],
        ];
    }
}
