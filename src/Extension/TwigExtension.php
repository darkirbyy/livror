<?php

declare(strict_types=1);

namespace App\Extension;

use App\Dto\QueryParam;
use App\Enum\TypePriceEnum;
use App\Service\HubUrlGenerator;
use App\Service\QueryParamHelper;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;

class TwigExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private TranslatorInterface $trans,
        private RequestStack $requestStack,
        private UrlGeneratorInterface $urlGenerator,
        private HubUrlGenerator $hubUrlGenerator,
        private QueryParamHelper $queryParamHelper,
    ) {
    }

    public function getGlobals(): array
    {
        return [];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('fmt_full_price', [$this, 'fmtFullPrice']),
            new TwigFilter('hub_url_generate_root', [$this, 'hubUrlGenerateRoot']),
            new TwigFilter('hub_url_generate_account', [$this, 'hubUrlGenerateAccount']),
            new TwigFilter('query_param_clone_with', [$this, 'queryParamCloneWith']),
            new TwigFilter('query_param_to_array', [$this, 'queryParamToArray']),
        ];
    }

    public function getFunctions(): array
    {
        return [new TwigFunction('path_query', [$this, 'pathQuery'])];
    }

    // Custom formatter for the full price, to return an empty string in case of an unknown price
    public function fmtFullPrice(?int $fullPrice, string $locale): string
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
    public function queryParamCloneWith(QueryParam $queryParam, string $property, mixed $value): QueryParam
    {
        return $this->queryParamHelper->cloneWith($queryParam, $property, $value);
    }

    // Convert a query param object to an array of parameters
    public function queryParamToArray(QueryParam $queryParam): array
    {
        return $this->queryParamHelper->toArray($queryParam);
    }

    public function pathQuery($queryParam): string
    {
        $params = $this->queryParamHelper->toArray($queryParam);
        $route = $this->requestStack->getMainRequest()->attributes->get('_route');
        $url = $this->urlGenerator->generate($route, $params);

        return $url;
    }
}
