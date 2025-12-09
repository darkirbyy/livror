<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\StimulusBundle\Dto\StimulusAttributes;
use Symfony\UX\StimulusBundle\Helper\StimulusHelper;

class AutocompletionHelper
{
    public function __construct(
        private int $autocompletionMinLength,
        private TranslatorInterface $trans,
        private StimulusHelper $stimulusHelper,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function prepareAttributes(string $route, array $parameters = []): StimulusAttributes
    {
        $stimulusController = $this->stimulusHelper->createStimulusAttributes();
        $stimulusController->addController('symfony/ux-autocomplete/autocomplete', [
            'url' => $this->urlGenerator->generate($route, $parameters),
            'noResultsFoundText' => $this->trans->trans('form.autocomplete.noResults'),
            'minCharacters' => $this->autocompletionMinLength,
            'preload' => false,
            'tomSelectOptions' => [
                'create' => false,
                'openOnFocus' => false,
                'maxItems' => 1,
                'optionsAsHtml' => true,
                'closeAfterSelect' => true,
                'placeholder' => $this->trans->trans('form.autocomplete.placeholder'),
                'loadThrottle' => 500,
                'plugins' => [
                    'clear_button' => false,
                    'remove_button' => false,
                ],
            ],
        ]);

        return $stimulusController;
    }
}
