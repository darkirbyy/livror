<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Dto\QueryParam;
use App\Service\QueryParamHelper;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\Attributes as PU;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

#[PU\AllowMockObjectsWithoutExpectations]
final class QueryParamHelperTest extends TestCase
{
    private Session $session;

    private static $defaultLimit = 10;
    private static $maxLimit = 50;
    private RequestStack $requestStack;

    private QueryParamHelper $queryParamHelper;

    public function setUp(): void
    {
        $this->session = new Session(new MockArraySessionStorage());
        $this->session->start();

        $this->requestStack = $this->createMock(RequestStack::class);

        $this->queryParamHelper = new QueryParamHelper(self::$defaultLimit, self::$maxLimit, $this->requestStack);
    }

    #[PU\Test]
    #[PU\DataProvider('loadValues')]
    public function load(QueryParam $queryParam, string $sessionKey, int $expectedGet): void
    {
        $storedQueryParam = new QueryParam(5, 20, [], []);
        $storedSessionKey = 'game';

        $this->session->set('livror/' . $storedSessionKey, $storedQueryParam);
        $this->requestStack->expects($this->exactly($expectedGet))->method('getSession')->willReturn($this->session);

        $this->queryParamHelper->load($queryParam, $sessionKey);
        $this->assertSame((array) $queryParam, (array) ($expectedGet > 1 ? $storedQueryParam : $queryParam));
    }

    #[PU\Test]
    #[PU\DataProvider('defaultsValues')]
    public function defaults(QueryParam $queryParam, array $defaultSorts, array $defaultFilters, QueryParam $expectedQueryParam): void
    {
        $this->queryParamHelper->defaults($queryParam, $defaultSorts, $defaultFilters);
        $this->assertSame((array) $expectedQueryParam, (array) $queryParam);
    }

    #[PU\Test]
    #[PU\DataProvider('validateValues')]
    public function validate(QueryParam $queryParam, array $allowedSortsKeys, array $allowedFiltersKeys, QueryParam $expectedQueryParam): void
    {
        $this->queryParamHelper->validate($queryParam, $allowedSortsKeys, $allowedFiltersKeys);

        $this->assertSame((array) $expectedQueryParam, (array) $queryParam);
    }

    #[PU\Test]
    #[PU\DataProvider('saveValues')]
    public function saveIsXml(QueryParam $queryParam, string $sessionKey, QueryParam $expectedQueryParam): void
    {
        $queryParamCloned = clone $queryParam;

        $mainRequest = $this->createMock(Request::class);
        $mainRequest->expects($this->once())->method('isXmlHttpRequest')->willReturn(true);
        $this->requestStack->expects($this->once())->method('getMainRequest')->willReturn($mainRequest);
        $this->requestStack->expects($this->never())->method('getSession');

        $this->queryParamHelper->save($queryParam, $sessionKey);

        $this->assertSame((array) $queryParamCloned, (array) $queryParam);
    }

    #[PU\Test]
    #[PU\DataProvider('saveValues')]
    public function saveIsNotXml(QueryParam $queryParam, string $sessionKey, QueryParam $expectedQueryParam): void
    {
        $queryParamCloned = clone $queryParam;

        $mainRequest = $this->createMock(Request::class);
        $mainRequest->expects($this->once())->method('isXmlHttpRequest')->willReturn(false);
        $this->requestStack->expects($this->once())->method('getMainRequest')->willReturn($mainRequest);
        $this->requestStack->expects($this->once())->method('getSession')->willReturn($this->session);

        $this->queryParamHelper->save($queryParam, $sessionKey);

        $this->assertSame((array) $queryParamCloned, (array) $queryParam);
        $this->assertSame((array) $expectedQueryParam, (array) $this->session->get('livror/' . $sessionKey));
    }

    #[PU\Test]
    #[PU\DataProvider('applyButFiltersToQbValues')]
    public function applyButFiltersToQb(QueryParam $queryParam, array $sortsConversion): void
    {
        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->exactly(count($queryParam->sorts)))->method('addOrderBy');
        $qb->expects($this->once())->method('setMaxResults');
        $qb->expects($this->once())->method('setFirstResult');

