<?php

declare(strict_types=1);

namespace App\Extension;

use App\Dto\QueryParam;
use App\Enum\TypePriceEnum;
use App\Service\AutocompletionManager;
use App\Service\BackpathUrlGenerator;
use App\Service\HubUrlGenerator;
use App\Service\QueryParamHelper;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\StimulusBundle\Dto\StimulusAttributes;
use Twig\Attribute\AsTwigFilter;
use Twig\Attribute\AsTwigFunction;

class TwigExtension
{
    public function __construct(
        private TranslatorInterface $trans,
        private HubUrlGenerator $hubUrlGenerator,
        private BackpathUrlGenerator $backpathUrlGenerator,
        private QueryParamHelper $queryParamHelper,
        private AutocompletionManager $autocompletionManager,
    ) {
    }

    // Transform a full price to a type of price
    #[AsTwigFilter(name: 'to_type_price')]
    public function fullPriceToTypePrice(?int $fullPrice): TypePriceEnum
    {
        return TypePriceEnum::fromPrice($fullPrice);
    }

    // Add the full url of the hub root path
    #[AsTwigFilter(name: 'generate_root')]
    public function hubUrlGenerateRoot(string $route): string
    {
        return $this->hubUrlGenerator->generateRoot($route);
    }

    // Add the full url of the hub account path
    #[AsTwigFilter(name: 'generate_account')]
    public function hubUrlGenerateAccount(string $route, array $parameters = []): string
    {
        return $this->hubUrlGenerator->generateAccount($route, $parameters);
    }

    // Generate the backpath if exists and valid, keep the given path otherwise
    #[AsTwigFilter(name: 'generate_backpath')]
    public function backpathUrlGenerate(string $defaultRoute): string
    {
        return $this->backpathUrlGenerator->generate($defaultRoute);
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

    // Prepare html attributes for stimulus autocompletion
    #[AsTwigFunction(name: 'prepare_attributes')]
    public function autocompletePrepareAttributes(string $route, array $parameters = []): StimulusAttributes
    {
        return $this->autocompletionManager->prepareAttributes($route, $parameters);
    }
}
