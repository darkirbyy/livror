<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\QueryParam;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Service to validate and complete the QueryParam Dto.
 */
final class QueryParamHelper
{
    private bool $isLoadFromSesion;

    public function __construct(private RequestStack $requestStack, private int $defaultLimit, private int $maxLimit)
    {
        $this->isLoadFromSesion = false;
    }

    // /////////////////////////////////////////////////////
    // Functions for repository ////////////////////////////
    // /////////////////////////////////////////////////////

    public function load(QueryParam $queryParam, string $sessionKey): void
    {
        $isQueryEmpty = array_all((array) $queryParam, fn ($value, $key): bool => is_null($value));
        $isSessionFull = $this->requestStack->getSession()->has('livror/' . $sessionKey);
        if ($isQueryEmpty && $isSessionFull) {
            $this->isLoadFromSesion = true;
            foreach ($this->requestStack->getSession()->get('livror/' . $sessionKey) as $property => $value) {
                $queryParam->$property = $value;
            }
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

    public function save(QueryParam $queryParam, string $sessionKey): void
    {
        if (!$this->isLoadFromSesion && !$this->requestStack->getMainRequest()->isXmlHttpRequest()) {
            $this->requestStack->getSession()->set('livror/' . $sessionKey, $queryParam);
        }
    }

    public function applyButFiltersToQb(QueryParam $queryParam, QueryBuilder $qb, array $sortsConversion): void
    {
        foreach ($queryParam->sorts as $key => $direction) {
            $qb->addOrderBy($sortsConversion[$key], strtoupper($direction));
        }

        $qb->setMaxResults($queryParam->limit + 1);
        $qb->setFirstResult($queryParam->offset);
    }

    // /////////////////////////////////////////////////////
    // Functions for twig extensions ///////////////////////
    // /////////////////////////////////////////////////////

    public function cloneWith(QueryParam $queryParam, string $property, mixed $value): QueryParam
    {
        $queryParamCloned = clone $queryParam;
        $queryParamCloned->$property = $value;

        return $queryParamCloned;
    }

    public function cloneReset(QueryParam $queryParam): QueryParam
    {
        $queryParamCloned = clone $queryParam;
        foreach ($queryParamCloned as $property => $value) {
            if ('offset' == $property) {
                continue;
            }
            $queryParamCloned->$property = null;
        }

        return $queryParamCloned;
    }

    public function toArray(QueryParam $queryParam): array
    {
        $queryParamArray = (array) $queryParam;
        if (isset($queryParamArray['filters'])) {
            $queryParamArray['filters'] = array_map(fn ($values) => [] !== $values ? $values : '', $queryParamArray['filters']);
        }

        return $queryParamArray;
    }
}