        $this->queryParamHelper->applyButFiltersToQb($queryParam, $qb, $sortsConversion);
    }

    #[PU\Test]
    #[PU\DataProvider('cloneWithValues')]
    public function cloneWith(QueryParam $queryParam, array $newParam, QueryParam $expectedQueryParam): void
    {
        $queryParamCloned = clone $queryParam;

        $newQueryParam = $this->queryParamHelper->cloneWith($queryParam, $newParam);

        $this->assertSame((array) $expectedQueryParam, (array) $newQueryParam);
        $this->assertSame((array) $queryParamCloned, (array) $queryParam);
    }

    #[PU\Test]
    #[PU\DataProvider('cloneResetValues')]
    public function cloneReset(QueryParam $queryParam, QueryParam $expectedQueryParam): void
    {
        $queryParamCloned = clone $queryParam;

        $newQueryParam = $this->queryParamHelper->cloneReset($queryParam);

        $this->assertSame((array) $expectedQueryParam, (array) $newQueryParam);
        $this->assertSame((array) $queryParamCloned, (array) $queryParam);
    }

    #[PU\Test]
    #[PU\DataProvider('toArrayValues')]
    public function toArray(QueryParam $queryParam, array $expectedArray): void
    {
        $queryParamCloned = clone $queryParam;

        $array = $this->queryParamHelper->toArray($queryParam);

        $this->assertSame($array, $expectedArray);
        $this->assertSame((array) $queryParamCloned, (array) $queryParam);
    }

    public static function loadValues(): array
    {
        return [
            'query param not null, session empty' => [new QueryParam(0, 10, null, null), 'review', 0],
            'query param null, session empty' => [new QueryParam(null, null, null, null), 'review', 1],
            'query param not null, session full' => [new QueryParam(0, 10, null, null), 'game', 0],
            'query param null, session full' => [new QueryParam(null, null, null, null), 'game', 2],
        ];
    }

    public static function defaultsValues(): array
    {
        return [
            'nothing' => [
                new QueryParam(5, 10, ['game' => 'asc'], ['typeGame' => [0 => 'GAME', 1 => 'DLC']]),
                ['game' => 'desc'],
                [],
                new QueryParam(5, 10, ['game' => 'asc'], ['typeGame' => [0 => 'GAME', 1 => 'DLC']]),
            ],
            'all' => [new QueryParam(null, null, null, null), ['game' => 'desc'], [], new QueryParam(0, self::$defaultLimit, ['game' => 'desc'], [])],
        ];
    }

    public static function validateValues(): array
    {
        return [
            'no change' => [
                new QueryParam(5, 10, ['game' => 'asc'], ['typeGame' => [0 => 'GAME', 1 => 'DLC']]),
                ['game'],
                ['typeGame'],
                new QueryParam(5, 10, ['game' => 'asc'], ['typeGame' => [0 => 'GAME', 1 => 'DLC']]),
            ],
            'wrong offset limit' => [new QueryParam(-5, self::$maxLimit + 1, [], []), [], [], new QueryParam(0, self::$defaultLimit, [], [])],
            'wrong sorts key' => [new QueryParam(0, 10, ['rate' => 'asc', 'game' => 'desc'], []), ['game'], [], new QueryParam(0, self::$defaultLimit, ['game' => 'desc'], [])],
            'wrong sorts value' => [new QueryParam(0, 10, ['game' => 'ascending'], []), ['game'], [], new QueryParam(0, self::$defaultLimit, [], [])],
            'wrong filters key' => [new QueryParam(0, 10, [], ['typeGame' => [0 => 'GAME']]), [], ['userId'], new QueryParam(0, self::$defaultLimit, [], [])],
            'wrong filters value 1' => [new QueryParam(0, 10, [], ['typeGame' => 'DLC']), [], ['userId'], new QueryParam(0, self::$defaultLimit, [], [])],
            'wrong filters value 2' => [new QueryParam(0, 10, [], ['typeGame' => [0 => '@DLC']]), [], ['userId'], new QueryParam(0, self::$defaultLimit, [], [])],
            'empty filters' => [new QueryParam(0, 10, [], ['typeGame' => '']), [], ['typeGame'], new QueryParam(0, self::$defaultLimit, [], ['typeGame' => []])],
        ];
    }

    public static function saveValues(): array
    {
        return [
            'all' => [new QueryParam(5, 10, ['game' => 'asc'], []), 'game', new QueryParam(0, self::$defaultLimit, ['game' => 'asc'], [])],
        ];
    }

    public static function applyButFiltersToQbValues(): array
    {
        return [
            'two sorts' => [new QueryParam(5, 10, ['game' => 'asc', 'rating' => 'desc'], []), ['game' => 'r.game', 'rating' => 'g.rating']],
            'no sort' => [new QueryParam(5, 10, [], []), ['game' => 'r.game']],
        ];
    }

    public static function cloneWithValues(): array
    {
        return [
            'change offset limit' => [new QueryParam(0, 10, ['game' => 'asc'], []), ['offset' => 5, 'limit' => 20], new QueryParam(5, 20, ['game' => 'asc'], [])],
            'change sorts' => [new QueryParam(0, 10, ['game' => 'asc'], []), ['sorts' => ['rating' => 'desc']], new QueryParam(0, 10, ['rating' => 'desc'], [])],
        ];
    }

    public static function cloneResetValues(): array
    {
        return [
            'reset offset 0' => [new QueryParam(0, 10, ['game' => 'asc'], ['type' => [0 => 'GAME']]), new QueryParam(0, null, null, null)],
            'reset offset 10' => [new QueryParam(10, 10, ['game' => 'asc'], ['type' => [0 => 'GAME']]), new QueryParam(10, null, null, null)],
        ];
    }

    public static function toArrayValues(): array
    {
        return [
            'normal ' => [
                new QueryParam(5, 20, ['game' => 'asc'], ['type' => [0 => 'GAME']]),
                ['offset' => 5, 'limit' => 20, 'sorts' => ['game' => 'asc'], 'filters' => ['type' => [0 => 'GAME']]],
            ],
            'empty filters' => [
                new QueryParam(0, 10, ['game' => 'asc'], ['type' => []]),
                ['offset' => 0, 'limit' => 10, 'sorts' => ['game' => 'asc'], 'filters' => ['type' => '']],
            ],
        ];
    }
}
