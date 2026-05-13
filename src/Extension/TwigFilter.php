<?php

declare(strict_types=1);

namespace App\Extension;

use App\Dto\QueryParam;
use App\Enum\TypePriceEnum;
use App\Service\BackpathUrlGenerator;
use App\Service\QueryParamHelper;
use Twig\Attribute\AsTwigFilter;

class TwigFilter
{
    public function __construct(
        private BackpathUrlGenerator $backpathUrlGenerator,
        private QueryParamHelper $queryParamHelper,
    ) {}

    #[AsTwigFilter(name: 'to_type_price')]
    public function fullPriceToTypePrice(?int $fullPrice): TypePriceEnum
    {
        return TypePriceEnum::fromPrice($fullPrice);
    }

    // Generate the backpath if exists and valid, keep the given path otherwise
    #[AsTwigFilter(name: 'generate_backpath')]
    public function backpathUrlGenerate(string $defaultRoute, array $forbiddenRoutes = []): string
    {
        return $this->backpathUrlGenerator->generate($defaultRoute, $forbiddenRoutes);
    }

    // Change one queryParam property without modyfiny the original instance
    #[AsTwigFilter(name: 'clone_with')]
    public function queryParamCloneWith(QueryParam $queryParam, array $newParam): QueryParam
    {
        return $this->queryParamHelper->cloneWith($queryParam, $newParam);
    }

    // Change one queryParam property without modyfiny the original instance
    #[AsTwigFilter(name: 'clone_reset')]
    public function queryParamCloneReset(QueryParam $queryParam): QueryParam
    {
        return $this->queryParamHelper->cloneReset($queryParam);
    }

    // Convert a query param object to an array of parameters
    #[AsTwigFilter(name: 'to_array')]
    public function queryParamToArray(QueryParam $queryParam): array
    {
        return $this->queryParamHelper->toArray($queryParam);
    }
}
