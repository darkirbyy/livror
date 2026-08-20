<?php

declare(strict_types=1);

namespace App\Extension;

use App\Service\AutocompletionHelper;
use Symfony\UX\StimulusBundle\Dto\StimulusAttributes;
use Twig\Attribute\AsTwigFunction;

class TwigFunction
{
    public function __construct(private AutocompletionHelper $autocompletionHelper) {}

    // Prepare html attributes for stimulus autocompletion
    #[AsTwigFunction(name: 'prepare_attributes')]
    public function autocompletePrepareAttributes(string $placeholderKey, string $route, array $parameters = []): StimulusAttributes
    {
        return $this->autocompletionHelper->prepareAttributes($placeholderKey, $route, $parameters);
    }
}
