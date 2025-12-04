<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Main\Game;
use App\Entity\Main\Steam;
use App\Enum\SearchModeEnum;
use App\Repository\GameRepository;
use App\Repository\SteamRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\StimulusBundle\Dto\StimulusAttributes;
use Symfony\UX\StimulusBundle\Helper\StimulusHelper;

class AutocompletionManager
{
    public function __construct(
        private int $autocompletionLimit,
        private int $autocompletionMinLength,
        private Security $security,
        private TranslatorInterface $trans,
        private SteamRepository $steamRepo,
        private GameRepository $gameRepo,
        private StimulusHelper $stimulusHelper,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function fromSteam(?string $search, SearchModeEnum $searchMode): array
    {
        // Sanitize the user input
        $search = $this->sanitizeSearch($search, $searchMode);

        // Query the database and return the data as an array formatted for tomselect
        $repoMethod = $searchMode->toRepoMethod();
        $result = strlen($search) >= $this->autocompletionMinLength ? $this->steamRepo->$repoMethod($search, $this->autocompletionLimit) : [];
        $data = array_map(fn (Steam $s) => ['value' => $s->getId(), 'text' => $s->getName() . ' <small>[' . $s->getId() . ']</small>'], $result);

        return $data;
    }

    public function fromGameWithoutReview(?string $search, SearchModeEnum $searchMode): array
    {
        // Sanitize the user input
        $search = $this->sanitizeSearch($search, $searchMode);

        // Get the user id to filter game without review from this user
        $userId = $this->security->getUser()->getId();

        // Query the database and return the data as an array formatted for tomselect
        $repoMethod = $searchMode->toRepoMethod() . 'WithoutReview';
        $result = strlen($search) >= $this->autocompletionMinLength ? $this->gameRepo->$repoMethod($search, $this->autocompletionLimit, $userId) : [];
        $data = array_map(fn (Game $g) => ['value' => $g->getId(), 'text' => $g->getName()], $result);

        return $data;
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

    private function sanitizeSearch(?string $search, SearchModeEnum $searchMode): string
    {
        // Return empty string if empty
        if (empty($search)) {
            return '';
        }

        // Remove multiple whitespaces
        $search = trim(preg_replace('/\s+/', ' ', $search));

        // Remove special characters that can interfer with mariadb fulltext search
        $search = preg_replace('/[^\p{L}\p{N}\s\-]/u', ' ', $search);

        // Lower all remaining characters
        $search = mb_strtolower($search);

        if (SearchModeEnum::PATTERN == $searchMode) {
            // Identify each word (max 5) and surround each with + and * for mariadb fulltext boolean mode
            $words = array_slice(array_filter(explode(' ', $search), fn ($word) => strlen($word) > 0), 0, 5);
            $search = implode('', array_map(fn ($word) => '+' . $word . '*', $words));
        } else {
            // Add % wildcard for mariadb like clause
            $search = '%' . $search . '%';
        }

        return $search;
    }
}
