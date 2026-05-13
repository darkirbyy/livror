<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\BackpathUrlGenerator;
use PHPUnit\Framework\Attributes as PU;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;

#[PU\AllowMockObjectsWithoutExpectations]
final class BackpathUrlGeneratorTest extends TestCase
{
    private Request $request;
    private RequestStack $requestStack;
    private UrlGeneratorInterface $urlGenerator;
    private UrlMatcherInterface $urlMatcher;

    private BackpathUrlGenerator $backpathUrlGenerator;

    public function setUp(): void
    {
        $this->request = new Request([]);
        $this->requestStack = new RequestStack([$this->request]);
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->urlMatcher = $this->createMock(UrlMatcherInterface::class);

        $this->backpathUrlGenerator = new BackpathUrlGenerator($this->requestStack, $this->urlGenerator, $this->urlMatcher);
    }

    #[PU\Test]
    #[PU\DataProvider('invalidBackpathValues')]
    public function invalidBackpath(?string $backpath): void
    {
        $this->request->initialize(['backpath' => $backpath]);
        $this->urlGenerator->expects($this->once())->method('generate')->with('review_index')->willReturn('/review');
        $this->urlMatcher->expects($this->never())->method('match');

        $this->assertSame('/review', $this->backpathUrlGenerator->generate('review_index'));
    }

    #[PU\Test]
    #[PU\DataProvider('noRouteBackpathValues')]
    public function noRouteBackpath(string $backpath): void
    {
        $this->request->initialize(['backpath' => $backpath]);
        $this->urlGenerator->expects($this->once())->method('generate')->with('review_index')->willReturn('/review');
        $this->urlMatcher->expects($this->exactly(2))->method('setContext');
        $this->urlMatcher->expects($this->once())->method('match')->willThrowException(new ResourceNotFoundException());

        $this->assertSame('/review', $this->backpathUrlGenerator->generate('review_index'));
    }

    #[PU\Test]
    #[PU\DataProvider('forbiddenRouteBackpathValues')]
    public function forbiddenRouteBackpath(string $backpath, array $forbiddenRoutes, string $route): void
    {
        $this->request->initialize(['backpath' => $backpath]);
        $this->urlGenerator->expects($this->once())->method('generate')->with('review_index')->willReturn('/review');
        $this->urlMatcher->expects($this->exactly(2))->method('setContext');
        $this->urlMatcher->expects($this->once())->method('match')->willReturn(['_route' => $route]);

        $this->assertSame('/review', $this->backpathUrlGenerator->generate('review_index', $forbiddenRoutes));
    }

    #[PU\Test]
    #[PU\DataProvider('validBackpathValues')]
    public function validBackpath(string $backpath, string $route): void
    {
        $this->request->initialize(['backpath' => $backpath]);
        $this->urlGenerator->expects($this->never())->method('generate');
        $this->urlMatcher->expects($this->exactly(2))->method('setContext');
        $this->urlMatcher->expects($this->once())->method('match')->willReturn(['_route' => $route]);

        $this->assertSame($backpath, $this->backpathUrlGenerator->generate('review_index'));
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

    public static function noRouteBackpathValues(): array
    {
        return [
            'non existent route' => ['/account'],
        ];
    }

    public static function forbiddenRouteBackpathValues(): array
    {
        return [
            'forbidden route' => ['/game/125', ['game_show'], 'game_show'],
        ];
    }

    public static function validBackpathValues(): array
    {
        return [
            'root URI' => ['/game', 'game_index'],
            'root URI and query param' => ['/game?limit=20&offset=5', 'game_index'],
        ];
    }
}
