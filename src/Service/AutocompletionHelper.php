<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\StimulusBundle\Dto\StimulusAttributes;
use Symfony\UX\StimulusBundle\Helper\StimulusHelper;
use Twig\Environment;

class AutocompletionHelper
{
    public function __construct(
        private int $autocompletionMinLength,
        private TranslatorInterface $trans,
        private StimulusHelper $stimulusHelper,
        private UrlGeneratorInterface $urlGenerator,
        private Environment $twig,
    ) {}

    /**
     * Prepare html stimulus for autocompletion.
     *
     * @param string $route      endpoint route
     * @param array  $parameters endpoint parameters
     */
    public function prepareAttributes(string $placeholderKey, string $route, array $parameters = []): StimulusAttributes
    {
        $stimulusController = $this->stimulusHelper->createStimulusAttributes();
        $stimulusController->addController('symfony/ux-autocomplete/autocomplete', [
            'url' => $this->urlGenerator->generate($route, $parameters),
            'noResultsFoundText' => $this->trans->trans('form.autocomplete.noResults'),
            'minCharacters' => $this->autocompletionMinLength,
            'preload' => false,
            'optionsAsHtml' => true,
            'tomSelectOptions' => [
                'create' => false,
                'openOnFocus' => false,
                'maxItems' => 1,
                'closeAfterSelect' => true,
                'placeholder' => $this->trans->trans('form.autocomplete.placeholder.' . $placeholderKey),
                'loadThrottle' => 500,
                'plugins' => [
                    'clear_button' => false,
                    'remove_button' => false,
                ],
            ],
        ]);

        return $stimulusController;
    }

    /**
     * Transform a list of objects into an array readable by tomselect for dynamic autocompletion.
     *
     * @param string $template the template to render each object
     * @param array  $objects  list of objects to render
     */
    public function renderItems(string $template, array $objects): array
    {
        $results = array_map(
            fn($object) => [
                'value' => $object->getId(),
                'text' => $this->twig->render($template, ['object' => $object]),
            ],
            $objects,
        );

        return ['results' => $results];
    }
}
