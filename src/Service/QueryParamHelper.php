<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\QueryParam;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Service to validate and complete the QueryParam Dto.
 */
final readonly class QueryParamHelper
{
    public function __construct(private RequestStack $requestStack, private int $defaultLimit, private int $maxLimit)
    {
    }

    public function load(QueryParam $queryParam, string $sessionKey): void
    {
        $isQueryEmpty = array_all((array) $queryParam, fn ($value, $key): bool => is_null($value));
        $isSessionFull = $this->requestStack->getSession()->has($sessionKey);
        if ($isQueryEmpty && $isSessionFull) {
            foreach ($this->requestStack->getSession()->get($sessionKey) as $property => $value) {
                $queryParam->$property = $value;
            }
        }
    }

    public function save(QueryParam $queryParam, string $sessionKey): void
    {
        // todo : optimize : don't save if it comes from session
        if (!$this->requestStack->getMainRequest()->isXmlHttpRequest()) {
            $this->requestStack->getSession()->set($sessionKey, $queryParam);
        }
    }

    public function defaults(QueryParam $queryParam, array $defaultSorts, array $defaultFilters): void
    {
        $queryParam->offset ??= 0;
        $queryParam->limit ??= $this->defaultLimit;
        $queryParam->sorts ??= $defaultSorts;
        $queryParam->filters ??= $defaultFilters;
    }

    public function validate(QueryParam $queryParam, array $allowedSortsKeys, array $allowedFiltersKeys): void
    {
        $queryParam->offset = filter_var($queryParam->offset, FILTER_VALIDATE_INT, ['options' => ['default' => 0, 'min_range' => 0]]);
        $queryParam->limit = filter_var($queryParam->limit, FILTER_VALIDATE_INT, [
            'options' => ['default' => $this->defaultLimit, 'min_range' => 1, 'max_range' => $this->maxLimit],
        ]);
        $queryParam->sorts = array_filter(
            $queryParam->sorts,
            fn ($value, $key): bool => in_array($key, $allowedSortsKeys, true) && in_array($value, ['asc', 'desc'], true),
            ARRAY_FILTER_USE_BOTH,
        );
        $queryParam->filters = array_map(fn ($values) => '' !== $values ? $values : [], $queryParam->filters);
        $queryParam->filters = array_filter(
            $queryParam->filters,
            fn ($values, $key): bool => in_array($key, $allowedFiltersKeys, true) && is_array($values) && array_all($values, fn ($value) => ctype_alnum($value)),
            ARRAY_FILTER_USE_BOTH,
        );
    }

    public function applyToQb(QueryParam $queryParam, QueryBuilder $qb, array $sortsConversion, array $filtersConversion): void
    {
        foreach ($queryParam->sorts as $key => $direction) {
            $qb->addOrderBy($sortsConversion[$key], strtoupper($direction));
        }

        foreach ($queryParam->filters as $key => $values) {
            $qb->andWhere($filtersConversion[$key] . ' IN (:' . $key . ')')->setParameter($key, $values);
        }

        $qb->setMaxResults($queryParam->limit + 1);
        $qb->setFirstResult($queryParam->offset);
    }

    public function cloneWith(QueryParam $queryParam, string $property, mixed $value): QueryParam
    {
        $queryParamCloned = clone $queryParam;
        $queryParamCloned->$property = $value;

        return $queryParamCloned;
    }

    public function toArray(QueryParam $queryParam): array
    {
        $queryParamArray = (array) $queryParam;
        $queryParamArray['filters'] = array_map(fn ($values) => [] !== $values ? $values : '', $queryParamArray['filters']);

        return $queryParamArray;
    }
}
