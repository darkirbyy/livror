<?php

declare(strict_types=1);

namespace App\Extension;

use App\Dto\QueryParam;
use App\Enum\TypePriceEnum;
use App\Service\HubUrlGenerator;
use App\Service\QueryParamHelper;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFilter;

class TwigExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(private TranslatorInterface $trans, private HubUrlGenerator $hubUrlGenerator, private QueryParamHelper $queryParamHelper)
    {
    }

    public function getGlobals(): array
    {
        return [];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('fmt_type_price', [$this, 'fmtTypePrice']),
            new TwigFilter('generate_root', [$this, 'hubUrlGenerateRoot']),
            new TwigFilter('generate_account', [$this, 'hubUrlGenerateAccount']),
            new TwigFilter('clone_with', [$this, 'queryParamCloneWith']),
            new TwigFilter('clone_reset', [$this, 'queryParamCloneReset']),
            new TwigFilter('to_array', [$this, 'queryParamToArray']),
        ];
    }

    // Custom formatter for the full price, to return an empty string in case of an unknown price
    public function fmtTypePrice(?int $fullPrice, string $locale): string
    {
        $typePrice = TypePriceEnum::fromPrice($fullPrice);

        return TypePriceEnum::UNKNOWN != $typePrice ? $typePrice->trans($this->trans, $locale) : '';
    }

    // Add the full url of the hub root path
    public function hubUrlGenerateRoot(string $route): string
    {
        return $this->hubUrlGenerator->generateRoot($route);
    }

    // Add the full url of the hub account path
    public function hubUrlGenerateAccount(string $route, array $parameters = []): string
    {
        return $this->hubUrlGenerator->generateAccount($route, $parameters);
    }

    // Change one queryParam property without modyfiny the original instance
    public function queryParamCloneWith(QueryParam $queryParam, array $newParam): QueryParam
    {
        return $this->queryParamHelper->cloneWith($queryParam, $newParam);
    }

    // Change one queryParam property without modyfiny the original instance
    public function queryParamCloneReset(QueryParam $queryParam): QueryParam
    {
        return $this->queryParamHelper->cloneReset($queryParam);
    }

    // Convert a query param object to an array of parameters
    public function queryParamToArray(QueryParam $queryParam): array
    {
        return $this->queryParamHelper->toArray($queryParam);
    }
}
