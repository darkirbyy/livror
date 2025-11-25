<?php

declare(strict_types=1);

namespace App\Extension;

use App\Dto\QueryParam;
use App\Enum\TypePriceEnum;
use App\Service\BackpathUrlGenerator;
use App\Service\HubUrlGenerator;
use App\Service\QueryParamHelper;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\StimulusBundle\Dto\StimulusAttributes;
use Symfony\UX\StimulusBundle\Helper\StimulusHelper;
use Twig\Attribute\AsTwigFilter;
use Twig\Attribute\AsTwigFunction;

class TwigExtension
{
    public function __construct(
        private TranslatorInterface $trans,
        private StimulusHelper $stimulusHelper,
        private UrlGeneratorInterface $urlGenerator,
        private HubUrlGenerator $hubUrlGenerator,
        private BackpathUrlGenerator $backpathUrlGenerator,
        private QueryParamHelper $queryParamHelper,
    ) {
    }

    // Custom formatter for the full price, to return an empty string in case of an unknown price
    #[AsTwigFilter(name: 'fmt_type_price')]
    public function fmtTypePrice(?int $fullPrice, string $locale): string
    {
        $typePrice = TypePriceEnum::fromPrice($fullPrice);

        return TypePriceEnum::UNKNOWN != $typePrice ? $typePrice->trans($this->trans, $locale) : '';
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

    #[AsTwigFunction(name: 'prepare_autocomplete')]
    public function prepareAutocomplete(string $route, array $parameters = []): StimulusAttributes
    {
        $stimulusController = $this->stimulusHelper->createStimulusAttributes();
        $stimulusController->addController('symfony/ux-autocomplete/autocomplete', [
            'url' => $this->urlGenerator->generate($route, $parameters),
            'noResultsFoundText' => $this->trans->trans('form.autocomplete.noResults'),
            'minCharacters' => 4,
            'preload' => false,
            'tomSelectOptions' => [
                'create' => false,
                'maxItems' => 1,
                'closeAfterSelect' => true,
                'placeholder' => $this->trans->trans('form.autocomplete.placeholder'),
            ],
        ]);

        return $stimulusController;
    }
}
