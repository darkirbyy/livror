<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\QueryParam;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Uid\Uuid;

class QueryParamHelper
{
    private bool $isLoadFromSesion;

    public function __construct(private int $defaultLimit, private int $maxLimit, private RequestStack $requestStack)
    {
        $this->isLoadFromSesion = false;
    }

    // /////////////////////////////////////////////////////
    // Functions for repository ////////////////////////////
    // /////////////////////////////////////////////////////

    /**
     * Load the queryParam from session if the passed queryParam is null (all fields = null) and exists.
     */
    public function load(QueryParam $queryParam, string $sessionKey): void
    {
        $isQueryEmpty = array_all((array) $queryParam, fn($value, $key): bool => is_null($value));
        $this->isLoadFromSesion = $isQueryEmpty && $this->requestStack->getSession()->has($sessionKey);
        if ($this->isLoadFromSesion) {
            foreach ($this->requestStack->getSession()->get($sessionKey) as $property => $value) {
                $queryParam->$property = $value;
            }
        }
    }

    /**
     * Set defaults values so that no field is null afterwards.
     */
    public function defaults(QueryParam $queryParam, array $defaultSorts, array $defaultFilters): void
    {
        $queryParam->offset ??= 0;
        $queryParam->limit ??= $this->defaultLimit;
        $queryParam->sorts ??= $defaultSorts;
        $queryParam->filters ??= $defaultFilters;
    }

    /**
     * Validate all fields as follow:
     * - offset : positive int
     * - limit : positive int between 1 and max_limit (configurable via .env)
     * - sorts : array, only keep item if the key is valid, and value is 'asc' or 'desc'
     * - filters : array, only keep item if the key is valid, and value is an array with only alphanum characters
     *            (empty filters are converted from '' to [])
     */
    public function validate(QueryParam $queryParam, array $allowedSortsKeys, array $allowedFiltersKeys): void
    {
        $queryParam->offset = filter_var($queryParam->offset, FILTER_VALIDATE_INT, ['options' => ['default' => 0, 'min_range' => 0]]);
        $queryParam->limit = filter_var($queryParam->limit, FILTER_VALIDATE_INT, [
            'options' => ['default' => $this->defaultLimit, 'min_range' => 1, 'max_range' => $this->maxLimit],
        ]);
        $queryParam->sorts = array_filter(
            $queryParam->sorts,
            fn($value, $key): bool => in_array($key, $allowedSortsKeys, true) && in_array($value, ['asc', 'desc'], true),
            ARRAY_FILTER_USE_BOTH,
        );
        $queryParam->filters = array_map(fn($values) => '' !== $values ? $values : [], $queryParam->filters);
        $queryParam->filters = array_filter(
            $queryParam->filters,
            fn($values, $key): bool => in_array($key, $allowedFiltersKeys, true)
                && is_array($values)
                && array_all($values, fn($value) => ctype_alnum($value) || Uuid::isValid($value)),
            ARRAY_FILTER_USE_BOTH,
        );
    }

    /**
     * Save the queryParam to session (if it does not come from session or a ajax request).
     */
    public function save(QueryParam $queryParam, string $sessionKey): void
    {
        if ($this->isLoadFromSesion || $this->requestStack->getMainRequest()->isXmlHttpRequest()) {
            return;
        }

        $queryParamCloned = clone $queryParam;
        $queryParamCloned->offset = 0;
        $queryParamCloned->limit = $this->defaultLimit;
        $this->requestStack->getSession()->set($sessionKey, $queryParamCloned);
    }

    /**
     * Apply the queryParam fields (excepts filters) to a doctrine QueryBuilder.
     */
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

    /**
     * Allows to change one or more parameters, without changing the original queryParam.
     */
    public function cloneWith(QueryParam $queryParam, array $newParam): QueryParam
    {
        $queryParamCloned = clone $queryParam;
        foreach ($newParam as $property => $value) {
            $queryParamCloned->$property = $value;
        }

        return $queryParamCloned;
    }

    /**
     * Allows to reset all fields but 'offset' to null, without changing the original queryParam.
     */
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

    /**
     * Converts the queryParam to an array (empty filters are converted from [] to '').
     */
    public function toArray(QueryParam $queryParam): array
    {
        $queryParamArray = (array) $queryParam;
        if (isset($queryParamArray['filters'])) {
            $queryParamArray['filters'] = array_map(fn($values) => [] !== $values ? $values : '', $queryParamArray['filters']);
        }

        return $queryParamArray;
    }
}
